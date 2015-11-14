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
	 * @param $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
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
	private static function eTagsMatch(Request $request, $responseTag)
	{
		$requestTags = str_replace('"', '', $request->getETags());

		return is_array($requestTags) && in_array($responseTag, $requestTags);
	}

}
