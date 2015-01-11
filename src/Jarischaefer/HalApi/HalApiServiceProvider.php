<?php namespace Jarischaefer\HalApi;

use Illuminate\Support\ServiceProvider;

class HalApiServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('hal-api', function()
		{
			return new HalApi();
		});

		$this->app->bind(HalApiContract::class, HalApi::class);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'hal-api'
		];
	}

}
