<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Routing\Route;

/**
 * Class LinkFactoryImpl
 * @package Jarischaefer\HalApi\Routing
 */
class LinkFactoryImpl implements LinkFactory
{

	/**
	 * @var HalApiUrlGenerator
	 */
	private $urlGenerator;

	/**
	 * @param HalApiUrlGenerator $urlGenerator
	 */
	public function __construct(HalApiUrlGenerator $urlGenerator)
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
