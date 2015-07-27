<?php namespace Jarischaefer\HalApi\Caching;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HalApiETagMiddleware
{

	public function handle($request, Closure $next)
	{
		$response = $next($request);

		if ($request instanceof Request && $response instanceof Response && $request->isMethodSafe()) {
			$responseTag = md5($response->getContent());
			$response->setMaxAge(0);
			$response->setEtag($responseTag);

			if ($this->eTagsMatch($request, $responseTag)) {
				$response->setNotModified();
			}
		}

		return $response;
	}

	private function eTagsMatch(Request $request, $responseTag)
	{
		$requestTags = str_replace('"', '', $request->getETags());

		return is_array($requestTags) && in_array($responseTag, $requestTags);
	}

}
