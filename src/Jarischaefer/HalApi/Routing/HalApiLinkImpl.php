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
	 * @var UrlGenerator
	 */
	private $urlGenerator;
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
		$this->urlGenerator = $urlGenerator;
		$this->route = $route;
		$this->parameters = is_array($parameters) ? $parameters : [$parameters];
		$this->templated = count($this->route->parameterNames()) > 0 ? true : false;
		$this->queryString = $queryString;
		$this->link = $this->urlGenerator->action($this->route->getActionName(), $this->parameters) . $queryString;
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

}
