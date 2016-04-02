<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;

/**
 * Class HalApiUrlGenerator
 * @package Jarischaefer\HalApi\Routing
 */
class HalApiUrlGenerator extends UrlGenerator
{

	/**
	 * @param Router $router
	 * @param Request $request
	 */
	public function __construct(Router $router, Request $request)
	{
		parent::__construct($router->getRoutes(), $request);
	}

	/**
	 * @inheritdoc
	 */
	protected function toRoute($route, $parameters, $absolute)
	{
		$parameters = $this->formatParameters($parameters);
		$domain = $this->getRouteDomain($route, $parameters);
		$root = $this->replaceRoot($route, $domain, $parameters);

		$replaced = $this->replaceRouteParameters($route->uri(), $parameters);
		$trimmed = $this->trimUrl($root, $replaced);
		$uri = strtr(urlencode($this->addQueryString($trimmed, $parameters)), $this->dontEncode);

		return $absolute ? $uri : '/' . ltrim(str_replace($root, '', $uri), '/');
	}

}
