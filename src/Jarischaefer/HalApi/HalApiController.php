<?php namespace Jarischaefer\HalApi;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Input;
use InvalidArgumentException;
use Jarischaefer\HalApi\Routing\RouteHelper;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;

/**
 * Class HalApiController
 * @package Jarischaefer\HalApi
 */
abstract class HalApiController extends Controller
{

	use DispatchesCommands, ValidatesRequests;

	/**
	 * @var Manager
	 */
	protected $manager;
	/**
	 * @var HalApiContract
	 */
	protected $api;
	/**
	 * @var SafeIndexArray
	 */
	protected $input;
	/**
	 * @var array
	 */
	protected $json;

	
	public final function __construct(Manager $manager, ArraySerializer $serializer, HalApiContract $api)
	{
		$routeParameters = \Route::current() ? \Route::current()->parameters() : [];

		$this->manager = $manager;
		$this->manager->setSerializer($serializer);

		$this->input = new SafeIndexArray(Input::all());
		$this->json = new SafeIndexArray(Input::json()->all());

		$this->api = $api;
		$this->api->self(HalLink::make(\Route::current(), $routeParameters, \Route::getCurrentRequest()->getQueryString()));
		$this->api->parent(HalLink::make(RouteHelper::parent(\Route::current()), $routeParameters));
		$subordinateRoutes = RouteHelper::subordinates(\Route::current());

		/* @var Route $route */
		foreach ($subordinateRoutes as $route) {
			$this->addLink($route, \Route::current()->parameters());
		}

		if (Input::has('include')) {
			$this->manager->parseIncludes(Input::get('include'));
		}

		$this->boot();
	}
	
	protected function boot()
	{

	}
	
	private function addLink(Route $route, $parameters = [], $queryString = '', $keepOriginalQueryString = false)
	{
		$actionName = $route->getActionName();

		// valid routes are backed by a controller (e.g. App\Http\Controllers\MyController@doSomething)
		if (!str_contains($actionName, '@')) {
			return;
		}

		list($class, $method) = explode('@', $actionName);

		// only add a link if this class is its controller's parent
		if (!is_subclass_of($class, __CLASS__)) {
			return;
		}

		/* @var HalApiController $class */
		$link = HalLink::make($route, $parameters, $queryString, $keepOriginalQueryString);
		$this->api->link($class::getRelation($method), $link);
	}
	
	public static function getRelation($action = null)
	{
		// TODO messy
		$baseName = class_basename(get_called_class());
		
		if (!str_contains($baseName, 'Controller')) {
			$rel = strtolower($baseName);
			return empty($action) ? $rel : $rel . '.' . $action;
		} else {
			$rel = explode('Controller', $baseName);
			return empty($action) ? strtolower($rel[0]) : strtolower($rel[0]) . '.' . $action;
		}
	}
	
	public static function actionName($methodName)
	{
		return get_called_class() . '@' . $methodName;
	}
	
	public static function action($methodName, $parameters = [])
	{
		$class = get_called_class();

		if (!method_exists($class, $methodName)) {
			throw new InvalidArgumentException('Method does not exist.');
		}

		$parameters = is_array($parameters) ? $parameters : [$parameters];
		$actionName = class_basename($class) . '@' . $methodName;

		return action($actionName, $parameters);
	}
	
}
