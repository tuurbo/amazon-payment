<?php namespace Tuurbo\AmazonPayment;

use Illuminate\Support\Facades\Facade;

class AmazonPaymentFacade extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'amazonpayment'; }

}