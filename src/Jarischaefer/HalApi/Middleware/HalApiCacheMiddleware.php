<?php namespace Jarischaefer\HalApi\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;
use Jarischaefer\HalApi\Helpers\RouteHelper;

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
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (!self::isCacheable($request)) {
			return $next($request);
		}

		$actionName = $request->route()->getActionName();

		$class = explode(RouteHelper::ACTION_NAME_DELIMITER, $actionName)[0];
		/** @var HalApiControllerContract $class */
		$cache = $class::getCache($this->cacheFactory);
		$key = self::generateKey($cache, $request);

		return $cache->persist($key, function () use ($next, $request) {
			return $next($request);
		});
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	private function isCacheable(Request $request): bool
	{
		if (!$request->isMethodSafe() || $this->config->get('app.debug', false)) {
			return false;
		}

		$route = $request->route();

		return $route instanceof Route && RouteHelper::isValidActionName($route->getActionName());
	}

	/**
	 * @param HalApiCache $cache
	 * @param Request $request
	 * @return string
	 */
	private static function generateKey(HalApiCache $cache, Request $request): string
	{
		$method = $request->getMethod();
		$uri = $request->getUri();

		return $cache->key($method, $uri);
	}

}
