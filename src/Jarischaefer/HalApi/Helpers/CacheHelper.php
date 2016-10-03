<?php namespace Jarischaefer\HalApi\Helpers;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;

final class CacheHelper
{

	/**
	 * This method registers a cache-purging callback for
	 * a given model's modifying events (created, updated, deleted).
	 * You should call this method from a Service Provider's boot method.
	 *
	 * @param CacheFactory $cacheFactory
	 * @param string $model
	 * @param string $controller
	 */
	public static function registerPurgeCallbacks(CacheFactory $cacheFactory, string $model, string $controller)
	{
		if (!is_subclass_of($model, Model::class)) {
			throw new InvalidArgumentException('Model class must be a subclass of ' . Model::class . ', but was: ' . $model);
		}

		if (!is_subclass_of($controller, HalApiControllerContract::class)) {
			throw new InvalidArgumentException('Controller class must be a subclass of ' . HalApiControllerContract::class . ', but was: ' . $controller);
		}

		/** @var Model $model */
		/** @var HalApiControllerContract $controller */

		$callback = function () use ($cacheFactory, $controller) {
			$controller::getCache($cacheFactory)->purge();

			foreach ($controller::getRelatedCaches($cacheFactory) as $cache) {
				$cache->purge();
			}
		};

		$model::created($callback);
		$model::updated($callback);
		$model::deleted($callback);
	}

	/**
	 * No instances
	 */
	private function __construct()
	{
	}

}
