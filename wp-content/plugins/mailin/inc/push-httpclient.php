<?php

use WonderPush\Net\Request;
use WonderPush\Net\Response;

/**
 * An implementation of \WonderPush\Net\HttpClientInterface that uses the Curl shipped with WordPress
 */

class SIB_Push_HttpClient implements \WonderPush\Net\HttpClientInterface {

	/** @var WP_Http */
	var $http;
	/** @var \WonderPush\WonderPush */
	var $wp;
	public function __construct(\WonderPush\WonderPush $wonderPush) {
		$this->http = new WP_Http();
		$this->wp = $wonderPush;
	}

	public function execute(Request $request) {
		// Construct absolute URL
		$root = $request->getRoot() ?: $this->wp->getApiRoot();
		$path = $request->getPath();
		if (!\WonderPush\Util\StringUtil::beginsWith($path, '/')) {
			$path = '/' . $path;
		}
		$url = $root . $path;
		$qsParams = $request->getQsParams();
		$headers = $request->getHeaders();
		$body = null;

		// Construct $qsParams and $body, and honors $request->getParams() too
		switch ($request->getMethod()) {
			case Request::GET:
			case Request::DELETE:
				$qsParams = array_merge($qsParams, $request->getParams());
				break;
			case Request::PUT:
			case Request::POST:
			case Request::PATCH:
				$body = $request->getParams();
				$files = $request->getFiles();
				if (count($files)) {
					$body = $request->getParams() ?: array();
					foreach ($files as $name => $file) {
						$body[$name] = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
					}
				} else if (empty($body)) {
					$body = null;
				} else {
					$headers['Content-Type'] = 'application/json';
					$options = 0;
					if (defined('JSON_UNESCAPED_SLASHES')) {
						$options |= JSON_UNESCAPED_SLASHES;
					}
					if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
						$options |= JSON_INVALID_UTF8_SUBSTITUTE;
					} else if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
						$options |= JSON_PARTIAL_OUTPUT_ON_ERROR;
					}
					$body = json_encode($body, $options);
				}
				break;
		}

		// Incorporate query string into URL
		if (!empty($qsParams)) {
			$prevQs = \WonderPush\Util\UrlUtil::parseQueryString(parse_url($url, PHP_URL_QUERY));
			$qsParams = array_merge($prevQs, $qsParams);
			$url = \WonderPush\Util\UrlUtil::replaceQueryStringInUrl($url, $qsParams);
		}

		if (!isset($headers['User-Agent'])) {
			$curlVersion = array();
			if (function_exists("curl_version")) {
				$curlVersion = curl_version();
			}
			$headers['User-Agent'] = 'BrevoPushApi/' . \WonderPush\WonderPush::API_VERSION
				. ' WonderPushPhpLib/' . \WonderPush\WonderPush::VERSION
				. ' curl/' . \WonderPush\Util\ArrayUtil::getIfSet($curlVersion, 'version', 'na')
				. ' ' . \WonderPush\Util\ArrayUtil::getIfSet($curlVersion, 'ssl_version', 'curlssl/na')
			;
		}

		$rawResponse = $this->http->request($url, array(
			'method' => $request->getMethod() ? strtoupper($request->getMethod()) : 'GET',
			'headers' => $headers,
			'body' => $body,
		));

		// Parse response
		$response = new Response();
		$response->setRequest($request);

		if (is_wp_error($rawResponse)) {
			$response->setStatusCode(0);
		} else {
			$response->setStatusCode(
				isset($rawResponse['response'])
				&& is_array($rawResponse['response'])
				&& isset($rawResponse['response']['code'])
					? $rawResponse['response']['code'] : 0
			);
			$response->setRawBody(isset($rawResponse['body']) ? $rawResponse['body'] : null);
			$responseHeaders = array();
			// Make a copy of the headers, which is not a regular array but a Requests_Utility_CaseInsensitiveDictionary
			if (isset($rawResponse['headers'])) {
				foreach ($rawResponse['headers'] as $key => $val) {
					$responseHeaders[$key] = $val;
				}
			}
			$response->setHeaders($responseHeaders);
		}
		return $response;
	}

}
