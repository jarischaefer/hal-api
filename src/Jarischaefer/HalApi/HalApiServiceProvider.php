<?php namespace Jarischaefer\HalApi;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\CacheFactoryImpl;
use Jarischaefer\HalApi\Caching\HalApiCacheContract;
use Jarischaefer\HalApi\Caching\HalApiCacheSimple;
use Jarischaefer\HalApi\Caching\HalApiCachingMiddleware;
use Jarischaefer\HalApi\Caching\HalApiETagMiddleware;

class HalApiServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	public function boot(Router $router)
	{
		$router->middleware('hal-api.etag', HalApiETagMiddleware::class);
		$router->middleware('hal-api.cache', HalApiCachingMiddleware::class);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(HalApiContract::class, HalApiElement::class);
		$this->app->bind(CacheFactory::class, CacheFactoryImpl::class);
		$this->app->bind(HalApiCacheContract::class, HalApiCacheSimple::class);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
