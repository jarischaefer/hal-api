<?php namespace Jarischaefer\HalApi\Tests\Helpers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Tests\TestCase;

class ResourceRouteTest extends TestCase
{

	private function assertRoute(RouteCollection $routes, $uri, $httpMethod, $actionName)
	{
		/** @var Route $route */
		foreach ($routes as $route) {
			if ($route->getUri() == $uri && in_array($httpMethod, $route->getMethods()) && $route->getActionName() == $actionName) {
				return true;
			}
		}

		$this->fail('Could not find route with uri [' . $uri . '], method [' . $httpMethod . '] and action name [' . $actionName . ']');
		return false;
	}

	public function testResourceRoute()
	{
		/* @var Dispatcher $dispatcher */
		$dispatcher = $this->getMock(Dispatcher::class);
		$router = new Router($dispatcher, null);
		$routeHelper = new RouteHelper($router);

		$routeHelper->resource('test', 'TestController')
			->get('get_test', 'get_test')
			->post('post_test', 'post_test')
			->put('put_test', 'put_test')
			->patch('patch_test', 'patch_test')
			->delete('delete_test', 'delete_test')
			->rawGet('rawget', 'rawget')
			->rawPost('rawpost', 'rawpost')
			->rawPut('rawput', 'rawput')
			->rawPatch('rawpatch', 'rawpatch')
			->rawDelete('rawdelete', 'rawdelete')
			->done();

		$routes = $router->getRoutes();

		$this->assertRoute($routes, 'test', 'GET', 'TestController@index');
		$this->assertRoute($routes, 'test?' . RouteHelper::PAGINATION_URI, 'GET', 'TestController@index');
		$this->assertRoute($routes, 'test', 'POST', 'TestController@store');
		$this->assertRoute($routes, 'test/{test}', 'GET', 'TestController@show');
		$this->assertRoute($routes, 'test/{test}', 'PUT', 'TestController@update');
		$this->assertRoute($routes, 'test/{test}', 'PATCH', 'TestController@update');
		$this->assertRoute($routes, 'test/{test}', 'DELETE', 'TestController@destroy');
		$this->assertRoute($routes, 'test/{test}/get_test', 'GET', 'TestController@get_test');
		$this->assertRoute($routes, 'test/{test}/post_test', 'POST', 'TestController@post_test');
		$this->assertRoute($routes, 'test/{test}/put_test', 'PUT', 'TestController@put_test');
		$this->assertRoute($routes, 'test/{test}/patch_test', 'PATCH', 'TestController@patch_test');
		$this->assertRoute($routes, 'test/{test}/delete_test', 'DELETE', 'TestController@delete_test');
		$this->assertRoute($routes, 'test/rawget', 'GET', 'TestController@rawget');
		$this->assertRoute($routes, 'test/rawpost', 'POST', 'TestController@rawpost');
		$this->assertRoute($routes, 'test/rawput', 'PUT', 'TestController@rawput');
		$this->assertRoute($routes, 'test/rawpatch', 'PATCH', 'TestController@rawpatch');
		$this->assertRoute($routes, 'test/rawdelete', 'DELETE', 'TestController@rawdelete');
	}

}