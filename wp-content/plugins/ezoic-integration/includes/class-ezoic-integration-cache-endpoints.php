<?php

namespace {

include_once 'include-functions.php';
}

namespace Ezoic_Namespace {

class Ezoic_Integration_Cache_Endpoints implements iEzoic_Integration_Endpoints {
	private $endpoints;
	private $cache_time;
	private $request_url;
	private $current_endpoint;
	private $protocol;
	private $file_path;
	private $ip;

	public function __construct() {
		$this->file_path = dirname( __FILE__ ) . "/endpoints/cache.json";

		//Cache endpoints for 24hours
		$this->cache_time = 86400;

		$this->protocol    = isset( $_SERVER["HTTPS"] ) ? 'https' : 'http';
		$this->request_url = EZOIC_GATEWAY_URL . "/wp/endpoints.go";
		$this->ip          = "";

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$this->ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}
	}

	public function bust_endpoint_cache() {
		if ( file_exists( $this->file_path ) ) {
			unlink( $this->file_path );
		}
	}

	public function is_ezoic_endpoint() {
		$host = '';
		if ( isset( $_SERVER["HTTP_HOST"] ) ) {
			$host = $_SERVER["HTTP_HOST"];
		}

		$request_uri = '';
		if ( isset( $_SERVER["REQUEST_URI"] ) ) {
			$request_uri = $_SERVER["REQUEST_URI"];
		}

		$current_url = "{$this->protocol}://" . $host . $request_uri;

		//Make sure we have our endpoints available eh?
		$this->get_endpoints();

		if ( is_array( $this->endpoints ) ) {
			foreach ( $this->endpoints as $endpoint ) {
				$matches = array();
				if ( preg_match( '/(' . preg_quote( $endpoint, '/' ) . '.*)/', $current_url, $matches ) ) {
					if ( isset( $matches[0] ) ) {
						$this->current_endpoint = str_replace( "/?", "?", $matches[0] );
					} else {
						$this->current_endpoint = $endpoint;
					}

					return true;
				}
			}
		}


		return false;
	}

	public function get_endpoint_asset() {
		$response = $this->curl_request_endpoints_from_ezoic();

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			$headers = $response['headers'];
			if ( ! empty( $headers ) ) {
				status_header( 200 );
				if ( is_array( $headers ) || is_object( $headers ) ) {
					foreach ( $headers as $key => $header ) {
						$this->handle_header_object( $key, $header );
					}
				} else {
					header( "Content-type: {$headers}" );
				}
			}

			//a hack to fix bad cache-control header with anchorfix.js
			$clean_endpoint = preg_replace('/\?.*/', '', trim($this->current_endpoint));
			if ($clean_endpoint == '/ezoic/anchorfix.js')
			{
				header('Cache-Control: public, max-age=86400');
			}

			return $response['body'];
		}

