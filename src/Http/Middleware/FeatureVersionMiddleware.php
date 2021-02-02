<?php


namespace Imageplus\APIVersionControl\Http\Middleware;

use Closure;
use Imageplus\APIVersionControl\Facades\APIVersionControl;

class FeatureVersionMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @param string $version
	 * @return mixed
	 */
	public function handle($request, Closure $next, $feature_key)
	{
		if (!config('api_vcs.enabled', false))
			return $next($request); //API isn't locked to versions

		if (!APIVersionControl::hasAccess($feature_key))
			return \Response::json([
				'success' => false,
				'errors' => [
					'version' => [
						'This version was locked out of the api.'
					]
				],
				'message' => APIVersionControl::getAccessMessage($feature_key)
			], 426);

		return $next($request);
	}
}