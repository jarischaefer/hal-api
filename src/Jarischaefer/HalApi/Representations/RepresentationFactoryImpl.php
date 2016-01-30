<?php namespace Jarischaefer\HalApi\Representations;

use Illuminate\Contracts\Pagination\Paginator;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;

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

	/**
	 * @param LinkFactory $linkFactory
	 * @param RouteHelper $routeHelper
	 */
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

	/**
	 * @inheritdoc
	 */
	public function paginated(HalApiLink $self, HalApiLink $parent, Paginator $paginator, HalApiTransformerContract $transformer, $relation)
	{
		return new HalApiPaginatedRepresentationImpl($this->linkFactory, $this->routeHelper, $self, $parent, $paginator, $transformer, $relation);
	}

}
