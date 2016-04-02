<?php namespace Jarischaefer\HalApi\Controllers;

use Jarischaefer\HalApi\Caching\CacheFactory;
use Jarischaefer\HalApi\Caching\HalApiCache;
use Jarischaefer\HalApi\Routing\HalApiUrlGenerator;

/**
 * Class HalApiControllerContract
 * @package Jarischaefer\HalApi\Controllers
 */
interface HalApiControllerContract
{

	/**
	 * @param CacheFactory $cacheFactory
	 * @return HalApiCache
	 */
	public static function getCache(CacheFactory $cacheFactory): HalApiCache;

	/**
	 * @param CacheFactory $cacheFactory
	 * @return HalApiCache[]
	 */
	public static function getRelatedCaches(CacheFactory $cacheFactory): array;

	/**
	 * Returns the controller's relation.
	 *
	 * @return string
	 */
	public static function getRelationName(): string;

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
	public static function getRelation(string $action = null): string;

	/**
	 * Returns the action name (e.g. App\Http\Controllers\MyController@doSomething).
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function actionName(string $methodName): string;

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
	 * @param HalApiUrlGenerator $urlGenerator
	 * @param string $methodName
	 * @param array|mixed $parameters
	 * @return string
	 */
	public static function action(HalApiUrlGenerator $urlGenerator, string $methodName, $parameters = []): string;

}
