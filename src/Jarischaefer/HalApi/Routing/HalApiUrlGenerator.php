<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteUrlGenerator;
use Illuminate\Routing\UrlGenerator;

/**
 * Class HalApiUrlGenerator
 * @package Jarischaefer\HalApi\Routing
 */
class HalApiUrlGenerator extends UrlGenerator
{

	/**
	 * @var RouteUrlGenerator
	 */
	private $urlGenerator;

	/**
	 * @param Router $router
	 * @param Request $request
	 */
	public function __construct(Router $router, Request $request)
	{
		parent::__construct($router->getRoutes(), $request);

		$this->urlGenerator = new class($this, $this->request) extends RouteUrlGenerator {

			/**
			 * @param HalApiUrlGenerator $url
			 * @param Request $request
			 */
			public function __construct(HalApiUrlGenerator $url, Request $request)
			{
				parent::__construct($url, $request);
			}

			/**
			 * @return HalApiUrlGenerator
			 */
			private function urlGenerator(): HalApiUrlGenerator
			{
				return $this->url;
			}

			/**
			 * @inheritdoc
			 */
			public function to($route, $parameters = [], $absolute = false)
			{
				$domain = $this->getRouteDomain($route, $parameters);
				$url = $this->urlGenerator()->format(
					$root = $this->replaceRootParameters($route, $domain, $parameters),
					$this->replaceRouteParameters($route->uri, $parameters)
				);
				$uri = strtr(rawurlencode($this->addQueryString($url, $parameters)), $this->dontEncode);

				return $absolute ? $uri : '/' . ltrim(str_replace($root, '', $uri), '/');
			}

		};
	}

	/**
	 * @inheritdoc
	 */
	protected function routeUrl()
	{
		return $this->urlGenerator;
	}

}
