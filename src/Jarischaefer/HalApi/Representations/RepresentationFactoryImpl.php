<?php namespace Jarischaefer\HalApi\Representations;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Pagination\Paginator;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;

/**
 * Class RepresentationFactoryImpl
 * @package Jarischaefer\HalApi\Representations
 */
final class RepresentationFactoryImpl implements RepresentationFactory
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
	 * @var Gate
	 */
	private $gate;

	/**
	 * @param LinkFactory $linkFactory
	 * @param RouteHelper $routeHelper
	 * @param Gate $gate
	 */
	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper, Gate $gate)
	{
		$this->linkFactory = $linkFactory;
		$this->routeHelper = $routeHelper;
		$this->gate = $gate;
	}

	/**
	 * @inheritdoc
	 */
	public function create(HalApiLink $self, HalApiLink $parent): HalApiRepresentation
	{
		return new HalApiRepresentationImpl($this->linkFactory, $this->routeHelper, $this->gate, $self, $parent);
	}

	/**
	 * @inheritdoc
	 */
	public function paginated(HalApiLink $self, HalApiLink $parent, Paginator $paginator, HalApiTransformerContract $transformer, string $relation): HalApiPaginatedRepresentation
	{
		return new HalApiPaginatedRepresentationImpl($this->linkFactory, $this->routeHelper, $this->gate, $self, $parent, $paginator, $transformer, $relation);
	}

}
