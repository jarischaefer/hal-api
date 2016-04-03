<?php namespace Jarischaefer\HalApi\Helpers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Jarischaefer\HalApi\Controllers\HalApiControllerContract;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an easier, more RESTful approach of registering routes.
 *
 * Class RouteHelper
 * @package Jarischaefer\HalApi\Helpers
 */
class RouteHelper implements RouteHelperConstants
{

	/**
	 * @var array
	 */
	private static $relationCache = [];
	/**
	 * @var array
	 */
	private static $isValidActionNameCache = [];

	/**
	 * Checks if a route is bound to an implementation of {@see HalApiControllerContract}
	 *
	 * @param Route $route
	 * @return bool
	 */
	public static function isValid(Route $route): bool
	{
		return self::isValidActionName($route->getActionName());
	}

	/**
	 * @param string $actionName
	 * @return bool
	 */
	public static function isValidActionName(string $actionName): bool
	{
		if (isset(self::$isValidActionNameCache[$actionName])) {
			return self::$isValidActionNameCache[$actionName];
		}

		$split = explode(self::ACTION_NAME_DELIMITER, $actionName);
		$isValid = empty($split) ? false : is_subclass_of($split[0], HalApiControllerContract::class);

		return self::$isValidActionNameCache[$actionName] = $isValid;
	}

	/**
	 * @param Route $route
	 * @return string
	 */
	public static function relation(Route $route): string
	{
		$actionName = $route->getActionName();

		if (isset(self::$relationCache[$actionName])) {
			return self::$relationCache[$actionName];
		}

		/** @var HalApiControllerContract $class */
		list($class, $method) = explode(self::ACTION_NAME_DELIMITER, $actionName);

		return self::$relationCache[$actionName] = $class::getRelation($method);
	}

	/**
	 * @return callable
	 */
	public static function getModelBindingCallback(): callable
	{
		return function ($value) {
			switch (\Request::getMethod()) {
				case Request::METHOD_GET:
					throw new NotFoundHttpException;
				case Request::METHOD_POST:
					throw new NotFoundHttpException;
				case Request::METHOD_PUT:
					return $value;
				case Request::METHOD_PATCH:
					throw new NotFoundHttpException;
				case Request::METHOD_DELETE:
					throw new NotFoundHttpException;
				default:
					return null;
			}
		};
	}

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
	 * @return RouteHelper
	 */
	public static function make(Router $router): RouteHelper
	{
		return new static($router);
	}

	/**
	 * @param Router $router
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * @return Router
	 */
	public function getRouter(): Router
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
	public function resource(string $name, string $controller, array $methods = [self::INDEX, self::SHOW, self::STORE, self::UPDATE, self::DESTROY], bool $pagination = true): ResourceRoute
	{
		return new ResourceRoute($name, $controller, $this, $methods, $pagination);
	}

	/**
	 * Registers a new GET route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function get(string $uri, string $controller, string $method): RouteHelper
	{
		$this->router->get($uri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Registers a new POST route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function post(string $uri, string $controller, string $method): RouteHelper
	{
		$this->router->post($uri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Registers a new PUT route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function put(string $uri, string $controller, string $method): RouteHelper
	{
		$this->router->put($uri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Registers a new PATCH route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function patch(string $uri, string $controller, string $method): RouteHelper
	{
		$this->router->patch($uri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Registers a new DELETE route.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function delete(string $uri, string $controller, string $method): RouteHelper
	{
		$this->router->delete($uri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Registers a new GET route with pagination parameters. Pagination parameters are appended to the current
	 * query string. An URI like /users/{userid}/friends?age=5 would result in/users/{userid}/friends?age=5&page={page}&per_page={per_page}.
	 *
	 * @param string $uri The URI (e.g. / or /users or /users/{param}/friends).
	 * @param string $controller The path to the controller handling the resource (e.g. UsersController::class or App\Http\Controllers\UsersController).
	 * @param string $method Name of the method inside the controller that will handle the request.
	 * @return RouteHelper
	 */
	public function pagination(string $uri, string $controller, string $method): RouteHelper
	{
		$paginatedUri = $uri . (stripos($uri, '?') ? '&' : '?') . self::PAGINATION_URI;
		$this->router->get($paginatedUri, $controller . static::ACTION_NAME_DELIMITER . $method);

		return $this;
	}

	/**
	 * Returns the route matching an action name.
	 *
	 * @param string $actionName The action name (e.g. App\Http\Controllers\UsersController@delete).
	 * @return Route
	 */
	public function byAction(string $actionName): Route
	{
		if (isset($this->byActionRouteCache[$actionName])) {
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
	public function parent(Route $child): Route
	{
		$childUri = $child->getUri();

		if (isset($this->parentRouteCache[$childUri])) {
			return $this->parentRouteCache[$childUri];
		}

		$lastSlash = strripos($childUri, '/');
		$guessedParentUri = $lastSlash === FALSE ? '/' : substr($childUri, 0, $lastSlash);

		while (true) {
			/** @var Route $route */
			foreach ($this->router->getRoutes() as $route) {
				if (strcmp($route->getUri(), $guessedParentUri) === 0) {
					return $this->parentRouteCache[$childUri] = $route;
				}
			}

			if ($guessedParentUri === '/') {
				break;
			}

			// cut off another slash part (e.g. /users/{userid}/friends -> /users/{userid})
			$guessedParentUri = substr($guessedParentUri, 0, strripos($guessedParentUri, '/'));

			if ($guessedParentUri === '') {
				$guessedParentUri = '/';
			}
		}

		return $this->parentRouteCache[$childUri] = $child; // return the same route if no parent exists
	}

	/**
	 * Returns a given route's children as an array. A parent route like /users would return (if they existed) all routes
	 * like /users/{userid}, /users/new.
	 *
	 * @param Route $parentRoute
	 * @return Route[]
	 */
	public function subordinates(Route $parentRoute): array
	{
		$parentUri = $parentRoute->getUri();

		if (isset($this->subordinateRouteCache[$parentUri])) {
			return $this->subordinateRouteCache[$parentUri];
		}

		$parentActionName = $parentRoute->getActionName();
		$children = [];

		/** @var Route $route */
		foreach ($this->router->getRoutes() as $route) {
			// if the route does not start with the same uri as the current route -> skip
			if ($parentUri !== '/' && strpos($route->getUri(), $parentUri) !== 0) {
				continue;
			}

			$actionName = $route->getActionName();

			if (!self::isValidActionName($actionName)) {
				continue;
			}

			if (strcmp($parentActionName, $actionName) !== 0) {
				$children[] = $route;
			}
		}

		return $this->subordinateRouteCache[$parentUri] = $children;
	}

}
