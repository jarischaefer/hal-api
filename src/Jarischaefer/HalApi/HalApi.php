<?php namespace Jarischaefer\HalApi;

use App;
use InvalidArgumentException;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

/**
 * Class HalApi
 * @package Jarischaefer\HalApi
 */
class HalApi implements HalApiContract
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
		foreach ($meta as $key => $value) {
			$this->meta($key, $value);
		}

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
	public function item(Manager $manager, Item $item)
	{
		$transformed = $manager->createData($item)->toArray();

		// data is optional, so we check its existence
		if (array_key_exists('data', $transformed)) {
			$this->dataFromArray($transformed['data']);
		}

		// meta is optional, so we check its existence
		if (array_key_exists('meta', $transformed)) {
			$this->metaFromArray($transformed['meta']);
		}

		$this->links($transformed['_links']);
		$this->embedFromArray($transformed['_embedded']);

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

		$this->links[$relation] = $link->toArray();

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
	public function embed($relation, HalApiContract $apiService)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string');
		}

		$this->embedded[$relation][] = $apiService->build();

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

		foreach ($embed as $relation => $item) {
			if ($item instanceof HalApiContract) {
				$this->embed($relation, $item);
			} else {
				$this->embedded[$relation] = $item;
			}
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function embedCollection($relation, Manager $manager, Collection $collection)
	{
		if (!is_string($relation)) {
			throw new InvalidArgumentException('relation must be a string');
		}

		$transformed = $manager->createData($collection)->toArray();

		foreach ($transformed['data'] as $data) {
			/* @var HalApiContract $api */
			$api = App::make(HalApiContract::class);

			if (array_key_exists('data', $data)) {
				$api->dataFromArray($data['data']);
			}

			$api->links($data['_links']);
			$api->embedFromArray($data['_embedded']);

			$this->embedded[$relation][] = $api->build();
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function build()
	{
		$build = $this->root;

		if (!empty($this->meta)) {
			$build['meta'] = $this->meta;
		}
		if (!empty($this->data)) {
			$build['data'] = $this->data;
		}

		$build['_links'] = $this->links;
		$build['_embedded'] = $this->embedded;

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
