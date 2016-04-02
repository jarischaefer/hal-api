<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
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
	 * @inheritdoc
	 */
	public static function getCache(CacheFactory $cacheFactory): HalApiCache
	{
		return $cacheFactory->create(self::CACHE_GLOBAL_PREFIX . '_' . static::class, static::CACHE_MINUTES);
	}

	/**
	 * @inheritdoc
	 */
	public static function getRelatedCaches(CacheFactory $cacheFactory): array
	{
		return [];
	}

	/**
	 * @inheritdoc
	 */
	public static function getRelation(string $action = null): string
	{
		$relation = static::getRelationName();

		return $action ? $relation . '.' . $action : $relation;
	}

	/**
	 * @inheritdoc
	 */
	public static function actionName(string $methodName): string
	{
		return static::class . RouteHelper::ACTION_NAME_DELIMITER . $methodName;
	}

	/**
	 * @inheritdoc
	 */
	public static function action(HalApiUrlGenerator $urlGenerator, string $methodName, $parameters = []): string
	{
		$parameters = is_array($parameters) ? $parameters : [$parameters];

		return $urlGenerator->action(self::actionName($methodName), $parameters);
	}

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
	 * @param HalApiControllerParameters $parameters
	 */
	public function __construct(HalApiControllerParameters $parameters)
	{
		$this->linkFactory = $parameters->getLinkFactory();
		$this->representationFactory = $parameters->getRepresentationFactory();
		$this->routeHelper = $parameters->getRouteHelper();
		$this->responseFactory = $parameters->getResponseFactory();
	}

	/**
	 * @param HalApiRequestParameters $parameters
	 * @return HalApiRepresentation
	 */
	protected function createResponse(HalApiRequestParameters $parameters): HalApiRepresentation
	{
		return $this->representationFactory->create($parameters->getSelf(), $parameters->getParent());
	}

}
