<?php namespace Jarischaefer\HalApi;

use Illuminate\Routing\Route;

/**
 * Class HalLink
 */
class HalLink
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
	private $isTemplated;
	/**
	 * @var string
	 */
	private $queryString;
	/**
	 * @var string
	 */
	private $originalQueryString;

	/**
	 * @param Route $route
	 * @param array $parameters
	 * @param string $queryString
	 * @param bool $keepOriginalQueryString
	 */
	public function __construct(Route $route, $parameters = [], $queryString = '', $keepOriginalQueryString = false)
	{
		$this->route = $route;
		$this->parameters = is_array($parameters) ? $parameters : [$parameters];
		$this->isTemplated = count($this->route->parameterNames()) > 0 ? true : false;
		$this->queryString = $queryString;
		$this->originalQueryString = $keepOriginalQueryString ? \Request::getQueryString() : '';
		$this->link = $this->buildLink();
	}

	/**
	 * @param Route $route
	 * @param array $parameters
	 * @param string $queryString
	 * @param bool $keepOriginalQueryString
	 * @return HalLink
	 */
	public static function make(Route $route, $parameters = [], $queryString = '', $keepOriginalQueryString = false)
	{
		return new self($route, $parameters, $queryString, $keepOriginalQueryString);
	}

	/**
	 * @return string
	 */
	private function buildLink()
	{
		// if our link is templated (e.g. example.com/api/items/{id}), we will generate it using the action() method
		if ($this->isTemplated) {
			list($class, $method) = explode('@', $this->route->getActionName());
			$className = class_basename($class);
			$link = action($className . '@' . $method, $this->parameters);
		} else {
			$link = \URL::to($this->route->getUri());
		}

		return $link . $this->buildQueryString();
	}

	private function buildQueryString()
	{
		parse_str($this->queryString, $queryParameters);
		parse_str($this->originalQueryString, $originalParameters);

		foreach ($queryParameters as $key => $value) {
			$originalParameters[$key] = $value;
		}

		return empty($originalParameters) ? '' : '?' . http_build_query($originalParameters);
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
		return $this->isTemplated;
	}

	/**
	 * @return string
	 */
	public function getOriginalQueryString()
	{
		return $this->originalQueryString;
	}

	/**
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->queryString;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return json_encode($this->toArray());
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return [
			'href' => $this->getLink(),
			'templated' => $this->isTemplated,
		];
	}

}