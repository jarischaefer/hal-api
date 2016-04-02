<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Http\Request;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Helpers\SafeIndexArray;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class HalApiRequestParameters
 * @package Jarischaefer\HalApi\Controllers
 */
class HalApiRequestParameters
{

	/**
	 * Query parameter name used for pagination's current page.
	 */
	const PAGINATION_URI_PAGE = 'page';
	/**
	 * Query parameter name used for pagination's item count per page.
	 */
	const PAGINATION_URI_PER_PAGE = 'per_page';
	/**
	 * Default number of items per pagination page. Used as a fallback if the value
	 * provided via configuration is invalid.
	 */
	const PAGINATION_DEFAULT_ITEMS_PER_PAGE = 10;

	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var HalApiLink
	 */
	private $self;
	/**
	 * @var HalApiLink
	 */
	private $parent;
	/**
	 * @var SafeIndexArray
	 */
	private $parameters;
	/**
	 * @var SafeIndexArray
	 */
	private $body;
	/**
	 * @var int
	 */
	private $page;
	/**
	 * @var int
	 */
	private $perPage;

	/**
	 * @param Request $request
	 * @return int
	 */
	private static function getPageFromRequest(Request $request): int
	{
		$page = $request->get(self::PAGINATION_URI_PAGE, 1);

		if (!is_numeric($page) || $page <= 0) {
			$page = 1;
		}

		return $page;
	}

	/**
	 * @param Request $request
	 * @return int
	 */
	private static function getPerPageFromRequest(Request $request): int
	{
		$perPage = $request->get(self::PAGINATION_URI_PER_PAGE, self::PAGINATION_DEFAULT_ITEMS_PER_PAGE);

		if (!is_numeric($perPage) || $perPage < 1) {
			$perPage = self::PAGINATION_DEFAULT_ITEMS_PER_PAGE;
		}

		return $perPage;
	}

	/**
	 * @param LinkFactory $linkFactory
	 * @param RouteHelper $routeHelper
	 * @param Request $request
	 */
	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper, Request $request)
	{
		$this->request = $request;
		$route = $request->route();
		$routeParameters = $route->parameters();
		$this->self = $linkFactory->create($route, $routeParameters);
		$this->parent = $linkFactory->create($routeHelper->parent($route), $routeParameters);
		$this->parameters = new SafeIndexArray($request->input());
		$this->body = new SafeIndexArray($request->json()->all());
		$this->page = self::getPageFromRequest($request);
		$this->perPage = self::getPerPageFromRequest($request);
	}

	/**
	 * @return Request
	 */
	public function getRequest(): Request
	{
		return $this->request;
	}

	/**
	 * @return HalApiLink
	 */
	public function getSelf(): HalApiLink
	{
		return $this->self;
	}

	/**
	 * @return HalApiLink
	 */
	public function getParent(): HalApiLink
	{
		return $this->parent;
	}

	/**
	 * @return SafeIndexArray
	 */
	public function getParameters(): SafeIndexArray
	{
		return $this->parameters;
	}

	/**
	 * @return SafeIndexArray
	 */
	public function getBody(): SafeIndexArray
	{
		return $this->body;
	}

	/**
	 * @return int
	 */
	public function getPage(): int
	{
		return $this->page;
	}

	/**
	 * @return int
	 */
	public function getPerPage(): int
	{
		return $this->perPage;
	}

}
