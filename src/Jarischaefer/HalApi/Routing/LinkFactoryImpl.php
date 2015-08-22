<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;

/**
 * Class LinkFactoryImpl
 * @package Jarischaefer\HalApi\Routing
 */
class LinkFactoryImpl implements LinkFactory
{

	/**
	 * @var UrlGenerator
	 */
	private $urlGenerator;

	/**
	 * @param UrlGenerator $urlGenerator
	 */
	public function __construct(UrlGenerator $urlGenerator)
	{
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @inheritdoc
	 */
	public function create(Route $route, $parameters = [], $queryString = '')
	{
		return new HalApiLinkImpl($this->urlGenerator, $route, $parameters, $queryString);
	}

}
