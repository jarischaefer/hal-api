<?php namespace Jarischaefer\HalApi\Routing;

/**
 * Class ResourceRoute
 * @package Jarischaefer\HalApi\Routing
 */
class ResourceRoute
{

	/**
	 * Method name for GET requests that list all resources (possibly paginated).
	 */
	const INDEX = RouteHelper::INDEX;
	/**
	 * Method name for GET requests that request a specific resource.
	 */
	const SHOW = RouteHelper::SHOW;
	/**
	 * Method name for POST requests that create a new resource.
	 */
	const STORE = RouteHelper::STORE;
	/**
	 * Method name for PUT and PATCH requests that either create or fully update (PUT)
	 * or partially update (PATCH) a resource.
	 */
	const UPDATE = RouteHelper::UPDATE;
	/**
	 * Method name for DELETE requests that delete an existing resource.
	 */
	const DESTROY = RouteHelper::DESTROY;

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
	 * An array of methods (see INDEX, SHOW, ... consts in this class) for which routes shall be
	 * generated automatically. By default, all CRUD methods are available.
	 *
	 * @var array
	 */
	private $methods;

	/**
	 * @param $name
	 * @param $controller
	 * @param RouteHelper $routeHelper
	 * @param $methods
	 */
	public function __construct($name, $controller, RouteHelper $routeHelper, $methods = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY])
	{
		$this->name = (string)$name;
		$this->controller = (string)$controller;
		$this->routeHelper = $routeHelper;
		$this->methods = is_array($methods) ? $methods : [$methods];

		if (in_array(self::INDEX, $this->methods)) {
			$this->routeHelper->get($this->name, $this->controller, self::INDEX);
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
	}

	/**
	 * @param $name
	 * @param $controller
	 * @param RouteHelper $routeHelper
	 * @param array $methods
	 * @return ResourceRoute
	 */
	public static function make($name, $controller, RouteHelper $routeHelper, $methods = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY])
	{
		return new self($name, $controller, $routeHelper, $methods);
	}

	/**
	 * Registers a GET route relative to the current resource.
	 *
	 * 	Example:
	 * 	Resource name: users
	 * 	Base URI: /users/{users} where {users} is the identifier (e.g. user ID) for a specific user
	 *  GET call to get a user's posts: /users/{users}/posts
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function get($uri, $method)
	{
		$this->routeHelper->get($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a POST route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function post($uri, $method)
	{
		$this->routeHelper->post($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PUT route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function put($uri, $method)
	{
		$this->routeHelper->put($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PATCH route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function patch($uri, $method)
	{
		$this->routeHelper->patch($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a DELETE route relative to the current resource. Take a look at the docs for the get method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function delete($uri, $method)
	{
		$this->routeHelper->delete($this->name . '/{' . $this->name . '}/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Adds a pagination route to the current resource.
	 *
	 * @return $this
	 */
	public function pagination()
	{
		$this->routeHelper->pagination($this->name, $this->controller, self::INDEX);

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
	 * @return $this
	 */
	public function rawGet($uri, $method)
	{
		$this->routeHelper->get($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a POST route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function rawPost($uri, $method)
	{
		$this->routeHelper->post($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PUT route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function rawPut($uri, $method)
	{
		$this->routeHelper->put($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a PATCH route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function rawPatch($uri, $method)
	{
		$this->routeHelper->patch($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Registers a DELETE route that is not relative to the current resource. Take a loot at the rawGet method for further information.
	 *
	 * @param $uri
	 * @param $method
	 * @return $this
	 */
	public function rawDelete($uri, $method)
	{
		$this->routeHelper->delete($this->name . '/' . $uri, $this->controller, $method);

		return $this;
	}

	/**
	 * Call this method once you're done registering additional routes to the current resource.
	 * The original RouteHelper instance is returned, allowing infinite chaining of ordinary routes and resources.
	 *
	 * @return RouteHelper
	 */
	public function done()
	{
		return $this->routeHelper;
	}

}