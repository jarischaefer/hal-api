<?php namespace Jarischaefer\HalApi\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\CacheFactoryImpl;
use Jarischaefer\HalApi\Caching\HalApiCacheContract;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Caching\HalApiCacheMiddleware;
use Jarischaefer\HalApi\Caching\HalApiETagMiddleware;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\HalApiRepresentationImpl;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Representations\RepresentationFactoryImpl;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;
use Jarischaefer\HalApi\Transformers\TransformerFactory;
use Jarischaefer\HalApi\Transformers\TransformerFactoryImpl;

/**
 * Class HalApiServiceProvider
 * @package Jarischaefer\HalApi\Providers
 */
class HalApiServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * @param Router $router
     */
	public function boot(Router $router)
	{
		$router->middleware(HalApiETagMiddleware::NAME, HalApiETagMiddleware::class);
		$router->middleware(HalApiCacheMiddleware::NAME, HalApiCacheMiddleware::class);
		$this->app->singleton(RouteHelper::class, function() use ($router) {
			return new RouteHelper($router);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(HalApiRepresentation::class, HalApiRepresentationImpl::class);
		$this->app->bind(CacheFactory::class, CacheFactoryImpl::class);
		$this->app->bind(HalApiCacheContract::class, HalApiCache::class);
		$this->app->bind(TransformerFactory::class, TransformerFactoryImpl::class);
		$this->app->bind(LinkFactory::class, LinkFactoryImpl::class);
		$this->app->bind(RepresentationFactory::class, RepresentationFactoryImpl::class);
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
