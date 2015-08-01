<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Jarischaefer\HalApi\Caching\HalApiCacheContract;

/**
 * Class HalApiControllerContract
 * @package Jarischaefer\HalApi\Controllers
 */
interface HalApiControllerContract
{

	/**
	 * @param Application $application
	 * @return HalApiCacheContract
	 */
	public static function getCache(Application $application);

	/**
	 * @param Application $application
	 * @return HalApiCacheContract[]
	 */
	public static function getRelatedCaches(Application $application);

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
	public static function getRelation($action = null);

	/**
	 * Returns the action name (e.g. App\Http\Controllers\MyController@doSomething).
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function actionName($methodName);

	/**
	 * Generates a URL for the given method name and parameters.
	 * If your routes.php contains an entry for /users linked to the
	 * index method and another entry for /users/{users} linked to the
	 * show method, then a call to this method would yield the following result:
	 *
	 * UsersController::action($urlGenerator, 'show', '99e31491-dd32-4e2c-b221-7deeb6cc4853')
	 *
	 * http://my.app.example.com/users/99e31491-dd32-4e2c-b221-7deeb6cc4853
	 *
	 * @param UrlGenerator $urlGenerator
	 * @param string $methodName
	 * @param array $parameters
	 * @return string
	 */
	public static function action(UrlGenerator $urlGenerator, $methodName, $parameters = []);

}