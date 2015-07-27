<?php namespace Jarischaefer\HalApi;

use App;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Input;
use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCacheContract;
use Jarischaefer\HalApi\Exceptions\NotImplementedException;
use Jarischaefer\HalApi\Routing\RouteHelper;
use RuntimeException;

/**
 * Class HalApiController
 * @package Jarischaefer\HalApi
 */
abstract class HalApiController extends Controller
{

	use DispatchesJobs, ValidatesRequests;

	/**
	 * Global prefix for the managed cache.
	 */
	const CACHE_GLOBAL_PREFIX = HalApiResourceController::class . '_cache';
	/**
	 * The TTL for managed cache entries.
	 */
	const CACHE_MINUTES = 60;

	/**
	 * @var SafeIndexArray
	 */
	protected $parameters;
	/**
	 * @var SafeIndexArray
	 */
	protected $body;
	/**
	 * @var HalLink
	 */
	protected $self;
	/**
	 * @var HalLink
	 */
	protected $parent;

	/**
	 *
	 */
	public function __construct()
	{
		$this->parameters = new SafeIndexArray(Input::all());
		$this->body = new SafeIndexArray(Input::json()->all());

		if (App::runningInConsole() && !App::runningUnitTests()) {
			return;
		}

		$routeParameters = \Route::current() ? \Route::current()->parameters() : [];
		$this->self = HalLink::make(\Route::current(), $routeParameters, \Route::getCurrentRequest()->getQueryString());
		$this->parent = HalLink::make(RouteHelper::parent(\Route::current()), $routeParameters);
	}

	/**
	 * @param array $parameters
	 * @return HalApiController
	 */
	public static function make(array $parameters = [])
	{
		return App::make(static::class, $parameters);
	}

	/**
	 * @return HalApiCacheContract
	 */
	public static function getCache()
	{
		/** @var CacheFactory $cacheFactory */
		$cacheFactory = App::make(CacheFactory::class);
		$repository = App::make(Repository::class);
		$cacheKey = self::CACHE_GLOBAL_PREFIX . '_' . static::class;

		return $cacheFactory->create($repository, $cacheKey, self::CACHE_MINUTES);
	}

	/**
	 * @return HalApiCacheContract[]
	 */
	public static function getRelatedCaches()
	{
		return [];
	}

	/**
	 * @return HalApiElement
	 */
	protected function createResponse()
	{
		return HalApiElement::make($this->self, $this->parent);
	}

	/**
	 * Returns a controller's relation. The relation is a way of interacting with resources using
	 * a name rather than relying on a hyperlink. Legacy web applications typically construct
	 * hyperlinks manually. Changing protocols from http to https, switching domain names or suddenly
	 * running your application in a subdirectory (http://my.app.example.com/users becomes http://example.com/php/myapp/users)
	 * will very likely break such code. HATEOAS refers to resources by relation rather than hyperlink.
	 * The URL http://my.app.example.com/users could be referred to as users.index and should include
	 * a hyperlink called self containing aforementioned URL.
	 *
	 * Check out http://stateless.co/hal_specification.html
	 *
	 * @param string $action
	 * @return string
	 */
	public static function getRelation($action = null)
	{
		throw new NotImplementedException('Child classes must implement this method');
	}

	/**
	 * Returns the action name (e.g. App\Http\Controllers\MyController@doSomething).
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function actionName($methodName)
	{
		return get_called_class() . '@' . $methodName;
	}

	/**
	 * Generates a URL for the given method name and parameters.
	 * If your routes.php contains an entry for /users linked to the
	 * index method and another entry for /users/{users} linked to the
	 * show method, then a call to this method would yield the following result:
	 *
	 * UsersController::action('show', '99e31491-dd32-4e2c-b221-7deeb6cc4853')
	 *
	 * http://my.app.example.com/users/99e31491-dd32-4e2c-b221-7deeb6cc4853
	 *
	 * @param string $methodName
	 * @param array $parameters
	 * @return string
	 */
	public static function action($methodName, $parameters = [])
	{
		$class = get_called_class();

		if (!method_exists($class, $methodName)) {
			throw new RuntimeException('Method does not exist!');
		}

		$parameters = is_array($parameters) ? $parameters : [$parameters];
		$actionName = class_basename($class) . '@' . $methodName;

		return action($actionName, $parameters);
	}

}
