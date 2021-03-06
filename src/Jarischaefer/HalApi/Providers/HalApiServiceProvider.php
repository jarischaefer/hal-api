<?php namespace Jarischaefer\HalApi\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\CacheFactoryImpl;
use Jarischaefer\HalApi\Middleware\HalApiCacheMiddleware;
use Jarischaefer\HalApi\Middleware\HalApiETagMiddleware;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Representations\RepresentationFactoryImpl;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Routing\LinkFactoryImpl;

/**
 * Class HalApiServiceProvider
 * @package Jarischaefer\HalApi\Providers
 */
class HalApiServiceProvider extends ServiceProvider
{

	/**
	 * Base path in the vendor folder.
	 */
	const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
	/**
	 * List of files for Laravel's compiled.php.
	 */
	const COMPILES = [
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'CacheFactory.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'CacheFactoryImpl.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'HalApiCache.php',
		self::BASE_PATH . 'Caching' . DIRECTORY_SEPARATOR . 'HalApiCacheImpl.php',

		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiController.php',
		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiControllerParameters.php',
		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiRequestParameters.php',
		self::BASE_PATH . 'Controllers' . DIRECTORY_SEPARATOR . 'HalApiResourceController.php',

		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'ResourceRoute.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'RouteHelper.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'RouteHelperConstants.php',
		self::BASE_PATH . 'Helpers' . DIRECTORY_SEPARATOR . 'SafeIndexArray.php',

		self::BASE_PATH . 'Middleware' . DIRECTORY_SEPARATOR . 'HalApiCacheMiddleware.php',
		self::BASE_PATH . 'Middleware' . DIRECTORY_SEPARATOR . 'HalApiETagMiddleware.php',

		self::BASE_PATH . 'Repositories' . DIRECTORY_SEPARATOR . 'HalApiEloquentRepository.php',
		self::BASE_PATH . 'Repositories' . DIRECTORY_SEPARATOR . 'HalApiEloquentSearchRepository.php',

		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiRepresentation.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiRepresentationImpl.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiPaginatedRepresentation.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'HalApiPaginatedRepresentationImpl.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'RepresentationFactory.php',
		self::BASE_PATH . 'Representations' . DIRECTORY_SEPARATOR . 'RepresentationFactoryImpl.php',

		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiLink.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiLinkImpl.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'HalApiUrlGenerator.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'LinkFactory.php',
		self::BASE_PATH . 'Routing' . DIRECTORY_SEPARATOR . 'LinkFactoryImpl.php',

		self::BASE_PATH . 'Transformers' . DIRECTORY_SEPARATOR . 'HalApiTransformer.php',
	];

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * @inheritdoc
	 */
	public static function compiles()
	{
		return self::COMPILES;
	}

	/**
	 * @param Router $router
	 */
	public function boot(Router $router)
	{
		$router->aliasMiddleware(HalApiETagMiddleware::NAME, HalApiETagMiddleware::class);
		$router->aliasMiddleware(HalApiCacheMiddleware::NAME, HalApiCacheMiddleware::class);

		$this->app->singleton(RouteHelper::class);
		$this->app->singleton(HalApiUrlGenerator::class);
		$this->app->singleton(LinkFactory::class, LinkFactoryImpl::class);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(CacheFactory::class, CacheFactoryImpl::class);
		$this->app->singleton(RepresentationFactory::class, RepresentationFactoryImpl::class);
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
