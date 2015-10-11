<?php namespace Jarischaefer\HalApi\Transformers;

use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;

/**
 * Interface HalApiTransformerContract
 * @package Jarischaefer\HalApi\Transformers
 */
interface HalApiTransformerContract
{

	/**
	 * Transforms the model into a Hal response. This includes the model's data and all its relations and embedded data.
	 *
	 * @param Model $model
	 * @return HalApiRepresentation
	 */
	public function item(Model $model);

	/**
	 * Transforms multiple models into Hal responses. This includes the model's data and all its relations and embedded data.
	 *
	 * @param array $collection
	 * @return HalApiRepresentation[]
	 */
	public function collection(array $collection);

	/**
	 * Performs a raw transformation returning a key-value array of the model's attributes.
     * This also allows hiding certain attributes (e.g. passwords) from responses.
	 *
	 * @param Model $model
	 * @return array
	 */
	public function transform(Model $model);

}
