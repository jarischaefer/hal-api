<?php namespace Jarischaefer\HalApi\Representations;

use Illuminate\Contracts\Pagination\Paginator;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;

/**
 * Interface RepresentationFactory
 * @package Jarischaefer\HalApi\Representations
 */
interface RepresentationFactory
{

	/**
	 * @param HalApiLink $self
	 * @param HalApiLink $parent
	 * @return HalApiRepresentation
	 */
	public function create(HalApiLink $self, HalApiLink $parent): HalApiRepresentation;

	/**
	 * @param HalApiLink $self
	 * @param HalApiLink $parent
	 * @param Paginator $paginator
	 * @param HalApiTransformerContract $transformer
	 * @param string $relation
	 * @return HalApiPaginatedRepresentation
	 */
	public function paginated(HalApiLink $self, HalApiLink $parent, Paginator $paginator, HalApiTransformerContract $transformer, string $relation): HalApiPaginatedRepresentation;

}
