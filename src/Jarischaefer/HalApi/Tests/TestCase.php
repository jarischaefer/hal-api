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
	protected function createRouter()
	{
		/** @var Dispatcher $dispatcher */
		$dispatcher = $this->getMock(Dispatcher::class);
		$router = new Router($dispatcher, null);

		return $router;
	}

	/**
	 * @param Router|null $router
	 * @return RouteHelper
	 */
	protected function createRouteHelper(Router $router = null)
	{
		if ($router === null) {
			$router = $this->createRouter();
		}

		return RouteHelper::make($router);
	}

}
