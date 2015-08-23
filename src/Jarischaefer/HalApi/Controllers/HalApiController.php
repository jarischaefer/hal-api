<?php namespace Jarischaefer\HalApi\Controllers;

use App;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Representations\HalApiRepresentationImpl;
use Jarischaefer\HalApi\Helpers\SafeIndexArray;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;
use RuntimeException;

/**
 * Class HalApiController
 * @package Jarischaefer\HalApi\Controllers
 */
abstract class HalApiController extends Controller implements HalApiControllerContract
{

	use DispatchesJobs, ValidatesRequests;

	/**
	 * Global prefix for the managed cache.
	 */
	const CACHE_GLOBAL_PREFIX = HalApiResourceController::class . '_cache';
	/**
	 * The TTL for managed cache entries.
	 */
	const CACHE_MINUTES = 60;

	/**
	 * @var Application
	 */
	protected $application;
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var LinkFactory
	 */
	protected $linkFactory;
	/**
	 * @var RepresentationFactory
	 */
	protected $representationFactory;
	/**
	 * @var RouteHelper
	 */
	protected $routeHelper;
	/**
	 * @var ResponseFactory
	 */
	protected $responseFactory;
	/**
	 * @var SafeIndexArray
	 */
	protected $parameters;
	/**
	 * @var SafeIndexArray
	 */
	protected $body;
	/**
	 * @var HalApiLink
	 */
	protected $self;
	/**
	 * @var HalApiLink
	 */
	protected $parent;

	/**
	 * @param Application $application
	 * @param Request $request
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param RouteHelper $routeHelper
	 * @param ResponseFactory $responseFactory
	 */
	public function __construct(Application $application, Request $request, LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, ResponseFactory $responseFactory)
	{
		$this->application = $application;
		$this->request = $request;
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->routeHelper = $routeHelper;
		$this->responseFactory = $responseFactory;
		$this->parameters = new SafeIndexArray($request->input());
		$this->body = new SafeIndexArray($request->json()->all());
		/** @var Route $route */
		$route = $request->route();

		if ($route) {
			$routeParameters = $route->parameters();
			$this->self = $this->linkFactory->create($route, $routeParameters, $request->getQueryString());
			$this->parent = $this->linkFactory->create($routeHelper->parent($route), $routeParameters);
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function getCache(Application $application)
	{
		/** @var CacheFactory $cacheFactory */
		$cacheFactory = $application->make(CacheFactory::class);
		$cacheKey = self::CACHE_GLOBAL_PREFIX . '_' . static::class;

		return $cacheFactory->create($cacheKey, self::CACHE_MINUTES);
	}

	/**
	 * @inheritdoc
	 */
	public static function getRelatedCaches(Application $application)
	{
		return [];
	}

	/**
	 * @return HalApiRepresentationImpl
	 */
	protected function createResponse()
	{
		return $this->representationFactory->create($this->self, $this->parent);
	}

	/**
	 * Returns the action name (e.g. App\Http\Controllers\MyController@doSomething).
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function actionName($methodName)
	{
		return get_called_class() . '@' . $methodName;
	}

	/**
	 * Generates a URL for the given method name and parameters.
	 * If your routes.php contains an entry for /users linked to the
	 * index method and another entry for /users/{users} linked to the
	 * show method, then a call to this method would yield the following result:
	 *
	 * UsersController::action($urlGenerator, 'show', '99e31491-dd32-4e2c-b221-7deeb6cc4853')
	 *
	 * http://my.app.example.com/users/99e31491-dd32-4e2c-b221-7deeb6cc4853
	 *
	 * @param UrlGenerator $urlGenerator
	 * @param string $methodName
	 * @param array $parameters
	 * @return string
	 */
	public static function action(UrlGenerator $urlGenerator, $methodName, $parameters = [])
	{
		$class = get_called_class();

		if (!method_exists($class, $methodName)) {
			throw new RuntimeException('Method does not exist!');
		}

		$parameters = is_array($parameters) ? $parameters : [$parameters];

		return $urlGenerator->action($class . '@' . $methodName, $parameters);
	}

}
