<?php namespace Jarischaefer\HalApi\Transformers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Helpers\Checks;
use Jarischaefer\HalApi\Routing\LinkFactory;
use ReflectionMethod;

/**
 * Class HalApiTransformer
 * @package Jarischaefer\HalApi\Transformers
 */
abstract class HalApiTransformer implements HalApiTransformerContract
{

	/**
	 * @var LinkFactory
	 */
	protected $linkFactory;
	/**
	 * @var RepresentationFactory
	 */
	protected $representationFactory;
	/**
	 * @var RouteHelper
	 */
	protected $routeHelper;
	/**
	 * @var Route
	 */
	protected $self;
	/**
	 * @var Route
	 */
	protected $parent;

	/**
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param RouteHelper $routeHelper
	 * @param Route $self
	 * @param Route $parent
	 */
	public function __construct(LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, Route $self, Route $parent)
	{
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->routeHelper = $routeHelper;
		$this->self = $self;
		$this->parent = $parent;
	}

	/**
	 * @inheritdoc
	 */
	public final function item(Model $model)
	{
		$self = $this->getSelf($model);
		$parent = $this->getParent($model);
		$data = $this->transform($model);
		$links = $this->getLinks($model);
		$embedded = $this->getEmbedded($model);

		return $this->representationFactory->create($self, $parent)
			->dataFromArray($data)
			->links($links)
			->embedFromArray($embedded);
	}

	/**
	 * @inheritdoc
	 */
	public function collection(array $collection)
	{
		$elements = [];

		foreach ($collection as $model) {
			$elements[] = $this->item($model);
		}

		return $elements;
	}

	/**
	 * @param Model $model
	 * @return HalApiLink
	 */
	protected function getSelf(Model $model)
	{
		return $this->linkFactory->create($this->self, $model->getKey());
	}

	/**
	 * @param Model $model
	 * @return HalApiLink
	 */
	protected function getParent(Model $model)
	{
		return $this->linkFactory->create($this->parent);
	}

	/**
	 * @param Model $model
	 * @return HalApiLink[]
	 */
	protected function getLinks(Model $model)
	{
		return [];
	}

	/**
	 * @param Model $model
	 * @return array
	 */
	protected function getEmbedded(Model $model)
	{
		return [];
	}

}
