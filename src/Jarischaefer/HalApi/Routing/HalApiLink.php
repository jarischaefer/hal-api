<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Routing\Route;

/**
 * Class HalApiLinkImpl
 * @package Jarischaefer\HalApi\Routing
 */
interface HalApiLink
{

	/**
	 * @return Route
	 */
	public function getRoute();

	/**
	 * @return array
	 */
	public function getParameters();

	/**
	 * @param bool $encoded
	 * @return string
	 */
	public function getLink($encoded = false);

	/**
	 * @return bool
	 */
	public function isTemplated();

	/**
	 * @return string
	 */
	public function getQueryString();

	/**
	 * Returns the link's array representation.
	 *
	 * @return array
	 */
	public function build();

}
