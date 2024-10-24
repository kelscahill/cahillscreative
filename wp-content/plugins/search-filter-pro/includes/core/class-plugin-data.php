<?php
/**
 * Update the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A stripped back version the Plugin_Update class, without
 * caching, or any hooks - it simply generates the api request
 * and returns the response.
 *
 * The purpose of this is to get the download URL for initial install
 * of a plugin.  In this case, the Search & Filter free plugin.
 *
 * @author
 * @version 3.0.0
 */
class Plugin_Data {

	/**
	 * The API URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * The API data.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $api_data = array();

	/**
	 * The health check timeout.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private $health_check_timeout = 5;

	/**
	 * The beta version.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private $beta = false;

	/**
	 * Class constructor.
	 *
	 * @uses plugin_basename()
	 * @uses hook()
	 *
	 * @param string $_api_url     The URL pointing to the custom API endpoint.
	 * @param array  $_api_data    Optional data to send with API calls.
	 */
	public function __construct( $_api_url, $_api_data = null ) {

		$this->api_url  = trailingslashit( $_api_url );
		$this->api_data = $_api_data;
		$this->beta     = ! empty( $this->api_data['beta'] ) ? true : false;

	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses api_request()
	 *
	 * @return object Modified update array with custom plugin data.
	 */
	public function get() {

		$plugin_data = new \stdClass();

		$version_info = $this->api_request(
			'plugin_latest_version',
			array(
				'beta' => $this->beta,
			)
		);

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
			$plugin_data = $version_info;
			// Make sure the plugin property is set to the plugin's name/location. See issue 1463 on Software Licensing's GitHub repo.
		}

		return $plugin_data;
	}


	/**
	 * Calls the API and, if successfull, returns the object delivered by the API.
	 *
	 * @uses get_bloginfo()
	 * @uses wp_remote_post()
	 * @uses is_wp_error()
	 *
	 * @param string $_action The requested action.
	 * @param array  $_data   Parameters for the API action.
	 * @return false|object
	 */
	private function api_request( $_action, $_data ) {

		global $wp_version, $edd_plugin_url_available;

		$verify_ssl = $this->verify_ssl();

		// Do a quick status check on this domain if we haven't already checked it.
		$store_hash = md5( $this->api_url );
		if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
			$test_url_parts = parse_url( $this->api_url );

			$scheme = ! empty( $test_url_parts['scheme'] ) ? $test_url_parts['scheme'] : 'http';
			$host   = ! empty( $test_url_parts['host'] ) ? $test_url_parts['host'] : '';
			$port   = ! empty( $test_url_parts['port'] ) ? ':' . $test_url_parts['port'] : '';

			if ( empty( $host ) ) {
				$edd_plugin_url_available[ $store_hash ] = false;
			} else {
				$test_url                                = $scheme . '://' . $host . $port;
				$response                                = wp_remote_get(
					$test_url,
					array(
						'timeout'   => $this->health_check_timeout,
						'sslverify' => $verify_ssl,
					)
				);
				$edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
			}
		}

		if ( false === $edd_plugin_url_available[ $store_hash ] ) {
			return;
		}

		$data = array_merge( $this->api_data, $_data );

		if ( $this->api_url == trailingslashit( home_url() ) ) {
			return false; // Don't allow a plugin to ping itself.
		}

		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => ! empty( $data['license'] ) ? $data['license'] : '',
			'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
			'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
			'version'    => isset( $data['version'] ) ? $data['version'] : false,
			'author'     => $data['author'],
			'url'        => home_url(),
			'beta'       => ! empty( $data['beta'] ),
		);

		$request = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => $verify_ssl,
				'body'      => $api_params,
			)
		);

		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );
		}

		if ( $request && isset( $request->sections ) ) {
			$request->sections = maybe_unserialize( $request->sections );
		} else {
			$request = false;
		}

		if ( $request && isset( $request->banners ) ) {
			$request->banners = maybe_unserialize( $request->banners );
		}

		if ( $request && isset( $request->icons ) ) {
			$request->icons = maybe_unserialize( $request->icons );
		}

		if ( ! empty( $request->sections ) ) {
			foreach ( $request->sections as $key => $section ) {
				$request->$key = (array) $section;
			}
		}

		return $request;
	}

	/**
	 * Returns if the SSL of the store should be verified.
	 *
	 * @since  1.6.13
	 * @return bool
	 */
	private function verify_ssl() {
		return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
	}
}
