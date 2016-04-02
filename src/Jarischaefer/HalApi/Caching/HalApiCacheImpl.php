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
	 * @param string $cacheKey
	 * @param int $cacheMinutes
	 */
	public function __construct(Repository $repository, string $cacheKey, int $cacheMinutes)
	{
		$this->repository = $repository;
		$this->cacheKey = $cacheKey;
		$this->cacheMinutes = $cacheMinutes;
	}

	/**
	 * @return Repository
	 */
	public function getRepository(): Repository
	{
		return $this->repository;
	}

	/**
	 * @return string
	 */
	public function getCacheKey(): string
	{
		return $this->cacheKey;
	}

	/**
	 * @return int
	 */
	public function getCacheMinutes(): int
	{
		return $this->cacheMinutes;
	}

	/**
	 * @inheritdoc
	 */
	public function all(): array
	{
		return $this->repository->get($this->cacheKey, []);
	}

	/**
	 * @inheritdoc
	 */
	public function has(string $key): bool
	{
		return array_key_exists($key, $this->all());
	}

	/**
	 * @inheritdoc
	 */
	public function fetch(string $key)
	{
		$cache = $this->all();

		return array_key_exists($key, $cache) ? $cache[$key] : null;
	}

	/**
	 * @inheritdoc
	 */
	public function put(string $key, $value): HalApiCache
	{
		$cache = $this->all();
		$cache[$key] = $value;
		$this->replace($cache);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function evict(string $key): HalApiCache
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
	public function purge(): HalApiCache
	{
		return $this->replace([]);
	}

	/**
	 * @inheritdoc
	 */
	private function replace($value): HalApiCache
	{
		$this->repository->put($this->cacheKey, $value, $this->cacheMinutes);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function persist(string $key, Closure $closure)
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
	public function key(string ...$fragments): string
	{
		return join('_', $fragments);
	}

}
