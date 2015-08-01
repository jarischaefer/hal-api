<?php namespace Jarischaefer\HalApi\Caching;

use App;
use Closure;
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

	public function __construct(Application $application)
	{
		$this->application = $application;
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

		$response = $next($request);
		$cache->purge();
		$relatedCaches = $class::getRelatedCaches($this->application);

		/** @var HalApiCacheContract $relatedCache */
		foreach ($relatedCaches as $relatedCache) {
			$relatedCache->purge();
		}

		return $response;
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
