<?php namespace Jarischaefer\HalApi\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Helpers\RouteHelper;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{

	/**
	 * The base URL to use while testing the application.
	 *
	 * @var string
	 */
	protected $baseUrl = 'http://localhost';

	/**
	 * Creates the application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public function createApplication()
	{
		$app = require __DIR__.'/../../../../../../../bootstrap/app.php';

		$app->make(Kernel::class)->bootstrap();

		return $app;
	}

	/**
	 * @inheritdoc
	 */
	public function setUp()
	{
		parent::setUp();
	}

	/**
	 * @return Router
	 */
	protected function createRouter(): Router
	{
		/** @var Dispatcher $dispatcher */
		$dispatcher = $this->getMock(Dispatcher::class);
		return new Router($dispatcher, $this->app);
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
