<?php


namespace Imageplus\APIVersionControl\Http\Middleware;

use Closure;
use Imageplus\APIVersionControl\Facades\APIVersionControl;

class Middleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @param string $version
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if (!config('api_vcs.enabled', false))
			return $next($request); //API isn't locked to versions

		$device_type = trim($request->header('Device-Type'));
		$device_version = trim($request->header('Device-Version'));

		$request_uri = $request->server->get('REQUEST_URI');
		$global_version = null;
		$preg_matches = [];
		preg_match('/(\/api\/v[0-9]+)/', $request_uri, $preg_matches);

		//If we have been redirected for a version, let's grab and register that version
		if (strrpos(url()->previous(), '/api/v') !== false){
			try {
				$request_uri = substr(url()->previous(), strrpos(url()->previous(), '/api/v'));
				$request_uri = substr($request_uri, strlen('/api/v'));

				if (substr($request_uri, 0, 1) === '/')
					$request_uri = substr($request_uri, 1);

				$global_version = (int)substr($request_uri, 0, strrpos($request_uri, '/'));
			}
			catch (\Exception $exception){
				abort(404, $exception->getMessage());
			}

		}
		//else let's check if we do have a version on the url
		else if (strrpos($request_uri, '/api/v') === 0 && count($preg_matches)){
			try {
				$request_uri = substr($request_uri, strlen('/api/v'));

				if (substr($request_uri, 0, 1) === '/')
					$request_uri = substr($request_uri, 1);

				$request_uri = '/api' . strstr($request_uri, '/');
			}
			catch (\Exception $exception){
				abort(404, $exception->getMessage());
			}

			return redirect($request_uri); //Can't find a way to pass the data to the new request
		}

		APIVersionControl::registerRequestGlobalVersion($global_version);
		APIVersionControl::registerRequestDevice($device_type, $device_version);

		if (!APIVersionControl::hasAccess())
			return \Response::json([
				'success' => false,
				'errors' => [
					'version' => [
						'This version was locked out of the api.'
					]
				],
				'message' => APIVersionControl::getAccessMessage()
			], 426);

		return $next($request);
	}
}