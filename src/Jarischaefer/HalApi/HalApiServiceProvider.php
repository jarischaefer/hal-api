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
		$this->app->bind('hal-api', function()
		{
			return new HalApiElement;
		});

		$this->app->bind(HalApiContract::class, HalApiElement::class);
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
