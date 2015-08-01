<?php namespace Jarischaefer\HalApi\Tests;

use Illuminate\Contracts\Console\Kernel;
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
	 * @var RouteHelper
	 */
	protected $routeHelper;

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

		$this->routeHelper = $this->app->make(RouteHelper::class);
	}

}
