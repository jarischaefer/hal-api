<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class HalApiControllerParameters
 * @package Jarischaefer\HalApi\Controllers
 */
final class HalApiControllerParameters
{

	/**
	 * @var LinkFactory
	 */
	private $linkFactory;
	/**
	 * @var RepresentationFactory
	 */
	private $representationFactory;
	/**
	 * @var ResponseFactory
	 */
	private $responseFactory;
	/**
	 * @var RouteHelper
	 */
	private $routeHelper;
	/**
	 * @var Gate
	 */
	private $gate;
	/**
	 * @var Guard
	 */
	private $guard;

	/**
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param ResponseFactory $responseFactory
	 * @param RouteHelper $routeHelper
	 * @param Gate $gate
	 * @param Guard $guard
	 */
	public function __construct(LinkFactory $linkFactory, RepresentationFactory $representationFactory, ResponseFactory $responseFactory, RouteHelper $routeHelper, Gate $gate, Guard $guard)
	{
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->responseFactory = $responseFactory;
		$this->routeHelper = $routeHelper;
		$this->gate = $gate;
		$this->guard = $guard;
	}

	/**
	 * @return LinkFactory
	 */
	public function getLinkFactory(): LinkFactory
	{
		return $this->linkFactory;
	}

	/**
	 * @return RepresentationFactory
	 */
	public function getRepresentationFactory(): RepresentationFactory
	{
		return $this->representationFactory;
	}

	/**
	 * @return ResponseFactory
	 */
	public function getResponseFactory(): ResponseFactory
	{
		return $this->responseFactory;
	}

	/**
	 * @return RouteHelper
	 */
	public function getRouteHelper(): RouteHelper
	{
		return $this->routeHelper;
	}

	/**
	 * @return Gate
	 */
	public function getGate(): Gate
	{
		return $this->gate;
	}

	/**
	 * @return Guard
	 */
	public function getGuard(): Guard
	{
		return $this->guard;
	}

}
