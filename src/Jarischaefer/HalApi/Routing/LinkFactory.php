<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Routing\Route;

/**
 * Interface LinkFactory
 * @package Jarischaefer\HalApi\Routing
 */
interface LinkFactory
{

	/**
	 * @param Route $route
	 * @param array $parameters
	 * @param string $queryString
	 * @return HalApiLink
	 */
	public function create(Route $route, $parameters = [], $queryString = '');

}
