<?php namespace Tuurbo\AmazonPayment\Exceptions;

use Exception;

class AmazonLoginException extends Exception
{
	private $_type;
	private $_requestId;

	function __construct($type, $message, $statusCode = null, $requestId = null)
	{
		parent::__construct($message, $statusCode);

		$this->_type = $type;
		$this->_requestId = $requestId;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function getRequestId()
	{
		return $this->_requestId;
	}

}
