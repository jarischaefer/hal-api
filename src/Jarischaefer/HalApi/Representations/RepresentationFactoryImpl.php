<?php namespace Jarischaefer\HalApi\Representations;

use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class RepresentationFactoryImpl
 * @package Jarischaefer\HalApi\Representations
 */
class RepresentationFactoryImpl implements RepresentationFactory
{

	/**
	 * @var LinkFactory
	 */
	private $linkFactory;
	/**
	 * @var RouteHelper
	 */
	private $routeHelper;

	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper)
	{
		$this->linkFactory = $linkFactory;
		$this->routeHelper = $routeHelper;
	}

	/**
	 * @inheritdoc
	 */
	public function create(HalApiLink $self, HalApiLink $parent)
	{
		return new HalApiRepresentationImpl($this->linkFactory, $this->routeHelper, $self, $parent);
	}

}
