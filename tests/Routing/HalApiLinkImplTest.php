<?php namespace Jarischaefer\HalApi\Tests\Routing;

use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Routing\HalApiLinkImpl;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Tests\TestCase;
use Mockery;

class HalApiLinkImplTest extends TestCase
{

	public function testGetLink()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters/foo');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertEquals('/parameters/foo?bar=test', $link->getLink());
	}

	public function testGetRoute()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters/foo');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertEquals($route, $link->getRoute());
	}

	public function testGetParameters()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters/foo');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertEquals(['foo'], $link->getParameters());
	}

	public function testGetQueryString()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->twice()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters/foo');
		$urlGenerator->shouldReceive('action')
			->twice()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertEquals('bar=test', $link->getQueryString());

		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], 'bar=test');
		$this->assertEquals('bar=test', $link->getQueryString());
	}

	public function testIsTemplated()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters/foo');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertTrue($link->isTemplated());
	}

	public function testIsTemplatedFalse()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething')
			->andReturn('/parameters');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [])
			->andReturn('/parameters');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, [], '?bar=test');

		$this->assertFalse($link->isTemplated());
	}

	public function testIsTemplatedQueryString()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['page' => 10])
			->andReturn('/parameters');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters?page={page}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['page' => 10], '?bar=test');

		$this->assertTrue($link->isTemplated());
	}

	public function testBuild()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$this->assertEquals([
			'href' => '/parameters/foo?bar=test',
			'templated' => true,
		], $link->build());
	}

	public function testToString()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', [], false)
			->andReturn('/parameters');
		$urlGenerator->shouldReceive('action')
			->once()
			->with('Foo\Bar\Controllers\TestController@doSomething', ['foo'])
			->andReturn('/parameters/foo');

		/** @var HalApiUrlGenerator $urlGenerator */
		$route = new Route(['GET'], '/parameters/{parameter}', ['controller' => 'Foo\Bar\Controllers\TestController@doSomething']);
		$link = new HalApiLinkImpl($urlGenerator, $route, ['foo'], '?bar=test');

		$build = $link->build();
		$this->assertEquals([
			'href' => '/parameters/foo?bar=test',
			'templated' => true,
		], $build);
		$this->assertEquals(json_encode($build), (string)$link);
	}

}
