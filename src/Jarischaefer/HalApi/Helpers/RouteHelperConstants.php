<?php namespace Jarischaefer\HalApi\Helpers;

/**
 * Interface RouteHelperConstants
 * @package Jarischaefer\HalApi\Helpers
 */
interface RouteHelperConstants
{

	/**
	 *
	 */
	const ALL = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY];
	/**
	 * Method name for GET requests that list all resources (possibly paginated).
	 */
	const INDEX = 'index';
	/**
	 * Method name for GET requests that request a specific resource.
	 */
	const SHOW = 'show';
	/**
	 * Method name for POST requests that create a new resource.
	 */
	const STORE = 'store';
	/**
	 * Method name for PUT and PATCH requests that either create or fully update (PUT)
	 * or partially update (PATCH) a resource.
	 */
	const UPDATE = 'update';
	/**
	 * Method name for DELETE requests that delete an existing resource.
	 */
	const DESTROY = 'destroy';
	/**
	 * Query parameter name used for pagination's current page.
	 */
	const PARAM_PAGE = 'page';
	/**
	 * Query parameter name used for pagination's item count per page.
	 */
	const PARAM_PER_PAGE = 'per_page';
	/**
	 * Query string for pagination purposes.
	 */
	const PAGINATION_QUERY_STRING = 'page={page}&per_page={per_page}';
	/**
	 * Delimiter for routes' action names.
	 *
	 */
	const ACTION_NAME_DELIMITER = '@';

}
