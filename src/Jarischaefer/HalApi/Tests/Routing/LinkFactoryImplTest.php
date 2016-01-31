<?php namespace Jarischaefer\HalApi\Tests\Routing;

use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;
use Jarischaefer\HalApi\Tests\TestCase;
use Mockery;

class LinkFactoryImplTest extends TestCase
{

	public function testCreate()
	{
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$urlGenerator->shouldReceive('action')
			->atLeast($this->once())
			->withArgs(['Jarischaefer\HalApi\Tests\TestController@doSomething', ['foo', 'bar']])
			->andReturn('/params/foo/bar');
		$route = new Route(['GET'], '/params/{paramonce}/{paramtwo}', ['controller' => 'Jarischaefer\HalApi\Tests\TestController@doSomething']);
		/** @var HalApiUrlGenerator $urlGenerator */
		$factory = new LinkFactoryImpl($urlGenerator);
		$link = $factory->create($route, ['foo', 'bar'], '?foo=bar');

		$this->assertEquals('/params/foo/bar?foo=bar', $link->getLink());
		$this->assertEquals(['foo', 'bar'], $link->getParameters());
		$this->assertEquals('foo=bar', $link->getQueryString());
	}

}
