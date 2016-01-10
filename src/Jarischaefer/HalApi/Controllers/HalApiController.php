<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Helpers\SafeIndexArray;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class HalApiController
 * @package Jarischaefer\HalApi\Controllers
 */
abstract class HalApiController extends Controller implements HalApiControllerContract
{

	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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
	 * @param HalApiControllerParameters $parameters
	 */
	public function __construct(HalApiControllerParameters $parameters)
	{
		$this->app = $parameters->getApplication();
		$this->linkFactory = $parameters->getLinkFactory();
		$this->representationFactory = $parameters->getRepresentationFactory();
		$this->routeHelper = $parameters->getRouteHelper();
		$this->responseFactory = $parameters->getResponseFactory();
		$this->request = $parameters->getRequest();

		$this->parameters = new SafeIndexArray($this->request->input());
		$this->body = new SafeIndexArray($this->request->json()->all());
		/** @var Route $route */
		$route = $this->request->route();

		if ($route) {
			$routeParameters = $route->parameters();
			$this->self = $this->linkFactory->create($route, $routeParameters);
			$this->parent = $this->linkFactory->create($this->routeHelper->parent($route), $routeParameters);
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
	 * @return HalApiRepresentation
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
	public static function action(HalApiUrlGenerator $urlGenerator, $methodName, $parameters = [])
	{
		$parameters = is_array($parameters) ? $parameters : [$parameters];

		return $urlGenerator->action(self::actionName($methodName), $parameters);
	}

}
