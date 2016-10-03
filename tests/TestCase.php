<?php namespace Jarischaefer\HalApi\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
{

	/**
	 * @return Router
	 */
	protected function createRouter(): Router
	{
		/** @var Dispatcher $dispatcher */
		$dispatcher = $this->createMock(Dispatcher::class);
		return new Router($dispatcher, null);
	}

	/**
	 * @param Router|null $router
	 * @return RouteHelper
	 */
	protected function createRouteHelper(Router $router = null): RouteHelper
	{
		return $router ? RouteHelper::make($router) : RouteHelper::make($this->createRouter());
	}

}
