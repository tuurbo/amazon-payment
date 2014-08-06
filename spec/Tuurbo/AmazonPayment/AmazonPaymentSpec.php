<?php namespace spec\Tuurbo\AmazonPayment;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Tuurbo\AmazonPayment\AmazonPaymentClient;

class AmazonPaymentSpec extends ObjectBehavior {

	function let(AmazonPaymentClient $client)
	{
		$this->beConstructedWith($client, [
			'client_id' => '23456',
			'seller_id' => '12345',
			'access_key' => 'ACCESSKEY',
			'secret_key' => 'SECRETKEY',
			'sandbox_mode' => true,
			'store_name' => 'acme',
			'statement_name' => 'acme 555-5555'
		]);

		$this->shouldHaveType('Tuurbo\AmazonPayment\AmazonPayment');
	}

	function it_returns_a_javascript_url()
	{
		$this->script()
			->shouldMatch('/https\:\/\/static-na\.payments-amazon\.com\/OffAmazonPayments\/us\/(sandbox\/)?js\/Widgets\.js\?sellerId=12345/');
	}

	public function it_expects_an_array_from_method_setOrderDetails($client)
	{
		$array = [];
		$array['SetOrderReferenceDetailsResult']['OrderReferenceDetails'] = ['foo'];
		$array['ResponseMetadata']['RequestId'] = ['bar'];

		$client->setupAmazonCall(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn($array);

		$array = [];
		$array['details'] = ['foo'];
		$array['requestId'] = ['bar'];

		$this->setOrderDetails([
				'referenceId' => 12345,
				'amount' => 10.99
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_getOrderDetails()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->getOrderDetails([
				'referenceId' => 12345
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_confirmOrder()
	{
		$array = [];
		$array['requestId'] = null;

		$this->confirmOrder([
				'referenceId' => 12345
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_authorize()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->authorize([
				'referenceId' => 12345,
				'amount' => 10.99
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_authorizeAndCapture()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->authorizeAndCapture([
				'referenceId' => 12345,
				'amount' => 10.99
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_capture()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->capture([
				'referenceId' => 12345,
				'authorizationId' => 67890,
				'amount' => 10.99
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_closeOrder()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->closeOrder([
				'referenceId' => 12345
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_cancelOrder()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->cancelOrder([
				'referenceId' => 12345
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_refund()
	{
		$array = [];
		$array['details'] = null;
		$array['requestId'] = null;

		$this->refund([
				'referenceId' => 12345,
				'amount' => 10.99
			])
			->shouldReturn($array);
	}

	public function it_expects_an_array_from_method_login($client)
	{
		$array = [];
		$array['aud'] = '23456';

		$client->getData(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn($array);

		$this->login('...token...')
			->shouldReturn($array);
	}

	function it_throws_an_exception_if_reference_id_is_not_set()
	{
		$this->shouldThrow('Tuurbo\AmazonPayment\Exceptions\InvalidDataException')
			->duringSetOrderDetails([]);
	}

}