		return "";
	}

	private function get_endpoints() {
		$result = $this->get_endpoints_from_file();

		if ( $result == false ) {
			ob_start();
			$this->get_endpoints_from_server();
			$this->store_endpoints_to_file();
			ob_end_clean();
		}
	}

	private function get_endpoints_from_server() {
		$settings = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $this->request_url,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER     => array(
				'Referer'        => "{$this->protocol}://" . $_SERVER["HTTP_HOST"],
				'X-Forwarded-for' => $this->ip,
				'X-Wordpress-Integration' => "true"
			),
			CURLOPT_POST           => true,
			CURLOPT_HEADER         => true
		);

		$result = Ezoic_Integration_Request_Utils::make_curl_request( $settings );
		$this->endpoints = array();

		if ( $result["status_code"] != 200 ) {
			return;
		}

		$ez_data = json_decode( $result["body"] );
		if ( $ez_data->result === "true" ) {
			foreach ( $ez_data->endpoints as $endpoint ) {
				$this->endpoints[] = $endpoint;
			}
		}
	}

	private function curl_request_endpoints_from_ezoic() {
		$timeout = 5;

		$url = EZOIC_GATEWAY_URL . $this->current_endpoint;

		//set endpoint headers
		$endpoint_headers                    = $this->parse_endpoint_headers();
		$endpoint_headers['Referer']        = "{$this->protocol}://" . $_SERVER["HTTP_HOST"];
		$endpoint_headers['X-Forwarded-for'] = $this->ip;
		$endpoint_headers['X-Wordpress-Integration'] = "true";
		//unset( $endpoint_headers['Content-Type'] );

		if ( ! empty( $endpoint_headers['Cookie'] ) ) {
			$endpoint_cookies = $endpoint_headers['Cookie'];
			unset( $endpoint_headers['Cookie'] );
		}

		$out_header = array();
		foreach ( $endpoint_headers as $k => $v ) {
			$out_header[] = $k . ': ' . $v;
		}

		$settings = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER     => $out_header,
			CURLOPT_HEADER         => true,
			CURLOPT_VERBOSE        => true,
		);

		$method = strtoupper( $_SERVER['REQUEST_METHOD'] );
		if ( ! empty( $method ) ) {
			// there are not POST fields, but there is raw input
			if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
				if ( ( strpos( $_SERVER["CONTENT_TYPE"], 'application/x-www-form-urlencoded' ) === false
					&& strpos( $_SERVER["CONTENT_TYPE"], 'multipart/form-data' ) === false )
					|| $method == 'PUT' ) {
					// read and set the raw input
					$post_input = file_get_contents( 'php://input' );
				} else {
					$post_input = http_build_query( $_POST );
				}
				$settings[ CURLOPT_POSTFIELDS ] = $post_input;

			}

			$settings[ CURLOPT_CUSTOMREQUEST ] = $method;
		}

		if ( ! empty( $endpoint_cookies ) ) {
			$settings[ CURLOPT_COOKIE ] = $endpoint_cookies;
		}

		return Ezoic_Integration_Request_Utils::make_curl_request( $settings );
	}

	private function get_endpoints_from_file() {
		if ( file_exists( $this->file_path ) && is_readable( $this->file_path ) ) {
			$cache_content = file_get_contents( $this->file_path );
			$content      = json_decode( $cache_content, true );
			if ( is_array( $content ) && isset( $content["expiration_time"] ) && $content["expiration_time"] > time() && isset( $content["endpoints"] ) ) {
				if ( count( $content["endpoints"] ) == 0 ) {
					return false;
				}
				$this->endpoints = $content["endpoints"];

				return true;
			}
		}

		return false;
	}

	private function store_endpoints_to_file() {
		$cache_content = array( "expiration_time" => time() + $this->cache_time, "endpoints" => $this->endpoints );
		$fileContents = json_encode( $cache_content );

		if ( ( $handle = fopen( $this->file_path, 'w' ) ) !== false ) {
			fwrite( $handle, $fileContents );
			fclose( $handle );
		}
	}

	private function parse_endpoint_headers() {
		$headers = getallheaders();

		if ( is_array( $headers ) ) {
			foreach ( $headers as $key => $header ) {
				// remove headers that should not be passed through
				if ( $this->is_bad_header( $key ) ) {
					unset( $headers[ $key ] );
				}
			}
		}

		return $headers;
	}

	private function is_bad_header( $key ) {
		return ( $key == 'Accept-Encoding'
			|| $key == 'accept-encoding'
			|| $key == 'Connection'
			|| $key == 'connection'
			|| $key == 'Range'
			|| $key == 'range'
			|| $key == 'Host'
			|| $key == 'host'
			|| $key == 'cache-control'
			|| $key == 'Cache-Control' );
	}

	private function handle_header_object( $key, $header ) {
		if ( is_array( $header ) ) {
			foreach ( $header as $subheader ) {
				header( $key . ': ' . $subheader, false );
			}
		} else {
			header( $key . ': ' . $header );
		}
	}

}

}
