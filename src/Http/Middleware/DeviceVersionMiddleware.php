<?php


namespace Imageplus\APIVersionControl\Http\Middleware;

use Closure;
use Imageplus\APIVersionControl\Classes\Device;
use Imageplus\APIVersionControl\Facades\APIVersionControl;
use Imageplus\APIVersionControl\Facades\APIVersionValidator;

class DeviceVersionMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @param string $type
	 * @param string $version
	 * @return mixed
	 */
	public function handle($request, Closure $next, string $type, string $version)
	{
		if (!config('api_vcs.enabled', false))
			return $next($request); //API isn't locked to versions

		$device = APIVersionControl::getDevice();

		if (!$device || $device->type !== $type)
			return $next($request);

		if (!APIVersionValidator::validateVersion($device->getVersion(), collect($version)))
			return \Response::json([
				'success' => false,
				'errors' => [
					'version' => [
						'Device version'
					]
				],
				'message' => 'Device version is invalid for current resource'
			], 426);

		return $next($request);
	}
}