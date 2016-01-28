<?php namespace Tuurbo\AmazonPayment;

use Tuurbo\AmazonPayment\Exceptions;
use GuzzleHttp\ClientInterface;

class AmazonPaymentClient {

	const SERVICE_VERSION = '2013-01-01';

	private $sellerId;
	private $accessKey;
	private $secretKey;
	private $signatureVersion = 2;
	private $signatureMethod = 'HmacSHA256';
	private $serviceUrl;

	public $connect;

	function __construct(ClientInterface $client, $config)
	{
		$this->connect = $client;
		$this->sellerId = $config['seller_id'];
		$this->accessKey = $config['access_key'];
		$this->secretKey = $config['secret_key'];

		if ($config['sandbox_mode'] === true) {
			$this->serviceUrl = 'https://mws.amazonservices.com/OffAmazonPayments_Sandbox/2013-01-01';
		} else {
			$this->serviceUrl = 'https://mws.amazonservices.com/OffAmazonPayments/2013-01-01';
		}
	}

	public function setupAmazonCall($actionName, array $parameters)
	{
		$parameters = $this->addRequiredParameters([
			'SellerId' => $this->sellerId,
			'Action' => $actionName
		] + $parameters);

		$response = $this->postToAmazon($parameters);

		return $response['data'];
	}

	private function postToAmazon(array $params)
	{
		$resp = $this->connect->post($this->serviceUrl, ['exceptions' => false, 'form_params' => $params]);
		$statusCode = (int) $resp->getStatusCode();

		if ($statusCode === 500 || $statusCode === 503) {
			throw new Exceptions\AmazonPaymentException(null, 'Internal Server Error', $statusCode);
		}

		$resp = simplexml_load_string($resp->getBody());
		$resp = json_decode(json_encode($resp), true);

		if (isset($resp['Error']) && $resp['Error']['Code']) {
			$requestId = isset($resp['RequestId']) ? $resp['RequestId'] : null;
			$this->getException($resp['Error']['Code'], $resp['Error']['Message'], $statusCode, $requestId);
		}

		return [
			'status' => $statusCode,
			'data' => $resp
		];
	}

	public function getException($amazonCode, $message, $statusCode, $requestId = null)
	{
		$customExceptionName = 'Tuurbo\AmazonPayment\Exceptions\\'. $this->exceptionAlias($amazonCode) .'Exception';

		if (class_exists($customExceptionName)) {
			throw new $customExceptionName($message, $statusCode);
		} else {
			throw new Exceptions\AmazonPaymentException($amazonCode, $message, $statusCode, $requestId);
		}
	}

	public function exceptionAlias($amazonCode)
	{
		$codes = [
			'InvalidAddress' => 'InvalidActionCode'
		];

		return isset($codes[$amazonCode]) ? $codes[$amazonCode] : $amazonCode;
	}

	private function addRequiredParameters(array $params)
	{
		$params['AWSAccessKeyId'] = $this->accessKey;
		$params['Timestamp'] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
		$params['Version'] = self::SERVICE_VERSION;
		$params['SignatureVersion'] = $this->signatureVersion;
		$params['SignatureMethod'] = $this->signatureMethod;
		$params['Signature'] = $this->signParameters($params);

		return $params;
	}

	private function signParameters(array $params)
	{
		$stringToSign = $this->stringToSignatureV2($params);

		return $this->signSignature($stringToSign, $this->secretKey);
	}

	private function stringToSignatureV2(array $params)
	{
		$data = 'POST';
		$data .= "\n";
		$endpoint = parse_url($this->serviceUrl);
		$data .= $endpoint['host'];
		$data .= "\n";
		$uri = isset($endpoint['path']) ? $endpoint['path'] : null;
		if (!isset ($uri)) {
			$uri = "/";
		}
		$uriencoded = implode("/", array_map([$this, "urlEncoder"], explode("/", $uri)));
		$data .= $uriencoded;
		$data .= "\n";
		uksort($params, 'strcmp');
		$data .= $this->getParametersAsString($params);

		return $data;
	}

	private function urlEncoder($value)
	{
		return str_replace('%7E', '~', rawurlencode($value));
	}

	private function getParametersAsString(array $params)
	{
		$queryParams = [];
		foreach ($params as $key => $value) {
			$queryParams[] = $key .'='. $this->urlEncoder($value);
		}

		return implode('&', $queryParams);
	}

	private function signSignature($data, $key)
	{
		return base64_encode(
			hash_hmac('sha256', $data, $key, true)
		);
	}

	public function getData($url, $params)
	{
		$resp = $this->connect->get($url, $params)->getBody();

		return json_decode($resp, true);
	}

}
