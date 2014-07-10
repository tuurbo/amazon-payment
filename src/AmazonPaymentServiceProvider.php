<?php namespace Tuurbo\AmazonPayment;

use Illuminate\Support\ServiceProvider;
use Config;

class AmazonPaymentServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['amazonpayment'] = $this->app->share(function($app)
		{
			return new AmazonPayment($app['config']->get('amazon'));
		});
	}

}