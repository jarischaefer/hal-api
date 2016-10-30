<?php namespace Jarischaefer\HalApi\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jarischaefer\HalApi\Middleware\HalApiETagMiddleware;
use Mockery;
use PHPUnit_Framework_TestCase;

class HalApiETagMiddlewareTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var Response
	 */
	private $response;
	/**
	 * @var HalApiETagMiddleware
	 */
	private $middleware;

	/**
	 * @inheritdoc
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->middleware = new HalApiETagMiddleware;
		$this->request = Mockery::mock(Request::class);
		$this->response = Mockery::mock(Response::class);
	}

	public function testETag()
	{
		$this->request->shouldReceive('isMethodSafe')->once()->andReturn(true);
		$this->request->shouldReceive('getETags')->once()->andReturn([]);

		$this->response->shouldReceive('getContent')->once()->andReturn('foo');
		$this->response->shouldReceive('setMaxAge')->once()->with(0);
		$this->response->shouldReceive('setEtag')->once()->with(md5('foo'));

		$this->middleware->handle($this->request, function () {
			return $this->response;
		});
	}

	public function testMatchingETag()
	{
		$this->request->shouldReceive('isMethodSafe')->once()->andReturn(true);
		$this->request->shouldReceive('getETags')->once()->andReturn([md5('foo')]);

		$this->response->shouldReceive('getContent')->once()->andReturn('foo');
		$this->response->shouldReceive('setMaxAge')->once()->with(0);
		$this->response->shouldReceive('setEtag')->once()->with(md5('foo'));
		$this->response->shouldReceive('setNotModified')->once();

		$this->middleware->handle($this->request, function () {
			return $this->response;
		});
	}

	public function testETagIncompatibleResponse()
	{
		$response = $this->middleware->handle($this->request, function () {
			return 'foo bar baz';
		});

		$this->assertEquals('foo bar baz', $response);
	}

}
