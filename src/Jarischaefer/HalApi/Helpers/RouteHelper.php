<?php namespace Jarischaefer\HalApi\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;
use ReflectionClass;
use RuntimeException;

/**
 * Provides an easier, more RESTful approach of registering routes.
 *
 * Class RouteHelper
 * @package Jarischaefer\HalApi\Helpers
 */
class RouteHelper implements RouteHelperConstants
{

	/**
	 * Holds routes belonging to an action name.
	 *
	 * @var array
	 */
	private $byActionRouteCache = [];
	/**
	 * Holds parent routes.
	 *
	 * @var array
	 */
	private $parentRouteCache = [];
	/**
	 * Holds child routes.
	 *
	 * @var array
	 */
	private $subordinateRouteCache = [];
	/**
	 * The router where routes shall be registered.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * @param Router $router
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * @param Router $router
	 * @return static
	 */
	public static function make(Router $router)
	{
		return new static($router);
	}

	/**
	 * @return Router
	 */
	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * Creates a new REST resource.
	 *
	 * @param string $name The resource's name (e.g. users).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param array $methods Array of CRUD methods which should be registered automatically.
	 * @param bool $pagination
	 * @return ResourceRoute
	 */
	public function resource($name, $controller, $methods = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY], $pagination = true)
	{
		return new ResourceRoute($name, $controller, $this, $methods, $pagination);
	}

	/**
	 * Registers a new GET route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function get($uri, $controller, $method)
	{
		$this->router->get($uri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Registers a new POST route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function post($uri, $controller, $method)
	{
		$this->router->post($uri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Registers a new PUT route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function put($uri, $controller, $method)
	{
		$this->router->put($uri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Registers a new PATCH route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function patch($uri, $controller, $method)
	{
		$this->router->patch($uri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Registers a new DELETE route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function delete($uri, $controller, $method)
	{
		$this->router->delete($uri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Registers a new GET route with pagination parameters. Pagination parameters are appended to the current
	 * query string. An URI like /users/{userid}/friends?age=5 would result in/users/{userid}/friends?age=5&page={page}&per_page={per_page}.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return $this
	 */
	public function pagination($uri, $controller, $method)
	{
		$paginatedUri = $uri . (stripos($uri, '?') ? '&' : '?') . self::PAGINATION_URI;
		$this->router->get($paginatedUri, $controller . '@' . $method);

		return $this;
	}

	/**
	 * Returns the route matching an action name.
	 *
	 * @param string $actionName The action name (e.g. App\Http\Controllers\UsersController@delete).
	 * @return Route
	 */
	public function byAction($actionName)
	{
		if (array_key_exists($actionName, $this->byActionRouteCache)) {
			return $this->byActionRouteCache[$actionName];
		}

		$route = $this->router->getRoutes()->getByAction($actionName);

		if ($route instanceof Route) {
			return $this->byActionRouteCache[$actionName] = $route;
		}

		throw new RuntimeException('Could not find route for action: ' . $actionName);
	}

	/**
	 * Returns a given route's parent route. Given the routes /users/{userid} and /users, this method would
	 * return the latter route if called with the former. If no parent is present (e.g. the / route), the uppermost
	 * route will be returned (always /).
	 *
	 * @param Route $child
	 * @return Route
	 */
	public function parent(Route $child)
	{
		// TODO reflection mess

		if (array_key_exists($child->getUri(), $this->parentRouteCache)) {
			return $this->parentRouteCache[$child->getUri()];
		}

		$lastSlash = strripos($child->getUri(), '/');
		$guessedParentUri = $lastSlash !== FALSE ? substr($child->getUri(), 0, $lastSlash) : '/';
		$request = new Request;
		$reflectionClass = new ReflectionClass($request);
		$pathInfo = $reflectionClass->getProperty('pathInfo');
		$pathInfo->setAccessible(true);
		$requestUri = $reflectionClass->getProperty('requestUri');
		$requestUri->setAccessible(true);

		while (true) {
			$pathInfo->setValue($request, $guessedParentUri);
			$requestUri->setValue($request, $guessedParentUri);

			try {
				$route = $this->router->getRoutes()->match($request);

				if ($route instanceof Route) {
					return $this->parentRouteCache[$child->getUri()] = $route;
				}
			} catch (Exception $e) {
                if ($guessedParentUri == '/') {
                    break;
                }
            }

			// cut off another slash part (e.g. /users/{userid}/friends -> /users/{userid})
			$guessedParentUri = substr($guessedParentUri, 0, strripos($guessedParentUri, '/'));

            if ($guessedParentUri == '') {
                $guessedParentUri = '/';
            }
		}

		return $this->parentRouteCache[$child->getUri()] = $child; // return the same route if no parent exists
	}

	/**
	 * Returns a given route's children as an array. A parent route like /users would return (if they existed) all routes
	 * like /users/{userid}, /users/new.
	 *
	 * @param Route $parentRoute
	 * @return Route[]
	 */
	public function subordinates(Route $parentRoute)
	{
		if (array_key_exists($parentRoute->getUri(), $this->subordinateRouteCache)) {
			return $this->subordinateRouteCache[$parentRoute->getUri()];
		}

		$routes = $this->router->getRoutes();
		$children = [];

		/** @var Route $route */
		foreach ($routes as $route) {
			if (!self::isValid($route)) {
				continue;
			}

			// if the route does not start with the same uri as the current route -> skip
			if ($parentRoute->getUri() != '/' && !starts_with($route->getUri(), $parentRoute->getUri())) {
				continue;
			}

			// if route equals the parent route
			if ($parentRoute->getActionName() == $route->getActionName()) {
				continue;
			}

			$children[] = $route;
		}

		return $this->subordinateRouteCache[$parentRoute->getUri()] = $children;
	}

	/**
	 * Checks if a route is bound to an implementation of {@see HalApiControllerContract}
	 *
	 * @param Route $route
	 * @return bool
	 */
	public static function isValid(Route $route)
	{
		$actionName = $route->getActionName();

		// valid routes are backed by a controller (e.g. App\Http\Controllers\MyController@doSomething)
		if (!str_contains($actionName, '@')) {
			return false;
		}

		$class = explode('@', $actionName)[0];

		// only add a link if this class is its controller's parent
		if (!is_subclass_of($class, HalApiControllerContract::class)) {
			return false;
		}

		return true;
	}

}
