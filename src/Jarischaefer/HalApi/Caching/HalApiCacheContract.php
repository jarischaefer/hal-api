<?php namespace Jarischaefer\HalApi\Caching;

use Closure;

/**
 * Interface HalApiCacheContract
 * @package Jarischaefer\HalApi\Caching
 */
interface HalApiCacheContract
{

	/**
	 * @return array
	 */
	public function all();

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function fetch($key);

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return HalApiCacheContract
	 */
	public function put($key, $value);

	/**
	 * @param mixed $value
	 * @return HalApiCacheContract
	 */
	public function replace($value);

	/**
	 * @param string $key
	 * @return HalApiCacheContract
	 */
	public function evict($key);

	/**
	 * @return void
	 */
	public function purge();

	/**
	 * @param string $key
	 * @param Closure $closure
	 * @return mixed
	 */
	public function persist($key, Closure $closure);

	/**
	 * @param string ...$fragments
	 * @return string
	 */
	public function key(...$fragments);

}
