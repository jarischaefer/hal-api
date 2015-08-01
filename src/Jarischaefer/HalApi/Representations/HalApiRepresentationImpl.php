<?php namespace Jarischaefer\HalApi\Representations;

use App;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use Jarischaefer\HalApi\Controllers\HalApiController;
use Jarischaefer\HalApi\Helpers\Checks;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;
use RuntimeException;

/**
 * Class HalApiRepresentationImpl
 * @package Jarischaefer\HalApi\Representations
 */
class HalApiRepresentationImpl implements HalApiRepresentation
{

	/**
	 * Keys which cannot be added directly to the API via the add() method.
	 *
	 * @var array
	 */
	private static $reservedApiKeys = ['data', 'meta', '_links', '_embedded'];
	/**
	 * @var LinkFactory
	 */
	private $linkFactory;
	/**
	 * @var RouteHelper
	 */
	private $routeHelper;
	/**
	 * @var array
	 */
	private $root = [];
	/**
	 * @var array
	 */
	private $meta = [];
	/**
	 * @var array
	 */
	private $data = [];
	/**
	 * @var array
	 */
	private $links = [];
	/**
	 * @var array
	 */
	private $embedded = [];
	/**
	 * Flag which indicates if subordinate routes should be added to the response automatically.
	 *
	 * @var boolean
	 */
	private $autoSubordinateRoutes = true;

	/**
	 * @param LinkFactory $linkFactory
	 * @param RouteHelper $routeHelper
	 * @param HalApiLink $self
	 * @param HalApiLink $parent
	 */
	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper, HalApiLink $self, HalApiLink $parent)
	{
		$this->linkFactory = $linkFactory;
		$this->routeHelper = $routeHelper;
		$this->self($self)->parent($parent);
	}

	/**
	 * @inheritdoc
	 */
	public function setAutoSubordinateRoutes($flag)
	{
		$this->autoSubordinateRoutes = (bool)$flag;
	}

	/**
	 * @inheritdoc
	 */
	public function add($key, $value)
	{
		if (!is_string($key)) {
			throw new InvalidArgumentException('key must be a string');
		}
		if (in_array($key, self::$reservedApiKeys)) {
			throw new InvalidArgumentException('key is restricted.');
		}

		$this->root[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function self(HalApiLink $self)
	{
		return $this->link(self::SELF, $self);
	}

	/**
	 * @inheritdoc
	 */
	public function parent(HalApiLink $parent)
	{
		return $this->link(self::PARENT, $parent);
	}

	/**
	 * @inheritdoc
	 */
	public function meta($key, $value)
	{
		$this->meta[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function metaFromArray(array $meta)
	{
		$this->meta = array_merge_recursive($meta, $this->meta);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function data($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function dataFromArray(array $data)
	{
		$this->data = array_merge_recursive($data, $this->data);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function link($relation, HalApiLink $link)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string, got: ' . gettype($relation));
		}

		$this->links[$relation] = $link;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function links(array $links)
	{
		if (empty($links)) {
			return $this;
		}

		foreach ($links as $relation => $link) {
			$this->link($relation, $link);
		}

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function embedSingle($relation, HalApiRepresentation $api)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string');
		}

		$this->embedded[$relation] = $api;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function embedMulti($relation, HalApiRepresentation $api)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string');
		}

		$this->embedded[$relation][] = $api;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function embedFromArray(array $embed)
	{
		if (empty($embed)) {
			return $this;
		}

		foreach ($embed as $relation => $item) {
			if (is_array($item)) {
				Checks::arrayType($item, HalApiRepresentation::class);

				foreach ($item as $api) {
					$this->embedMulti($relation, $api);
				}
			} else {
				$this->embedSingle($relation, $item);
			}
		}

		return $this;
	}

	/**
	 * @param HalApiLink $link
     */
	private function addSubordinateRoutes(HalApiLink $link)
	{
		$subordinateRoutes = $this->routeHelper->subordinates($link->getRoute());

		/* @var Route $subRoute */
		foreach ($subordinateRoutes as $subRoute) {
			/* @var HalApiController $class */
			list($class, $method) = explode('@', $subRoute->getActionName());
			$this->link($class::getRelation($method), $this->linkFactory->create($subRoute, $link->getParameters()));
		}
	}

	/**
	 * @inheritdoc
	 */
	public function build()
	{
		$build = $this->root;

		if ($this->autoSubordinateRoutes) {
			if (!array_key_exists(self::SELF, $this->links)) {
				throw new RuntimeException('relation for self is not defined, cannot add subordinate routes');
			}

			/* @var HalApiLink $self */
			$self = $this->links[self::SELF];
			$this->addSubordinateRoutes($self);
		}

		if (!empty($this->meta)) {
			$build['meta'] = $this->meta;
		}
		if (!empty($this->data)) {
			$build['data'] = $this->data;
		}

		/* @var HalApiLink $link */
		foreach ($this->links as $relation => $link) {
			$build['_links'][$relation] = $link->build();
		}

		$build['_embedded'] = [];

		foreach ($this->embedded as $relation => $embedded) {
			/* @var HalApiRepresentation $item */
			if (is_array($embedded)) {
				foreach ($embedded as $item) {
					$build['_embedded'][$relation][] = $item->build();
				}
			} else {
				/** @var HalApiRepresentation $embedded */
				$build['_embedded'][$relation] = $embedded->build();
			}
		}

		return $build;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString()
	{
		return json_encode($this->build());
	}

}
