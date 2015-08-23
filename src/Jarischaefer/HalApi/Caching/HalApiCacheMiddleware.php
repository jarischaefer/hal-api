<?php namespace Jarischaefer\HalApi\Caching;

use App;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Controllers\HalApiController;

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
	 * @var Application
	 */
	private $application;
	/**
	 * @var Repository
	 */
	private $config;

	/**
	 * @param Application $application
	 * @param Repository $config
	 */
	public function __construct(Application $application, Repository $config)
	{
		$this->application = $application;
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

		if (!is_subclass_of($class, HalApiController::class)) {
			return $next($request);
		}

		/** @var HalApiController $class */
		$cache = $class::getCache($this->application);

		if ($request->isMethodSafe()) {
			$key = $this->generateKey($cache, $request);

			return $cache->persist($key, function () use ($next, $request) {
				return $next($request);
			});
		}

		$cache->purge();
		$relatedCaches = $class::getRelatedCaches($this->application);

		/** @var HalApiCacheContract $relatedCache */
		foreach ($relatedCaches as $relatedCache) {
			$relatedCache->purge();
		}

		return $next($request);
	}

	/**
	 * @param HalApiCacheContract $cache
	 * @param Request $request
	 * @return string
	 */
	private function generateKey(HalApiCacheContract $cache, Request $request)
	{
		$method = $request->getMethod();
		$uri = $request->getUri();

		return $cache->key($method, $uri);
	}

}
