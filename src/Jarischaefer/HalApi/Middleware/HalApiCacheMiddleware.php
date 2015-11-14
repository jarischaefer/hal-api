<?php namespace Jarischaefer\HalApi\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;

/**
 * Class HalApiCacheMiddleware
 * @package Jarischaefer\HalApi\Caching
 */
class HalApiCacheMiddleware
{

	/**
	 *
	 */
	const NAME = 'hal-api.cache';

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory;
	/**
	 * @var Repository
	 */
	private $config;

	/**
	 * @param CacheFactory $cacheFactory
	 * @param Repository $config
	 */
	public function __construct(CacheFactory $cacheFactory, Repository $config)
	{
		$this->cacheFactory = $cacheFactory;
		$this->config = $config;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if ($this->config->get('app.debug', false)) {
			return $next($request);
		}

		if (!($request instanceof Request)) {
			return $next($request);
		}

		$route = $request->route();

		if (!($route instanceof Route)) {
			return $next($request);
		}

		$class = explode('@', $route->getActionName())[0];

		if (!is_subclass_of($class, HalApiControllerContract::class)) {
			return $next($request);
		}

		/** @var HalApiControllerContract $class */
		$cache = $class::getCache($this->cacheFactory);

		if ($request->isMethodSafe()) {
			$key = $this->generateKey($cache, $request);

			return $cache->persist($key, function () use ($next, $request) {
				return $next($request);
			});
		}

		$cache->purge();

		foreach ($class::getRelatedCaches($this->cacheFactory) as $relatedCache) {
			$relatedCache->purge();
		}

		return $next($request);
	}

	/**
	 * @param HalApiCache $cache
	 * @param Request $request
	 * @return string
	 */
	private function generateKey(HalApiCache $cache, Request $request)
	{
		$method = $request->getMethod();
		$uri = $request->getUri();

		return $cache->key($method, $uri);
	}

}
