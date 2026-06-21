<?php
defined('_JEXEC') or die('Restricted access');

/**
 * Lightweight Pin Payments REST API client.
 * Replaces the bundled omnipay/pin + Guzzle 3 stack, which is incompatible
 * with PHP 8 (Guzzle 3 expects curl_init() to return a resource), with direct
 * curl calls to the Pin Payments charges API.
 *
 * @link https://pinpayments.com/developers/api-reference
 */
class PinPaymentsAPI
{
	/** @var string API base URL including the version segment */
	protected $baseUrl = '';

	/** @var string Secret API key, sent as the HTTP basic auth username */
	protected $secretKey = '';

	/** @var bool Log requests/responses to the HikaShop log */
	protected $debug = false;

	/**
	 * @param string $secretKey Pin Payments secret API key
	 * @param bool $sandbox Use the test environment
	 * @param bool $debug Log requests and responses
	 */
	public function __construct($secretKey, $sandbox = false, $debug = false)
	{
		$this->secretKey = $secretKey;
		$this->baseUrl = $sandbox ? 'https://test-api.pinpayments.com/1' : 'https://api.pinpayments.com/1';
		$this->debug = $debug;
	}

	/**
	 * Create a charge.
	 *
	 * @param array $data Charge fields (amount, currency, description, card_token, ...)
	 * @return object|null Decoded JSON response
	 * @throws Exception on curl error
	 */
	public function createCharge($data)
	{
		return $this->request('POST', '/charges', $data);
	}

	/**
	 * Make an authenticated, form-encoded request to the Pin Payments API.
	 * Authentication is HTTP basic with the secret key as the username and a
	 * blank password.
	 *
	 * @param string $method HTTP method
	 * @param string $endpoint Endpoint path (e.g. /charges)
	 * @param array|null $data Body fields, form-encoded for POST/PUT/PATCH
	 * @return object|null Decoded JSON response
	 * @throws Exception on curl error
	 */
	public function request($method, $endpoint, $data = null)
	{
		$headers = array(
			'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
		);

		$body = null;
		if ($data !== null) {
			$body = http_build_query($data);
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
		curl_setopt($ch, CURLOPT_CAPATH, __DIR__ . '/cacert.pem');

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			if ($body !== null)
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		} elseif ($method !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if ($body !== null)
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		if ($this->debug && function_exists('hikashop_writeToLog'))
			hikashop_writeToLog('Pin Payments API ' . $method . ' ' . $this->baseUrl . $endpoint . ($body ? "\n" . $body : ''));

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);

		if ($curlError !== '')
			throw new Exception('Pin Payments API curl error: ' . $curlError);

		if ($this->debug && function_exists('hikashop_writeToLog'))
			hikashop_writeToLog('Pin Payments API response (' . $httpCode . '): ' . $response);

		if ($response === '' || $response === false)
			return null;

		return json_decode($response);
	}
}
