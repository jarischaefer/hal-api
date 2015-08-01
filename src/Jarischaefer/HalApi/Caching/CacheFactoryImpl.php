<?php namespace Jarischaefer\HalApi\Caching;

use Illuminate\Contracts\Cache\Repository;

/**
 * Class CacheFactoryImpl
 * @package Jarischaefer\HalApi\Caching
 */
class CacheFactoryImpl implements CacheFactory
{

	/**
	 * @inheritdoc
	 */
	public function create(Repository $repository, $cacheKey, $cacheMinutes)
	{
		return new HalApiCache($repository, $cacheKey, $cacheMinutes);
	}

}
