<?php namespace Tuurbo\AmazonPayment;

use Tuurbo\AmazonPayment\Exceptions;

class AmazonPayment {

	protected $client;
	protected $config;
	protected $storeName;
	protected $statementName;

	public function __construct(AmazonPaymentClient $client, $config)
	{
		$this->client = $client;
		$this->config = $config;

		if ($this->config['store_name']) {
			$this->storeName = $this->config['store_name'];
		}

		if ($this->config['statement_name']) {
			$this->statementName = $this->config['statement_name'];
		}
	}

	public function setOrderDetails($data)
	{
		$this->requiredData($data, ['referenceId', 'amount']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
			'OrderReferenceAttributes.OrderTotal.CurrencyCode' => 'USD',
			'OrderReferenceAttributes.OrderTotal.Amount' => $data['amount']
		];

		if (isset($data['storeName']) || $this->storeName) {
			$params['OrderReferenceAttributes.SellerOrderAttributes.StoreName'] = isset($data['storeName']) ? $data['storeName'] : $this->storeName;
		}

		if (isset($data['orderId'])) {
			$params['OrderReferenceAttributes.SellerOrderAttributes.SellerOrderId'] = $data['orderId'];
		}

		if (isset($data['note'])) {
			$params['OrderReferenceAttributes.SellerNote'] = $data['note'];
		}

		$resp = $this->client->setupAmazonCall('SetOrderReferenceDetails', $params);

		return [
			'details' => $resp['SetOrderReferenceDetailsResult']['OrderReferenceDetails'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function getOrderDetails($data)
	{
		$this->requiredData($data, ['referenceId']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId']
		];

		if (isset($data['token'])) {
			$params['AddressConsentToken'] = $data['token'];
		}

		$resp = $this->client->setupAmazonCall('GetOrderReferenceDetails', $params);

		return [
			'details' => $resp['GetOrderReferenceDetailsResult']['OrderReferenceDetails'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function confirmOrder($data)
	{
		$this->requiredData($data, ['referenceId']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
		];

		$resp = $this->client->setupAmazonCall('ConfirmOrderReference', $params);

		return [
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function authorize($data)
	{
		return $this->setupAuthorize($data);
	}

	public function authorizeAndCapture($data)
	{
		return $this->setupAuthorize($data, true);
	}

	public function capture($data)
	{
		$this->requiredData($data, ['referenceId', 'authorizationId', 'amount']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
			'AmazonAuthorizationId' => $data['authorizationId'],
			'CaptureReferenceId' => $this->generateReferenceId($data['referenceId'], 'C'),
			'CaptureAmount.Amount' => $data['amount'],
			'CaptureAmount.CurrencyCode' => 'USD'
		];

		if (isset($data['statementName']) || $this->statementName) {
			$params['SoftDescriptor'] = isset($data['statementName']) ? $data['statementName'] : $this->statementName;
		}

		$resp = $this->client->setupAmazonCall('Capture', $params);

		return [
			'details' => $resp['CaptureResult']['CaptureDetails'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function closeOrder($data)
	{
		$this->requiredData($data, ['referenceId']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
			'ClosureReason' => isset($data['reason']) ? $data['reason'] : 'Order complete'
		];

		$resp = $this->client->setupAmazonCall('CloseOrderReference', $params);

		return [
			'details' => $resp['CloseOrderReferenceResult'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function cancelOrder($data)
	{
		$this->requiredData($data, ['referenceId']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
			'CancelationReason' => isset($data['reason']) ? $data['reason'] : null
		];

		$resp = $this->client->setupAmazonCall('CancelOrderReference', $params);

		return [
			'details' => $resp['CancelOrderReferenceResult'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	public function refund($data)
	{
		$this->requiredData($data, ['referenceId', 'amount']);

		$params = [
			'AmazonCaptureId' => $data['referenceId'],
			'RefundReferenceId' => $this->generateReferenceId($data['referenceId'], 'R'), // accept custom refund id???
			'RefundAmount.Amount' => $data['amount'],
			'RefundAmount.CurrencyCode' => 'USD',
			'SellerRefundNote' => isset($data['reason']) ? $data['reason'] : null
		];

		if (isset($data['statementName']) || $this->statementName) {
			$params['SoftDescriptor'] = isset($data['statementName']) ? $data['statementName'] : $this->statementName;
		}

		$resp = $this->client->setupAmazonCall('Refund', $params);

		return [
			'details' => $resp['RefundResult']['RefundDetails'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	private function setupAuthorize($data, $capture = false)
	{
		$this->requiredData($data, ['referenceId', 'amount']);

		$params = [
			'AmazonOrderReferenceId' => $data['referenceId'],
			'AuthorizationReferenceId' => $this->generateReferenceId($data['referenceId'], 'A'),
			'AuthorizationAmount.Amount' => $data['amount'],
			'AuthorizationAmount.CurrencyCode' => 'USD',
			'TransactionTimeout' => 0
		];

		if (isset($data['note'])) {
			$params['SellerAuthorizationNote'] = $data['note'];
		}

		if ($capture === true) {
			$params['CaptureNow'] = true;

			if (isset($data['statementName']) || $this->statementName) {
				$params['SoftDescriptor'] = isset($data['statementName']) ? $data['statementName'] : $this->statementName;
			}
		}

		$resp = $this->client->setupAmazonCall('Authorize', $params);

		return [
			'details' => $resp['AuthorizeResult']['AuthorizationDetails'],
			'requestId' => $resp['ResponseMetadata']['RequestId']
		];
	}

	private function generateReferenceId($referenceId, $append)
	{
		return str_replace('-', '', $referenceId) . $append . mt_rand(100000, 999999);
	}

	public function login($accessToken)
	{
		$data = $this->client->getData('https://api.amazon.com/auth/o2/tokeninfo?access_token='. urlencode($accessToken), [
			'exceptions' => false
		]);

		if (isset($data['error'])) {
			throw new Exceptions\AmazonLoginException($data['error'], $data['error_description']);
		}

		if ($data['aud'] !== $this->config['client_id']) {
			throw new Exceptions\AmazonLoginException('invalid_access_token', 'Access token does not belong to this seller.');
		}

		return $data;
	}

	public function getLoginDetails($accessToken)
	{
		if ($this->config['sandbox_mode'] === true) {
			$script = 'https://api.sandbox.amazon.com/user/profile';
		} else {
			$script = 'https://api.amazon.com/user/profile';
		}

		$resp = $this->client->connect->get($script, [
			'exceptions' => false,
			'headers' => [
				'Authorization' => 'bearer '. $accessToken
			]
		]);

		$data = $resp->json();

		if (isset($data['error'])) {
			throw new Exceptions\AmazonLoginException($data['error'], $data['error_description']);
		}

		return $data;
	}

	public function script()
	{
		if ($this->config['sandbox_mode'] === true) {
			$script = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js?sellerId=';
		} else {
			$script = 'https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js?sellerId=';
		}

		return $script . $this->config['seller_id'];
	}

	private function requiredData($data, $args)
	{
		$missing = [];
		foreach ($args as $arg) {
			if (! isset($data[$arg]) || (isset($data[$arg]) && ! $data[$arg])) {
				$missing[] = $arg;
			}
		}

		if ($missing) {
			$plural = count($missing) > 1 ? 'parameters' : 'parameter';
			throw new Exceptions\InvalidDataException('Missing '. implode(', ', $missing) .' '. $plural .' from array');
		}
	}

}