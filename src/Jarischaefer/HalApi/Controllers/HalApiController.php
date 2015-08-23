<?php namespace Jarischaefer\HalApi\Controllers;

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
	const CACHE_GLOBAL_PREFIX = HalApiController::class . '_cache';
	/**
	 * The TTL for managed cache entries.
	 */
	const CACHE_MINUTES = 60;

	/**
	 * @var Application
	 */
	protected $app;
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
	 * @param Application $app
	 * @param Request $request
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param RouteHelper $routeHelper
	 * @param ResponseFactory $responseFactory
	 */
	public function __construct(Application $app, Request $request, LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, ResponseFactory $responseFactory)
	{
		$this->app = $app;
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
	public static function getCache(CacheFactory $cacheFactory)
	{
		return $cacheFactory->create(self::CACHE_GLOBAL_PREFIX . '_' . static::class, self::CACHE_MINUTES);
	}

	/**
	 * @inheritdoc
	 */
	public static function getRelatedCaches(CacheFactory $cacheFactory)
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
	 * @inheritdoc
	 */
	public static function actionName($methodName)
	{
		return static::class . '@' . $methodName;
	}

	/**
	 * @inheritdoc
	 */
	public static function action(UrlGenerator $urlGenerator, $methodName, $parameters = [])
	{
		$parameters = is_array($parameters) ? $parameters : [$parameters];

		return $urlGenerator->action(self::actionName($methodName), $parameters);
	}

}
