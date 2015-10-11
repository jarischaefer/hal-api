<?php namespace Jarischaefer\HalApi\Tests\Representations;

use Illuminate\Contracts\Routing\UrlGenerator;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\RepresentationFactoryImpl;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;
use Jarischaefer\HalApi\Tests\TestCase;
use Mockery;

class RepresentationFactoryImplTest extends TestCase
{

	public function testCreate()
	{
		$urlGenerator = Mockery::mock(UrlGenerator::class);
		/** @var UrLGenerator $urlGenerator */
		$linkFactory = new LinkFactoryImpl($urlGenerator);
		$self = Mockery::mock(HalApiLink::class);
		$parent = Mockery::mock(HalApiLink::class);
		/**
		 * @var HalApiLink $self
		 * @var HalApiLink $parent
		 */
		$factory = new RepresentationFactoryImpl($linkFactory, $this->routeHelper);
		$representation = $factory->create($self, $parent);

		$this->assertInstanceOf(HalApiRepresentation::class, $representation);
	}

}
