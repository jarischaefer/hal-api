<?php namespace Jarischaefer\HalApi\Tests\Helpers;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Controllers\HalApiController;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Tests\TestCase;
use ReflectionClass;

class TestController extends HalApiController
{

	/**
	 * @inheritdoc
	 */
	public static function getRelation($action = null)
	{
		return $action ? 'test@' . $action : 'test';
	}

}

class RouteHelperTest extends TestCase
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

		$helper = RouteHelper::make($router);
		$helper->resource('test', TestController::class)->pagination()->done();

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

		$this->assertNotNull($routes->getByAction(TestController::actionName('index')), 'index route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName('show')), 'show route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName('store')), 'store route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName('update')), 'update route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName('destroy')), 'destroy route was not found.');
	}

	public function testGetRouteByAction()
	{
		\Route::get('/test', TestController::actionName('test'));
		$test = $this->routeHelper->byAction(TestController::actionName('test'));
		$this->assertEquals($test->getActionName(), TestController::actionName('test'));

		try {
			$this->routeHelper->byAction(null);
			$this->fail('Route should not have been found.');
		} catch (Exception $e) {
			// expected
		}

		try {
			$this->routeHelper->byAction('');
			$this->fail('Route should not have been found.');
		} catch (Exception $e) {
			// expected
		}
	}

	public function testGetParentRoute()
	{
		self::clearRoutes();

		\Route::get('/test', TestController::actionName('test'));
		\Route::get('/test/sub1', TestController::actionName('sub1'));
		\Route::get('/test/sub1/sub2', TestController::actionName('sub2'));

		$test = $this->routeHelper->byAction(TestController::actionName('test'));
		$sub1 = $this->routeHelper->byAction(TestController::actionName('sub1'));
		$sub2 = $this->routeHelper->byAction(TestController::actionName('sub2'));

		$parentTest = $this->routeHelper->parent($test);
		$parentSub1 = $this->routeHelper->parent($sub1);
		$parentSub2 = $this->routeHelper->parent($sub2);

		$this->assertEquals($test->getActionName(), $parentTest->getActionName());
		$this->assertEquals($test->getActionName(), $parentSub1->getActionName());
		$this->assertEquals($sub1->getActionName(), $parentSub2->getActionName());
	}

	public function testGetSubordinateRoutes()
	{
		self::clearRoutes();

		\Route::get('/test', TestController::actionName('test'));
		\Route::get('/test/sub1', TestController::actionName('sub1'));
		\Route::get('/test/sub1/sub2', TestController::actionName('sub2'));

		$test = $this->routeHelper->byAction(TestController::actionName('test'));
		$sub1 = $this->routeHelper->byAction(TestController::actionName('sub1'));
		$sub2 = $this->routeHelper->byAction(TestController::actionName('sub2'));

		$subordinateTest = $this->routeHelper->subordinates($test);
		$subordinateSub1 = $this->routeHelper->subordinates($sub1);
		$subordinateSub2 = $this->routeHelper->subordinates($sub2);

		$this->assertEquals(2, count($subordinateTest));
		$this->assertEquals(TestController::actionName('sub1'), $subordinateTest[0]->getActionName());
		$this->assertEquals(TestController::actionName('sub2'), $subordinateTest[1]->getActionName());

		$this->assertEquals(1, count($subordinateSub1));
		$this->assertEquals(TestController::actionName('sub2'), $subordinateSub1[0]->getActionName());

		$this->assertEquals(0, count($subordinateSub2));
	}

}
