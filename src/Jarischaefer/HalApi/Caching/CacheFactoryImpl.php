<?php namespace Jarischaefer\HalApi\Caching;

use Illuminate\Contracts\Cache\Repository;

/**
 * Class CacheFactoryImpl
 * @package Jarischaefer\HalApi\Caching
 */
class CacheFactoryImpl implements CacheFactory
{

	/**
	 * @var Repository
	 */
	private $repository;

	/**
	 * @param Repository $repository
	 */
	public function __construct(Repository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * @inheritdoc
	 */
	public function create($cacheKey, $cacheMinutes)
	{
		return new HalApiCacheImpl($this->repository, $cacheKey, $cacheMinutes);
	}

}
