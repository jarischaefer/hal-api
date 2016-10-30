<?php namespace Jarischaefer\HalApi\Tests\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;
use Jarischaefer\HalApi\Middleware\HalApiCacheMiddleware;
use Mockery;
use PHPUnit_Framework_TestCase;

class HalApiCacheMiddlewareTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory;
	/**
	 * @var Repository
	 */
	private $config;
	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var Route
	 */
	private $route;
	/**
	 * @var HalApiCacheMiddleware
	 */
	private $middleware;

	/**
	 * @inheritdoc
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->cacheFactory = Mockery::mock(CacheFactory::class);
		$this->config = Mockery::mock(Repository::class);
		$this->config->shouldReceive('get')->with('app.debug', false)->andReturn(false);
		$this->request = Mockery::mock(Request::class);
		$this->route = Mockery::mock(Route::class);
		$this->middleware = new HalApiCacheMiddleware($this->cacheFactory, $this->config);
	}

	public function testGETRequest()
	{
		$cache = Mockery::mock(HalApiCache::class);
		$cache->shouldReceive('key')->once()->with(Request::METHOD_GET, '/')->andReturn('GET_/');
		$cache->shouldReceive('persist')->once()->with('GET_/', Mockery::on(function (Closure $closure) {
			return $closure($this->request) === 'foo';
		}))->andReturn('foo');

		$controller = Mockery::mock(HalApiControllerContract::class);
		$controller->shouldReceive('getCache')->once()->with($this->cacheFactory)->andReturn($cache);
		$this->request->shouldReceive('route')->once()->andReturn($this->route);
		$this->request->shouldReceive('getMethod')->once()->andReturn(Request::METHOD_GET);
		$this->request->shouldReceive('getUri')->once()->andReturn('/');
		$this->request->shouldReceive('isMethodSafe')->once()->andReturn(false);
		$this->route->shouldReceive('getActionName')->once()->andReturn(get_class($controller));

		$response = $this->middleware->handle($this->request, function (Request $request) {
			if ($request !== $this->request) {
				$this->fail('Invalid request');
			}
			return 'foo';
		});

		$this->assertEquals('foo', $response);
	}

	public function testPOSTRequest()
	{
		$this->request->shouldReceive('isMethodSafe')->once()->andReturn(false);

		$response = $this->middleware->handle($this->request, function (Request $request) {
			if ($request !== $this->request) {
				$this->fail('Invalid request');
			}
			return 'foo';
		});

		$this->assertEquals('foo', $response);
	}

}
