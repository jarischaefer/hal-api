<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;

class LinkFactoryImpl implements LinkFactory
{

	/**
	 * @var UrlGenerator
	 */
	private $urlGenerator;

	public function __construct(UrlGenerator $urlGenerator)
	{
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param Route $route
	 * @param array $parameters
	 * @param string $queryString
	 * @return HalApiLink
	 */
	public function create(Route $route, $parameters = [], $queryString = '')
	{
		return new HalApiLinkImpl($this->urlGenerator, $route, $parameters, $queryString);
	}

}