<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
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
	 * @var Application
	 */
	private $application;
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
	 * @var Request
	 */
	private $request;

	public function __construct(Application $application, LinkFactory $linkFactory, RepresentationFactory $representationFactory, ResponseFactory $responseFactory, RouteHelper $routeHelper, Request $request) {
		$this->application = $application;
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->responseFactory = $responseFactory;
		$this->routeHelper = $routeHelper;
		$this->request = $request;
	}

	/**
	 * @return Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return LinkFactory
	 */
	public function getLinkFactory()
	{
		return $this->linkFactory;
	}

	/**
	 * @return RepresentationFactory
	 */
	public function getRepresentationFactory()
	{
		return $this->representationFactory;
	}

	/**
	 * @return ResponseFactory
	 */
	public function getResponseFactory()
	{
		return $this->responseFactory;
	}

	/**
	 * @return RouteHelper
	 */
	public function getRouteHelper()
	{
		return $this->routeHelper;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

}