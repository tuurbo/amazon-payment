<?php namespace Tuurbo\AmazonPayment;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\RequestException;
use GuzzleHttp\Exception\ConnectException;

class GuzzleRetryClient {

    private $retries;
    private $delayMS;

    function __construct($retries = 3, $delayMS = 500)
	{
        $this->retries = $retries;
        $this->delayMS = $delayMS;
	}

	public function create()
	{
        if ($this->retries == 0) {
            return new Client();
        }

		$handlerStack = HandlerStack::create(new CurlHandler());
		$handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

		return new Client(['handler' => $handlerStack]);
	}

	private function retryDecider()
	{
		return function (
			$retries,
			Request $request,
			Response $response = null,
			RequestException $exception = null
		) {
			// Limit the number of retries
			if ($retries >= $this->retries) {
				return false;
			}

			// Retry connection exceptions
			if ($exception instanceof ConnectException) {
				return true;
			}

			if ($response) {
				// Retry on server errors
				if ($response->getStatusCode() >= 500) {
					return true;
				}
			}

			return false;
		};
	}

	private function retryDelay()
	{
		return function ($numberOfRetries) {
			return $this->delayMS * $numberOfRetries;
		};
	}

}
