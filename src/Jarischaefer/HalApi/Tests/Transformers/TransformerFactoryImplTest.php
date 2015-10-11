<?php namespace Jarischaefer\HalApi\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformer;
use Jarischaefer\HalApi\Transformers\TransformerFactoryImpl;
use Mockery;

class TestingTransformer extends HalApiTransformer
{

	public function transform(Model $model)
	{
		// TODO: Implement transform() method.
	}

	protected function getLinks(Model $model)
	{
		// TODO: Implement getLinks() method.
	}

	protected function getEmbedded(Model $model)
	{
		// TODO: Implement getEmbedded() method.
	}

}

class TransformerFactoryImplTest extends TestCase
{

	public function testCreate()
	{
		$self = new Route(['GET'], '/api', ['foo']);
		$parent = new Route(['GET'], '/', ['bar']);

		$urlGenerator = $this->app->make(UrlGenerator::class);
		$representationFactory = $this->app->make(RepresentationFactory::class);
		/** @var LinkFactory $linkFactory */
		$linkFactory = Mockery::mock(LinkFactory::class);
		/** @var Application $applicationMock */
		$applicationMock = Mockery::mock($this->app);

		$linkFactory->shouldReceive('create')
			->withArgs([$parent]);
		$applicationMock->shouldReceive('make')
			->withArgs(['url'])
			->andReturn($urlGenerator);
		$expected = new TestingTransformer($linkFactory, $representationFactory, $this->routeHelper, $self, $parent);
		$applicationMock->shouldReceive('make')
			->withArgs([TestingTransformer::class, [$linkFactory, $representationFactory, $this->routeHelper, $self, $parent, 123]])
			->andReturn($expected);

		$factory = new TransformerFactoryImpl($applicationMock, $linkFactory, $representationFactory, $this->routeHelper);
		$created = $factory->create(TestingTransformer::class, $self, $parent, [123]);

		$this->assertEquals($expected, $created);
	}

}
