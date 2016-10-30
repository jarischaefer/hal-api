<?php namespace Jarischaefer\HalApi\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HalApiETagMiddleware
 * @package Jarischaefer\HalApi\Caching
 */
class HalApiETagMiddleware
{

	/**
	 *
	 */
	const NAME = 'hal-api.etag';

	/**
	 * @var bool
	 */
	private static $weakETagsAllowed = true;

	/**
	 * @return bool
	 */
	public static function isWeakETagsAllowed(): bool
	{
		return self::$weakETagsAllowed;
	}

	/**
	 * @param bool $weakETagsAllowed
	 */
	public static function setWeakETagsAllowed(bool $weakETagsAllowed)
	{
		self::$weakETagsAllowed = $weakETagsAllowed;
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$response = $next($request);

		if ($request instanceof Request && $response instanceof Response && $request->isMethodSafe()) {
			$responseTag = md5($response->getContent());
			$response->setMaxAge(0);
			$response->setEtag($responseTag);

			if (self::eTagsMatch($request, $responseTag)) {
				$response->setNotModified();
			}
		}

		return $response;
	}

	/**
	 * @param Request $request
	 * @param string $responseTag
	 * @return bool
	 */
	private static function eTagsMatch(Request $request, string $responseTag): bool
	{
		$requestTags = str_replace('"', '', $request->getETags());

		if (in_array($responseTag, $requestTags)) {
			return true;
		}

		if (!self::$weakETagsAllowed) {
			return false;
		}

		$requestTags = str_replace('W/', '', $requestTags);

		return in_array($responseTag, $requestTags);
	}

}
