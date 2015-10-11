<?php namespace Jarischaefer\HalApi\Caching;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Class HalApiCacheImpl
 * @package Jarischaefer\HalApi\Caching
 */
class HalApiCacheImpl implements HalApiCache
{

	/**
	 * @var Repository
	 */
	private $repository;
	/**
	 * @var string
	 */
	private $cacheKey;
	/**
	 * @var int
	 */
	private $cacheMinutes;

	/**
	 * @param Repository $repository
	 * @param $cacheKey
	 * @param $cacheMinutes
	 */
	public function __construct(Repository $repository, $cacheKey, $cacheMinutes)
	{
		$this->repository = $repository;
		$this->cacheKey = (string)$cacheKey;
		$this->cacheMinutes = (int)$cacheMinutes;
	}

	/**
	 * @return Repository
	 */
	public function getRepository()
	{
		return $this->repository;
	}

	/**
	 * @return string
	 */
	public function getCacheKey()
	{
		return $this->cacheKey;
	}

	/**
	 * @return int
	 */
	public function getCacheMinutes()
	{
		return $this->cacheMinutes;
	}

	/**
	 * @inheritdoc
	 */
	public function all()
	{
		return $this->repository->get($this->cacheKey, []);
	}

	/**
	 * @inheritdoc
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->all());
	}

	/**
	 * @inheritdoc
	 */
	public function fetch($key)
	{
		$cache = $this->all();

		return array_key_exists($key, $cache) ? $cache[$key] : null;
	}

	/**
	 * @inheritdoc
	 */
	public function put($key, $value)
	{
		$cache = $this->all();
		$cache[$key] = $value;
		$this->replace($cache);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function replace($value)
	{
		$this->repository->put($this->cacheKey, $value, $this->cacheMinutes);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function evict($key)
	{
		$cache = $this->all();

		if (array_key_exists($key, $cache)) {
			unset($cache[$key]);
			$this->replace($cache);
		}

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function purge()
	{
		$this->replace([]);
	}

	/**
	 * @inheritdoc
	 */
	public function persist($key, Closure $closure)
	{
		$cached = $this->fetch($key);

		if ($cached === null) {
			$cached = $closure();
			$this->put($key, $cached);
		}

		return $cached;
	}

	/**
	 * @inheritdoc
	 */
	public function key(...$fragments)
	{
		return join('_', $fragments);
	}

}
