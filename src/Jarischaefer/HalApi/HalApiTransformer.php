<?php namespace Jarischaefer\HalApi;

use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Helpers\Checks;

/**
 * Class HalTransformer
 * @package Jarischaefer\HalApi\Transformers
 */
abstract class HalApiTransformer
{

	/**
	 * Transforms the model into a Hal response. This includes the model's data and all its relations and embedded data.
	 *
	 * @param Model $model
	 * @return HalApiElement
	 */
	public final function item(Model $model)
	{
		return HalApiElement::make($this->getSelf($model), $this->getParent($model))
			->dataFromArray($this->transform($model))
			->links($this->getLinks($model))
			->embedFromArray($this->getEmbedded($model));
	}

	/**
	 * Transforms multiple models into Hal responses. This includes the model's data and all its relations and embedded data.
	 *
	 * @param array $collection
	 * @return array
	 */
	public function collection(array $collection)
	{
		Checks::arrayType($collection, Model::class);

		$elements = [];

		foreach ($collection as $model) {
			$elements[] = $this->item($model);
		}

		return $elements;
	}

	abstract public function transform(Model $model);

	/**
	 * @param Model $model
	 * @return HalLink
	 */
	abstract protected function getSelf(Model $model);

	/**
	 * @param Model $model
	 * @return HalLink
	 */
	abstract protected function getParent(Model $model);

	abstract protected function getLinks(Model $model);

	abstract protected function getEmbedded(Model $model);

}
