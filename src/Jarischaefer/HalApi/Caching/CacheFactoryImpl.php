<?php namespace Jarischaefer\HalApi\Caching;

use Illuminate\Contracts\Cache\Repository;

class CacheFactoryImpl implements CacheFactory
{

	/**
	 * {@inheritdoc}
	 */
	public function create(Repository $repository, $cacheKey, $cacheMinutes)
	{
		return new HalApiCacheSimple($repository, $cacheKey, $cacheMinutes);
	}

}
