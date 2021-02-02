<?php


namespace Imageplus\APIVersionControl\Http\Middleware;

use Closure;
use Imageplus\APIVersionControl\Facades\APIVersionControl;
use Imageplus\APIVersionControl\Facades\APIVersionValidator;

class APIVersionMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @param string $version
	 * @return mixed
	 */
	public function handle($request, Closure $next, string $version)
	{
		if (!config('api_vcs.enabled', false))
			return $next($request); //API isn't locked to versions

		if (!APIVersionValidator::validateVersion(APIVersionControl::getVersion(), collect($version)))
			return \Response::json([
				'success' => false,
				'errors' => [
					'version' => [
						'API version'
					]
				],
				'message' => 'API Version is invalid for current resource'
			], 426);

		return $next($request);
	}
}