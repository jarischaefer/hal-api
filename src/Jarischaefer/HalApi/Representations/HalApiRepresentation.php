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
	 * @var boolean
	 */
	public function setAutoSubordinateRoutes($flag);

	/**
	 * Adds an item directly to the API's root. The following keys are not allowed: data, meta, _links, _embedded.
	 * Use the specific methods meant for the inclusion of those.
	 *
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function add($key, $value);

	/**
	 * Adds metadata (e.g. pagination info) to the API.
	 *
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function meta($key, $value);

	/**
	 * Adds metadata (e.g. pagination info) to the API.
	 *
	 * @param array $meta
	 * @return $this
	 */
	public function metaFromArray(array $meta);

	/**
	 * Adds data (e.g. a model) to the "data" key. Existing data will be overwritten.
	 *
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function data($key, $value);

	/**
	 * Behaves like the data() method, but takes an array. Existing data will be overwritten.
	 *
	 * @param array $data
	 * @return $this
	 */
	public function dataFromArray(array $data);

	/**
	 * Adds a link. Existing relations will be overwritten.
	 *
	 * @param $relation
	 * @param HalApiLink $link
	 * @return $this
	 */
	public function link($relation, HalApiLink $link);

	/**
	 * Adds multiple links. Existing relations will be overwritten.
	 *
	 * @param array $links
	 * @return $this
	 */
	public function links(array $links);

	/**
	 * Embeds a sub-API element to the current API's _embedded field. Existing relations will be overwritten.
	 * This method is supposed to be used for relations containing only one element.
	 *
	 * @param $relation
	 * @param HalApiRepresentation $api
	 * @return $this
	 */
	public function embedSingle($relation, HalApiRepresentation $api);

	/**
	 * Embeds a sub-API element to the current API's _embedded field. Existing relations will be overwritten.
	 * This method is supposed to be used for relations containing multiple elements.
	 *
	 * @param string $relation
	 * @param HalApiRepresentation $api
	 * @return mixed
	 */
	public function embedMulti($relation, HalApiRepresentation $api);

	/**
	 * Embeds multiple sub-API elements to the current API's _embedded field.
	 *
	 * Already existing relations will be overwritten.
	 *
	 * @param array $embed
	 * @return $this
	 */
	public function embedFromArray(array $embed);

	/**
	 * Returns the API as an array.
	 *
	 * @return array
	 */
	public function build();

	/**
	 * Returns the API as a JSON string.
	 *
	 * @return string
	 */
	public function __toString();

}
