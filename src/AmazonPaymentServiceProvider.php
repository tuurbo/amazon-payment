<?php namespace Tuurbo\AmazonPayment;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

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
			$config = $app['config']->get('services.amazonpayment');

			$client = (new GuzzleRetryClient(3))->create();

			return new AmazonPayment(new AmazonPaymentClient($client, $config), $config);
		});
	}

}
