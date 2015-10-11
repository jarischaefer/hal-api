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
	 * @return HalApiCache
	 */
	public function put($key, $value);

	/**
	 * @param mixed $value
	 * @return HalApiCache
	 */
	public function replace($value);

	/**
	 * @param string $key
	 * @return HalApiCache
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
