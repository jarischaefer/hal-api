<?php namespace Jarischaefer\HalApi\Caching;

use Closure;

/**
 * Interface HalApiCache
 * @package Jarischaefer\HalApi\Caching
 */
interface HalApiCache
{

	/**
	 * @return array
	 */
	public function all(): array;

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool;

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function fetch(string $key);

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return HalApiCache
	 */
	public function put(string $key, $value): HalApiCache;

	/**
	 * @param string $key
	 * @return HalApiCache
	 */
	public function evict(string $key): HalApiCache;

	/**
	 * @return HalApiCache
	 */
	public function purge(): HalApiCache;

	/**
	 * @param string $key
	 * @param Closure $closure
	 * @return mixed
	 */
	public function persist(string $key, Closure $closure);

	/**
	 * @param string[] ...$fragments
	 * @return string
	 */
	public function key(string ...$fragments): string;

}
