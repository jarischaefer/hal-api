<?php namespace Jarischaefer\HalApi\Representations;

use Jarischaefer\HalApi\Routing\HalApiLink;

/**
 * Interface HalApiRepresentation
 * @package Jarischaefer\HalApi\Representations
 */
interface HalApiRepresentation
{

	/**
	 * The name of the link pointing to the current resource.
	 */
	const SELF = 'self';
	/**
	 * The name of the link pointing to the current resource's parent.
	 */
	const PARENT = 'parent';

	/**
	 * Flag which indicates if subordinate routes should be added to the response automatically.
	 *
	 * @param bool $flag
	 * @return
	 */
	public function setAutoSubordinateRoutes(bool $flag);

	/**
	 * Adds an item directly to the API's root. The following keys are not allowed: data, meta, _links, _embedded.
	 * Use the specific methods meant for the inclusion of those.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return HalApiRepresentation
	 */
	public function add(string $key, $value): HalApiRepresentation;

	/**
	 * Adds metadata (e.g. pagination info) to the API.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return HalApiRepresentation
	 */
	public function meta(string $key, $value): HalApiRepresentation;

	/**
	 * Adds metadata (e.g. pagination info) to the API.
	 *
	 * @param array $meta
	 * @return HalApiRepresentation
	 */
	public function metaFromArray(array $meta): HalApiRepresentation;

	/**
	 * Adds data (e.g. a model) to the "data" key. Existing data will be overwritten.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return HalApiRepresentation
	 */
	public function data(string $key, $value): HalApiRepresentation;

	/**
	 * Behaves like the data() method, but takes an array. Existing data will be overwritten.
	 *
	 * @param array $data
	 * @return HalApiRepresentation
	 */
	public function dataFromArray(array $data): HalApiRepresentation;

	/**
	 * Adds a link. Existing relations will be overwritten.
	 *
	 * @param string $relation
	 * @param HalApiLink $link
	 * @return HalApiRepresentation
	 */
	public function link(string $relation, HalApiLink $link): HalApiRepresentation;

	/**
	 * Adds multiple links. Existing relations will be overwritten.
	 *
	 * @param array $links
	 * @return HalApiRepresentation
	 */
	public function links(array $links): HalApiRepresentation;

	/**
	 * Embeds a sub-API element to the current API's _embedded field. Existing relations will be overwritten.
	 * This method is supposed to be used for relations containing only one element.
	 *
	 * @param string $relation
	 * @param HalApiRepresentation $representation
	 * @return HalApiRepresentation
	 */
	public function embedSingle(string $relation, HalApiRepresentation $representation): HalApiRepresentation;

	/**
	 * Embeds a sub-API element to the current API's _embedded field. Existing relations will be overwritten.
	 * This method is supposed to be used for relations containing multiple elements.
	 *
	 * @param string $relation
	 * @param HalApiRepresentation $representation
	 * @return mixed
	 */
	public function embedMulti(string $relation, HalApiRepresentation $representation): HalApiRepresentation;

	/**
	 * Embeds multiple sub-API elements to the current API's _embedded field.
	 *
	 * Already existing relations will be overwritten.
	 *
	 * @param array $embed
	 * @return HalApiRepresentation
	 */
	public function embedFromArray(array $embed): HalApiRepresentation;

	/**
	 * Returns the API as an array.
	 *
	 * @return array
	 */
	public function build(): array;

	/**
	 * Returns the API as a JSON string.
	 *
	 * @return string
	 */
	public function __toString(): string;

}
