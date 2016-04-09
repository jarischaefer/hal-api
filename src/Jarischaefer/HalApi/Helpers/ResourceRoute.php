<?php namespace Jarischaefer\HalApi\Helpers;

/**
 * Class ResourceRoute
 * @package Jarischaefer\HalApi\Helpers
 */
class ResourceRoute implements RouteHelperConstants
{

	/**
	 * The resource's name. Should be named like the underlying model's name in plural
	 * (User -> users) or the first part of its controller's name (UsersController -> users).
	 * The URI always starts with this name (e.g. /users).
	 *
	 * @var string
	 */
	private $name;
	/**
	 * The full path (e.g. App\Http\Controllers\MyController) to the controller which handles
	 * all requests for this resource.
	 *
	 * @var string
	 */
	private $controller;
	/**
	 * The "parent" RouteHelper where routes are actually registered.
	 *
	 * @var RouteHelper
	 */
	private $routeHelper;
	/**
	 * @var bool
	 */
	private $pagination;
	/**
	 * An array of methods (see INDEX, SHOW, ... consts in this class) for which routes shall be
	 * generated automatically. By default, all CRUD methods are available.
	 *
	 * @var array
	 */
	private $methods;

	/**
	 * @param string $name
	 * @param string $controller
	 * @param RouteHelper $routeHelper
	 * @param array $methods
	 * @param bool $pagination
	 */
	public function __construct(string $name, string $controller, RouteHelper $routeHelper, array $methods = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY], bool $pagination = true)
	{
		$this->name = $name;
		$this->controller = $controller;
		$this->routeHelper = $routeHelper;
		$this->pagination = $pagination;
		$this->methods = is_array($methods) ? $methods : [$methods];
	}

	/**
	 * Registers a GET route relative to the current resource.
	 *
	 * 	Example:
	 * 	Resource name: users
	 * 	Base URI: /users/{users} where {users} is the identifier (e.g. user ID) for a specific user
	 *  GET call to get a user's posts: /users/{users}/posts
	 *
	 * @param string $uri
	 * @param string $method
	 * @return ResourceRoute
	 */
	public function get(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->get($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a POST route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function post(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->post($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PUT route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function put(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->put($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PATCH route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function patch(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->patch($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a DELETE route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function delete(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->delete($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a GET route that is not relative to the current resource.
	 *
	 * 	Example:
	 * 	Resource name: users
	 * 	Base URI: /users/{users}
	 * 	Raw GET URI for all inactive users: /users/inactive
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function rawGet(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->get($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a POST route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function rawPost(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->post($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PUT route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function rawPut(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->put($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PATCH route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function rawPatch(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->patch($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a DELETE route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return ResourceRoute
	 */
	public function rawDelete(string $uri, string $method): ResourceRoute
	{
		$this->routeHelper->delete($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * @return ResourceRoute
	 */
	public function searchable(): ResourceRoute
	{
		$this->routeHelper
			->get($this->name . '/search', $this->controller, 'search')
			->get($this->name . '/search?' . self::PAGINATION_QUERY_STRING, $this->controller, 'search');

		return $this;
	}

	/**
	 * Call this method once you're done registering additional routes to the current resource.
	 * The original RouteHelper instance is returned, allowing infinite chaining of ordinary routes and resources.
	 *
	 * @return RouteHelper
	 */
	public function done(): RouteHelper
	{
		if (in_array(self::INDEX, $this->methods)) {
			$this->routeHelper->get($this->name, $this->controller, self::INDEX);

			if ($this->pagination) {
				$this->routeHelper->pagination($this->name, $this->controller, self::INDEX);
			}
		}
		if (in_array(self::SHOW, $this->methods)) {
			$this->routeHelper->get($this->name . '/{' . $this->name . '}', $this->controller, self::SHOW);
		}
		if (in_array(self::STORE, $this->methods)) {
			$this->routeHelper->post($this->name, $this->controller, self::STORE);
		}
		if (in_array(self::UPDATE, $this->methods)) {
			$this->routeHelper->put($this->name . '/{' . $this->name . '}', $this->controller, self::UPDATE);
			$this->routeHelper->patch($this->name . '/{' . $this->name . '}', $this->controller, self::UPDATE);
		}
		if (in_array(self::DESTROY, $this->methods)) {
			$this->routeHelper->delete($this->name . '/{' . $this->name . '}', $this->controller, self::DESTROY);
		}

		return $this->routeHelper;
	}

}
