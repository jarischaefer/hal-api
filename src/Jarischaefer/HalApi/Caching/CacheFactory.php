<?php namespace Jarischaefer\HalApi\Caching;

use Illuminate\Contracts\Cache\Repository;

/**
 * Interface CacheFactory
 * @package Jarischaefer\HalApi\Caching
 */
interface CacheFactory
{

	/**
	 * @param Repository $repository
	 * @param string $cacheKey
	 * @param int $cacheMinutes
	 * @return HalApiCache
	 */
	public function create(Repository $repository, $cacheKey, $cacheMinutes);

}
