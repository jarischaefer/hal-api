<?php namespace Jarischaefer\HalApi\Routing;

use Illuminate\Routing\Route;

/**
 * Interface HalApiLink
 * @package Jarischaefer\HalApi\Routing
 */
interface HalApiLink
{

	/**
	 * @return Route
	 */
	public function getRoute(): Route;

	/**
	 * @return array
	 */
	public function getParameters(): array;

	/**
	 * @param bool $encoded
	 * @return string
	 */
	public function getLink($encoded = false): string;

	/**
	 * @return bool
	 */
	public function isTemplated(): bool;

	/**
	 * @return string
	 */
	public function getQueryString(): string;

	/**
	 * Returns the link's array representation.
	 *
	 * @return array
	 */
	public function build(): array;

}
