<?php namespace Jarischaefer\HalApi\Tests\Helpers;

use Exception;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Controllers\HalApiController;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Tests\TestCase;

class TestController extends HalApiController
{

	/**
	 * @inheritdoc
	 */
	public static function getRelationName(): string
	{
		return 'test';
	}

}

class RouteHelperTest extends TestCase
{

	public function testRouteHelper()
	{
		$helper = $this->createRouteHelper();
		$helper->resource('test', TestController::class)->done();
		$routes = $helper->getRouter()->getRoutes();

		$found = false;
		/** @var Route $route */
		foreach ($routes as $route) {
			if (strcmp($route->getUri(), 'test?' . RouteHelper::PAGINATION_URI) === 0) {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found, 'Did not find pagination uri in routes list.');

		$this->assertNotNull($routes->getByAction(TestController::actionName(RouteHelper::INDEX)), 'index route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName(RouteHelper::SHOW)), 'show route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName(RouteHelper::STORE)), 'store route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName(RouteHelper::UPDATE)), 'update route was not found.');
		$this->assertNotNull($routes->getByAction(TestController::actionName(RouteHelper::DESTROY)), 'destroy route was not found.');
	}

	public function testGetRouteByAction()
	{
		$helper = $this->createRouteHelper();
		$helper->getRouter()->get('/test', TestController::actionName('test'));

		$test = $helper->byAction(TestController::actionName('test'));
		$this->assertEquals($test->getActionName(), TestController::actionName('test'));

		try {
			$helper->byAction('');
			$this->fail('Route should not have been found.');
		} catch (Exception $e) {
			// expected
		}
	}

	public function testGetParentRoute()
	{
		$helper = $this->createRouteHelper();
		$router = $helper->getRouter();
		$router->get('/test', TestController::actionName('test'));
		$router->get('/test/sub1', TestController::actionName('sub1'));
		$router->get('/test/sub1/sub2', TestController::actionName('sub2'));

		$test = $helper->byAction(TestController::actionName('test'));
		$sub1 = $helper->byAction(TestController::actionName('sub1'));
		$sub2 = $helper->byAction(TestController::actionName('sub2'));

		$parentTest = $helper->parent($test);
		$parentSub1 = $helper->parent($sub1);
		$parentSub2 = $helper->parent($sub2);

		$this->assertEquals($test->getActionName(), $parentTest->getActionName());
		$this->assertEquals($test->getActionName(), $parentSub1->getActionName());
		$this->assertEquals($sub1->getActionName(), $parentSub2->getActionName());
	}

	public function testGetSubordinateRoutes()
	{
		$helper = $this->createRouteHelper();
		$router = $helper->getRouter();

		$router->get('/test', TestController::actionName('test'));
		$router->get('/test/sub1', TestController::actionName('sub1'));
		$router->get('/test/sub1/sub2', TestController::actionName('sub2'));

		$test = $helper->byAction(TestController::actionName('test'));
		$sub1 = $helper->byAction(TestController::actionName('sub1'));
		$sub2 = $helper->byAction(TestController::actionName('sub2'));

		$subordinateTest = $helper->subordinates($test);
		$subordinateSub1 = $helper->subordinates($sub1);
		$subordinateSub2 = $helper->subordinates($sub2);

		$this->assertEquals(2, count($subordinateTest));
		$this->assertEquals(TestController::actionName('sub1'), $subordinateTest[0]->getActionName());
		$this->assertEquals(TestController::actionName('sub2'), $subordinateTest[1]->getActionName());

		$this->assertEquals(1, count($subordinateSub1));
		$this->assertEquals(TestController::actionName('sub2'), $subordinateSub1[0]->getActionName());

		$this->assertEquals(0, count($subordinateSub2));
	}

}
