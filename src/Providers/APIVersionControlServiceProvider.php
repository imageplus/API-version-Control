<?php

namespace Imageplus\APIVersionControl\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imageplus\APIVersionControl\Classes\APIVersionControlSingleton;
use Imageplus\APIVersionControl\Classes\APIVersionControlValidator;
use Imageplus\APIVersionControl\Console\Commands\HasAccessCommand;
use Imageplus\APIVersionControl\Http\Middleware\APIVersionMiddleware;
use Imageplus\APIVersionControl\Http\Middleware\DeviceVersionMiddleware;
use Imageplus\APIVersionControl\Http\Middleware\FeatureVersionMiddleware;
use Imageplus\APIVersionControl\Http\Middleware\Middleware;

class APIVersionControlServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		//only 1 instance of the sns manager is required
		$this->app->singleton('api_vcs', APIVersionControlSingleton::class);
		$this->app->singleton('api_vcs_validator', APIVersionControlValidator::class);
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//register the default config
		$this->mergeConfigFrom(__DIR__.'/../config/api_vcs.php', 'api_vcs');
		$this->publishes([
			__DIR__ . '/../config/api_vcs.php' => config_path('api_vcs.php')
		], 'imageplus-api-vcs-config');

		//appends the middleware
		$this->appendMiddleware();
		$this->handleCommands();
		$this->handleRoutes();
	}

	/**
	 *  Handles discovery of packages within laravel
	 */
	protected function handleCommands()
	{
		if (!$this->app->runningInConsole()) {
			return;
		}

		$this->commands([
			HasAccessCommand::class,
		]);
	}

	/**
	 * Handle the creation of the routes
	 *
	 */
	protected function handleRoutes()
	{
		//Forces the middleware to always trigger
		Route::any('/api/{version}/{any}', function () {
			abort(404);
		})
			->middleware('api')
			->where('version', 'v[0-9]+')
			->where('any', '.*');
	}

	/**
	 * Add the ability to append the shared data for inertia
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function appendMiddleware(){
		$kernel = $this->app->make(Kernel::class);
		$router = $this->app->get('router');

		$kernel->appendMiddlewareToGroup('api', Middleware::class);
		$kernel->appendToMiddlewarePriority(Middleware::class);

		$router->aliasMiddleware('feature', FeatureVersionMiddleware::class);
		$router->aliasMiddleware('api_version', APIVersionMiddleware::class);
		$router->aliasMiddleware('device_version', DeviceVersionMiddleware::class);
	}
}
