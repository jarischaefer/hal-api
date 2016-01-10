<?php namespace Jarischaefer\HalApi\Tests\Representations;

use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\RepresentationFactoryImpl;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;
use Jarischaefer\HalApi\Tests\TestCase;
use Mockery;

class RepresentationFactoryImplTest extends TestCase
{

	public function testCreate()
	{
		/** @var HalApiUrlGenerator $urlGenerator */
		$urlGenerator = Mockery::mock(HalApiUrlGenerator::class);
		$linkFactory = new LinkFactoryImpl($urlGenerator);
		/** @var HalApiLink $self */
		$self = Mockery::mock(HalApiLink::class);
		/** @var HalApiLink $parent */
		$parent = Mockery::mock(HalApiLink::class);

		$factory = new RepresentationFactoryImpl($linkFactory, $this->createRouteHelper());
		$representation = $factory->create($self, $parent);

		$this->assertInstanceOf(HalApiRepresentation::class, $representation);
	}

}
