<?php namespace Jarischaefer\HalApi\Caching;

/**
 * Interface CacheFactory
 * @package Jarischaefer\HalApi\Caching
 */
interface CacheFactory
{

	/**
	 * @param string $cacheKey
	 * @param int $cacheMinutes
	 * @return HalApiCache
	 */
	public function create(string $cacheKey, int $cacheMinutes): HalApiCache;

}
