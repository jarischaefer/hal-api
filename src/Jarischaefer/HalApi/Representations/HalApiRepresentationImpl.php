<?php namespace Jarischaefer\HalApi\Representations;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
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
	 * @var Gate
	 */
	private $gate;
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
	 * @var HalApiLink[]
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
	 * @param Gate $gate
	 * @param HalApiLink $self
	 * @param HalApiLink $parent
	 */
	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper, Gate $gate, HalApiLink $self, HalApiLink $parent)
	{
		$this->linkFactory = $linkFactory;
		$this->routeHelper = $routeHelper;
		$this->gate = $gate;

		$this->link(self::SELF, $self);
		$this->link(self::PARENT, $parent);
	}

	/**
	 * @inheritdoc
	 */
	public function setAutoSubordinateRoutes(bool $flag)
	{
		$this->autoSubordinateRoutes = $flag;
	}

	/**
	 * @inheritdoc
	 */
	public function add(string $key, $value): HalApiRepresentation
	{
		if (in_array($key, self::$reservedApiKeys)) {
			throw new InvalidArgumentException('key is restricted.');
		}

		$this->root[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function meta(string $key, $value): HalApiRepresentation
	{
		$this->meta[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function metaFromArray(array $meta): HalApiRepresentation
	{
		$this->meta = array_merge_recursive($meta, $this->meta);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function data(string $key, $value): HalApiRepresentation
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function dataFromArray(array $data): HalApiRepresentation
	{
		$this->data = array_merge_recursive($data, $this->data);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function link(string $relation, HalApiLink $link): HalApiRepresentation
	{
		$this->links[$relation] = $link;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function links(array $links): HalApiRepresentation
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
	public function embedSingle(string $relation, HalApiRepresentation $representation): HalApiRepresentation
	{
		$this->embedded[$relation] = $representation;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function embedMulti(string $relation, HalApiRepresentation $representation): HalApiRepresentation
	{
		$this->embedded[$relation][] = $representation;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function embedFromArray(array $embed): HalApiRepresentation
	{
		foreach ($embed as $relation => $item) {
			if (is_array($item)) {
				Checks::arrayType($item, HalApiRepresentation::class);

				foreach ($item as $representation) {
					$this->embedMulti($relation, $representation);
				}
			} else {
				$this->embedSingle($relation, $item);
			}
		}

		return $this;
	}

	/**
	 * @param Authenticatable $authenticatable
	 */
	private function addSubordinateRoutes(Authenticatable $authenticatable = null)
	{
		$self = $this->links[self::SELF];
		$parameters = $self->getParameters();
		$subordinateRoutes = $this->routeHelper->subordinates($self->getRoute());
		$gate = $authenticatable ? $this->gate->forUser($authenticatable) : null;

		foreach ($subordinateRoutes as $subRoute) {
			if ($gate) {
				$actionName = $subRoute->getActionName();

				if ($gate->has($actionName) && $gate->denies($actionName)) {
					continue;
				}
			}

			$relation = RouteHelper::relation($subRoute);
			$link = $this->linkFactory->create($subRoute, $parameters);
			$this->link($relation, $link);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function build(Authenticatable $authenticatable = null): array
	{
		$build = $this->root;

		if ($this->autoSubordinateRoutes) {
			if (!isset($this->links[self::SELF])) {
				throw new RuntimeException('relation for self is not defined, cannot add subordinate routes');
			}

			$this->addSubordinateRoutes($authenticatable);
		}

		if (!empty($this->meta)) {
			$build['meta'] = $this->meta;
		}
		if (!empty($this->data)) {
			$build['data'] = $this->data;
		}

		foreach ($this->links as $relation => $link) {
			$build['_links'][$relation] = $link->build();
		}

		$build['_embedded'] = [];

		foreach ($this->embedded as $relation => $embedded) {
			if (is_array($embedded)) {
				/** @var HalApiRepresentation $item */
				foreach ($embedded as $item) {
					$build['_embedded'][$relation][] = $item->build($authenticatable);
				}
			} else {
				/** @var HalApiRepresentation $embedded */
				$build['_embedded'][$relation] = $embedded->build($authenticatable);
			}
		}

		return $build;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString(): string
	{
		return json_encode($this->build());
	}

}
