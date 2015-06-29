<?php namespace Jarischaefer\HalApi;

use App;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use Jarischaefer\HalApi\Helpers\Checks;
use Jarischaefer\HalApi\Routing\RouteHelper;
use RuntimeException;

/**
 * Class HalApi
 * @package Jarischaefer\HalApi
 */
class HalApiElement implements HalApiContract
{

	/**
	 * Keys which cannot be added directly to the API via the add() method.
	 *
	 * @var array
	 */
	private static $reservedApiKeys = ['data', 'meta', '_links', '_embedded'];
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

	public function __construct(HalLink $self = null, HalLink $parent = null)
	{
		$this->self($self)->parent($parent);
	}

	public static function make(HalLink $self = null, HalLink $parent = null)
	{
		return new static($self, $parent);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setAutoSubordinateRoutes($flag)
	{
		$this->autoSubordinateRoutes = (bool)$flag;
	}

	/**
	 * {@inheritdoc}
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
	 * {@inheritdoc}
	 */
	public function self(HalLink $self)
	{
		return $this->link(self::SELF, $self);
	}

	/**
	 * {@inheritdoc}
	 */
	public function parent(HalLink $parent)
	{
		return $this->link(self::PARENT, $parent);
	}

	/**
	 * {@inheritdoc}
	 */
	public function meta($key, $value)
	{
		$this->meta[$key] = $value;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function metaFromArray(array $meta)
	{
		$this->meta = array_merge_recursive($meta, $this->meta);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function data($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function dataFromArray(array $data)
	{
		$this->data = array_merge_recursive($data, $this->data);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function link($relation, HalLink $link)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string, got: ' . gettype($relation));
		}

		$this->links[$relation] = $link;

		return $this;
	}

	/**
	 * {@inheritdoc}
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
	 * {@inheritdoc}
	 */
	public function embed($relation, HalApiContract $api)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string');
		}

		$this->embedded[$relation][] = $api;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function embedFromArray(array $embed)
	{
		if (empty($embed)) {
			return $this;
		}

		foreach ($embed as $relation => $items) {
			Checks::arrayType($items, HalApiContract::class);

			foreach ($items as $item) {
				$this->embed($relation, $item);
			}
		}

		return $this;
	}

	private function addSubordinateRoutes(HalLink $halLink)
	{
		$subordinateRoutes = RouteHelper::subordinates($halLink->getRoute());

		/* @var Route $subRoute */
		foreach ($subordinateRoutes as $subRoute) {
			if (!self::isValidRoute($halLink->getRoute())) {
				continue;
			}

			/* @var HalApiController $class */
			list($class, $method) = explode('@', $subRoute->getActionName());
			$this->link($class::getRelation($method), HalLink::make($subRoute, $halLink->getParameters()));
		}
	}

	private static function isValidRoute(Route $route)
	{
		$actionName = $route->getActionName();

		// valid routes are backed by a controller (e.g. App\Http\Controllers\MyController@doSomething)
		if (!str_contains($actionName, '@')) {
			return false;
		}

		$class = explode('@', $actionName)[0];

		// only add a link if this class is its controller's parent
		if (!is_subclass_of($class, HalApiController::class)) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function build()
	{
		$build = $this->root;

		if ($this->autoSubordinateRoutes) {
			if (!array_key_exists(self::SELF, $this->links)) {
				throw new RuntimeException('relation for self is not defined, cannot add subordinate routes');
			}

			/* @var HalLink $self */
			$self = $this->links[self::SELF];
			$this->addSubordinateRoutes($self);
		}

		if (!empty($this->meta)) {
			$build['meta'] = $this->meta;
		}
		if (!empty($this->data)) {
			$build['data'] = $this->data;
		}

		/* @var HalLink $link */
		foreach ($this->links as $relation => $link) {
			$build['_links'][$relation] = $link->build();
		}

		$build['_embedded'] = [];

		foreach ($this->embedded as $relation => $embedded) {
			/* @var HalApiContract $item */
			foreach ($embedded as $item) {
				$build['_embedded'][$relation][] = $item->build();
			}
		}

		return $build;
	}

	/**
	 * {@inheritdoc}
	 */
	public function __toString()
	{
		return json_encode($this->build());
	}

	/**
	 * {@inheritdoc}
	 */
	public function toArray()
	{
		return $this->build();
	}

}
