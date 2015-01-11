<?php

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Routing\RouteHelper;

class RouteHelperHalTest extends TestCase
{

	private static function clearRoutes()
	{
		$routes = \Route::getRoutes();
		$reflectionClass = new ReflectionClass($routes);
		$routesProperty = $reflectionClass->getProperty('routes');
		$allRoutesProperty = $reflectionClass->getProperty('allRoutes');
		$routesProperty->setAccessible(true);
		$allRoutesProperty->setAccessible(true);
		$routesProperty->setValue($routes, []);
		$allRoutesProperty->setValue($routes, []);
	}

	public function testRouteHelper()
	{
		/* @var Dispatcher $dispatcher */
		$dispatcher = $this->getMock(Dispatcher::class);
		$router = new Router($dispatcher, null);

		$router->group(['namespace' => 'App\Http\Controllers'], function(Router $router) use (&$helper)
		{
			$helper = RouteHelper::make($router);
			$helper->delete('test/{id}/{whatever}', 'App\Http\Controllers\UnitTest', 'deleteTest');
			$helper->resource('test', 'App\Http\Controllers\ResourceControllerUnitTest')->pagination()->done();
		});

		$routes = $router->getRoutes();

		$found = false;
		/* @var Route $route */
		foreach ($routes as $route) {
			if ($route->getUri() == 'test?' . RouteHelper::PAGINATION_URI) {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found, 'Did not find pagination uri in routes list.');

		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\UnitTest@deleteTest'), 'deleteTest route was not found.');
		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\ResourceControllerUnitTest@index'), 'index route was not found.');
		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\ResourceControllerUnitTest@show'), 'show route was not found.');
		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\ResourceControllerUnitTest@store'), 'store route was not found.');
		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\ResourceControllerUnitTest@update'), 'update route was not found.');
		$this->assertNotNull($router->getRoutes()->getByAction('App\Http\Controllers\ResourceControllerUnitTest@destroy'), 'destroy route was not found.');
	}

	public function testGetRouteByAction()
	{
		\Route::get('/test', 'App\Http\Controllers\TestController@test');
		$test = RouteHelper::byAction('App\Http\Controllers\TestController@test');
		$this->assertEquals($test->getActionName(), 'App\Http\Controllers\TestController@test');

		try {
			RouteHelper::byAction(null);
			$this->fail('Route should not have been found.');
		} catch (Exception $e) {
			// expected
		}

		try {
			RouteHelper::byAction('');
			$this->fail('Route should not have been found.');
		} catch (Exception $e) {
			// expected
		}
	}

	public function testGetParentRoute()
	{
		self::clearRoutes();

		\Route::get('/test', 'App\Http\Controllers\TestController@test');
		\Route::get('/test/sub1', 'App\Http\Controllers\TestController@sub1');
		\Route::get('/test/sub1/sub2', 'App\Http\Controllers\TestController@sub2');

		$test = RouteHelper::byAction('App\Http\Controllers\TestController@test');
		$sub1 = RouteHelper::byAction('App\Http\Controllers\TestController@sub1');
		$sub2 = RouteHelper::byAction('App\Http\Controllers\TestController@sub2');

		$parentTest = RouteHelper::parent($test);
		$parentSub1 = RouteHelper::parent($sub1);
		$parentSub2 = RouteHelper::parent($sub2);

		$this->assertEquals($test->getActionName(), $parentTest->getActionName());
		$this->assertEquals($test->getActionName(), $parentSub1->getActionName());
		$this->assertEquals($sub1->getActionName(), $parentSub2->getActionName());
	}

	public function testGetSubordinateRoutes()
	{
		self::clearRoutes();

		\Route::get('/test', 'App\Http\Controllers\TestController@test');
		\Route::get('/test/sub1', 'App\Http\Controllers\TestController@sub1');
		\Route::get('/test/sub1/sub2', 'App\Http\Controllers\TestController@sub2');

		$test = RouteHelper::byAction('App\Http\Controllers\TestController@test');
		$sub1 = RouteHelper::byAction('App\Http\Controllers\TestController@sub1');
		$sub2 = RouteHelper::byAction('App\Http\Controllers\TestController@sub2');

		$subordinateTest = RouteHelper::subordinates($test);
		$subordinateSub1 = RouteHelper::subordinates($sub1);
		$subordinateSub2 = RouteHelper::subordinates($sub2);

		$this->assertEquals(2, count($subordinateTest));
		$this->assertEquals('App\Http\Controllers\TestController@sub1', $subordinateTest[0]->getActionName());
		$this->assertEquals('App\Http\Controllers\TestController@sub2', $subordinateTest[1]->getActionName());

		$this->assertEquals(1, count($subordinateSub1));
		$this->assertEquals('App\Http\Controllers\TestController@sub2', $subordinateSub1[0]->getActionName());

		$this->assertEquals(0, count($subordinateSub2));
	}

}