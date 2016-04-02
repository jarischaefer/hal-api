<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class HalApiControllerParameters
 * @package Jarischaefer\HalApi\Controllers
 */
class HalApiControllerParameters
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
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param ResponseFactory $responseFactory
	 * @param RouteHelper $routeHelper
	 */
	public function __construct(LinkFactory $linkFactory, RepresentationFactory $representationFactory, ResponseFactory $responseFactory, RouteHelper $routeHelper)
	{
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->responseFactory = $responseFactory;
		$this->routeHelper = $routeHelper;
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

}
