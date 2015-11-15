<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;

/**
 * Class HalApiLinkImpl
 * @package Jarischaefer\HalApi\Routing
 */
class HalApiLinkImpl implements HalApiLink
{

	/**
	 * @var Route
	 */
	private $route;
	/**
	 * @var array
	 */
	private $parameters;
	/**
	 * @var string
	 */
	private $link;
	/**
	 * @var bool
	 */
	private $templated;
	/**
	 * @var string
	 */
	private $queryString;

	/**
	 * @param UrlGenerator $urlGenerator
	 * @param Route $route
	 * @param array $parameters
	 * @param string $queryString
	 */
	public function __construct(UrlGenerator $urlGenerator, Route $route, $parameters = [], $queryString = '')
	{
		$this->route = $route;
		$this->parameters = is_array($parameters) ? $parameters : [$parameters];
		$this->queryString = self::createQueryString($queryString);
		$this->templated = self::evaluateTemplated($route, $urlGenerator, $queryString);
		$this->link = $urlGenerator->action($this->route->getActionName(), $this->templated ? $this->parameters : []);

		if (!empty($this->queryString)) {
			$this->link .= '?' . $this->queryString;
		}
	}

	/**
	 * @return Route
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param bool $encoded
	 * @return string
	 */
	public function getLink($encoded = false)
	{
		return $encoded ? $this->link : rawurldecode($this->link);
	}

	/**
	 * @return bool
	 */
	public function isTemplated()
	{
		return $this->templated;
	}

	/**
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->queryString;
	}

	/**
	 * Returns the link's array representation.
	 *
	 * @return array
	 */
	public function build()
	{
		return [
			'href' => $this->getLink(),
			'templated' => $this->templated,
		];
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return json_encode($this->build());
	}

	/**
	 * @param Route $route
	 * @param UrlGenerator $urlGenerator
	 * @param $queryString
	 * @return bool
	 */
	private static function evaluateTemplated(Route $route, UrlGenerator $urlGenerator, $queryString)
	{
		// Does the route have named parameters? http://example.com/users/{users}
		if (count($route->parameterNames())) {
			return true;
		}

		$url = rawurldecode($urlGenerator->action($route->getActionName()));

		// Does the route's URI already contain a query string? http://example.com/users?page={page}&per_page={per_page}
		if (preg_match('/\?.*=\{.*?\}/', $url)) {
			return true;
		}

		// Does the query string contain any parameters?
		if (preg_match('/\?.*=\{.*?\}/', $queryString)) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $queryString
	 * @return string
	 */
	private static function createQueryString($queryString)
	{
		return empty($queryString) ? '' : ($queryString[0] === '?' ? substr($queryString, 1) : $queryString);
	}

}
