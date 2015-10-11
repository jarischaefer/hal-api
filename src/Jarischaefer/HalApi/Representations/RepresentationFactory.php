<?php namespace Jarischaefer\HalApi\Representations;

use Jarischaefer\HalApi\Routing\HalApiLink;

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
	public function create(HalApiLink $self, HalApiLink $parent);

}
