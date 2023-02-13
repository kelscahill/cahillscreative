<?php
namespace Ezoic_Namespace;

//Request utils are meant to handle pieces of data that can be shared between any
//Type of request being sent to ezoic. Eg. Data that can be accessed via standard
//php functions and calls.
class Ezoic_Integration_Request_Utils {

	public static function parse_response_headers( $resp_headers ) {
		$modified_headers = array();
		if( is_array($resp_headers) ) {
			foreach($resp_headers as $key => $header) {
				list($headername, $headervalue) = explode(":", $header, 2);
				$modified_headers[$headername] = $headervalue;
			}
        }

        //$modified_headers['Content-Type'] = 'text/html';

		return $modified_headers;
	}

	public static function make_curl_request($settings, $curl_init = null) {
		if ( ! empty( $curl_init ) ) {
			$curl = $curl_init;
		} else {
			$curl = curl_init();
		}

		curl_setopt_array($curl, $settings);

		// Get Ezoic modified content
		$result = curl_exec($curl);

		$result_error = "";
		// get curl errors if they exist
		if ($result === false && curl_errno($curl)) {
			$result_error = curl_error($curl);
		}

		$result_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($result, 0, $header_size);

		$finalHeaders = array();

		foreach ( explode( "\r\n", $headers ) as $i => $line ) {
			if ( $i === 0 ) {
				$finalHeaders['http_code'] = $line;
			} else {
				$header_info = explode( ': ', $line );
				if ( count( $header_info ) == 2 ) {
					if ( ! isset( $finalHeaders[ $header_info[0] ] ) ) {
						$finalHeaders[ $header_info[0] ] = array();
					}
					if ( $header_info[0] === 'http_code' ) {
						continue;
					}
					$finalHeaders[ $header_info[0] ][] = $header_info[1];
				}
			}
		}

		$body = substr($result, $header_size);

		if(empty($curl_init)) {
			curl_close($curl);
		}

		return array("body" => $body, "headers" => $finalHeaders, "status_code" => $result_http_code, "error" => $result_error);
	}

	public static function get_ezoic_server_address() {
		return EZOIC_GATEWAY_URL . "/wp/data.go";
	}

	public static function get_client_ip() {
		$ip = "";

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	public static function get_request_base_data() {
		$request_base_data = array();
		$request_base_data["request_headers"] = getallheaders();
		$resp_headers = headers_list();
		$request_base_data["response_headers"] = Ezoic_Integration_Request_Utils::parse_response_headers($resp_headers);

		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_base_data["http_method"] = $_SERVER['REQUEST_METHOD'];
		} else {
			$request_base_data["http_method"] = 'GET';
		}

		$request_base_data["ezoic_request_url"] = Ezoic_Integration_Request_Utils::get_ezoic_server_address();
		$request_base_data["client_ip"] = Ezoic_Integration_Request_Utils::get_client_ip();

		if( defined('EZOIC_API_VERSION') ) {
			$request_base_data["ezoic_api_version"] = EZOIC_API_VERSION;
		} else {
			$request_base_data["ezoic_api_version"] = '';
		}

		if ( defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
			$request_base_data["ezoic_wp_plugin_version"] = EZOIC_INTEGRATION_VERSION;
		} else {
			$request_base_data["ezoic_wp_plugin_version"] = '?';
		}

		return $request_base_data;
	}

	/**
	 * Fetches the domain and TLD from the current request URL
	 *
	 * @access private
	 * @return string
	 */
	public static function get_domain() {
		$domain = "";
		if ( function_exists( 'site_url' ) ) {
			$domain = parse_url( site_url(), PHP_URL_HOST );
		} else {
			// todo: find a backup domain parser
		}

		return $domain;
	}

	/**
	 * Check if is an AMP request
	 *
	 * @return bool
	 */
	public static function is_amp_endpoint() {
		global $wp;

		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return true;
		}

		if ( isset( $wp ) ) {
			if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
				return true;
			}
		}

		return false;
	}
}
