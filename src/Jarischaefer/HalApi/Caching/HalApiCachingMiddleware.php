<?php namespace Jarischaefer\HalApi\Caching;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\HalApiController;

class HalApiCachingMiddleware
{

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$route = $request->route();

		if ($route instanceof Route) {
			$class = explode('@', $route->getActionName())[0];

			if (!is_subclass_of($class, HalApiController::class)) {
				return $next($request);
			}

			/** @var HalApiController $class */

			$cache = $class::getCache();
			$key = $this->generateKey($cache, $request);

			if ($request->isMethodSafe()) {
				return $cache->persist($key, function() use ($next, $request) {
					return $next($request);
				});
			} else {
				$response = $next($request);
				$cache->purge();
				$relatedCaches = $class::getRelatedCaches();

				foreach ($relatedCaches as $relatedCache) {
					$relatedCache->purge();
				}

				return $response;
			}
		}
	}

	private function generateKey(HalApiCacheContract $cache, Request $request)
	{
		$method = $request->getMethod();
		$uri = $request->getUri();

		return $cache->key($method, $uri);
	}

}
