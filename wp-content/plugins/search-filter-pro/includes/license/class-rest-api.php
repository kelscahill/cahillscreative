<?php
namespace Search_Filter_Pro\License;

use Search_Filter\Options;
use Search_Filter_Pro\Core\License_Server;
use Search_Filter_Pro\Util;
use WP_REST_Response;
use WP_Error;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the license dashboard widget REST API endpoints.
 *
 * @since 3.0.0
 */
class Rest_API {

	const PLUGIN_ITEM_ID   = 526297;
	/**
	 * Init the cron class.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
	}

	/**
	 * Add rest routes.
	 *
	 * @since    3.0.0
	 */
	public static function add_routes() {

		register_rest_route(
			'search-filter-pro/v1',
			'/license',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_status' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/license/connect',
			array(
				'args' => array(
					'license' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'connect' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/disconnect',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'disconnect' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/license/refresh',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'refresh' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/license/test-connection',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'test_connection' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);
	}

	/**
	 * Get the indexer status.
	 *
	 * @since    3.0.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_status() {
		$license_data = License_Server::get_license_data();
		if ( self::is_license_key_connected( $license_data ) ) {
			$license_data['license'] = self::obfuscate_license_key( $license_data['license'] );
		}
		return rest_ensure_response( $license_data );
	}

	

	/**
	 * Check whether the license data object seems to be connected.
	 *
	 * @param array $license_data The license data assoc array.
	 * @return bool
	 */
	private static function is_license_key_connected( $license_data ) {
		if ( ! isset( $license_data['status'] ) ) {
			return false;
		}
		if ( empty( $license_data['status'] ) ) {
			return false;
		}
		if ( ! isset( $license_data['license'] ) ) {
			return false;
		}
		if ( empty( $license_data['license'] ) ) {
			return false;
		}

		$status = $license_data['status'];
		if ( $status === 'valid' ) {
			return true;
		}
		return false;
	}
	/**
	 * Connect the license.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function connect( \WP_REST_Request $request ) {

		$license = $request->get_param( 'license' );

		$license_data = array(
			'status'       => '',
			'expires'      => '',
			'license'      => $license,
			'error'        => '',
			'errorMessage' => '',
		);

		// License data to send.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => self::PLUGIN_ITEM_ID,
			'url'        => home_url(),
			'info'       => License_Server::get_site_info(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			License_Server::get_endpoint(),
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// Make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			$license_data['errorMessage'] = __( "Couldn't retreive license information - `wp_remote_post` failed.", 'search-filter-pro' );
			return rest_ensure_response( $license_data );
		}

		// Get the response body.
		$response_license_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_license_body ) ) {
			$license_data['errorMessage'] = __( "Couldn't retreive license information - empty response.", 'search-filter-pro' );
			return rest_ensure_response( $license_data );
		}

		// Decode the license data.
		$remote_license_data = json_decode( $response_license_body );
		// $remote_license_data->license will be either "valid" or "invalid"
		if ( property_exists( $remote_license_data, 'license' ) ) {

			$remote_license_status  = $remote_license_data->license;
			$remote_license_error   = property_exists( $remote_license_data, 'error' ) ? $remote_license_data->error : '';
			$license_data['status'] = $remote_license_status;

			if ( property_exists( $remote_license_data, 'expires' ) ) {
				$license_data['expires'] = $remote_license_data->expires;
			}

			if ( $remote_license_status === 'invalid' ) {
				$license_data['error'] = $remote_license_data->error;
			}

			Options::update_option_value( 'license-data', $license_data );

			if ( $remote_license_status === 'valid' ) {
				// Once we have a valid license key and it's activated
				// Only obfuscate if it's valid, or expired.
				$license_data['license'] = self::obfuscate_license_key( $license_data['license'] );
			}
		} else {
			$license_data['errorMessage'] = __( "Couldn't retreive license information.", 'search-filter-pro' );
		}
		return rest_ensure_response( $license_data );
	}

	/**
	 * Disconnect the license.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function disconnect( \WP_REST_Request $request ) {

		$license_data = License_Server::get_license_data();
		$license      = $license_data['license'];
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id'    => self::PLUGIN_ITEM_ID,
			'url'        => home_url(),
			'info'       => License_Server::get_site_info(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			License_Server::get_endpoint(),
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			$license_data['errorMessage'] = __( "Couldn't disconnect license - `wp_remote_post` failed.", 'search-filter-pro' );
			return rest_ensure_response( $license_data );
		}

		// Decode the license data.
		$remote_license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed".
		if ( $remote_license_data->license === 'deactivated' || $remote_license_data->license === 'failed' ) {
			$license_data = array(
				'status'       => '',
				'expires'      => '',
				'license'      => '', // Remove the license.
				'error'        => '',
				'errorMessage' => '',
			);
			Options::update_option_value( 'license-data', $license_data );

			// Now update the status to disconnected in the response.
			$license_data['status'] = 'disconnected';
		} else {
			$license_data['errorMessage'] = __( "Couldn't disconnect license.", 'search-filter-pro' );
			$license_data['license']      = self::obfuscate_license_key( $license );
		}
		return rest_ensure_response( $license_data );
	}

	/**
	 * Refresh the license status.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function refresh( \WP_REST_Request $request ) {
		$license_data = License_Server::get_license_data();
		if ( self::is_license_key_connected( $license_data ) ) {
			$license_data['license'] = self::obfuscate_license_key( $license_data['license'] );
		}
		return rest_ensure_response( $license_data );
	}

	/**
	 * Check if the user has the permissions to access the settings.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if the user has the permissions.
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Hide the license key be converting all but the last 4
	 * characters to an asterix.
	 */
	private static function obfuscate_license_key( $key ) {
		$length = strlen( $key );
		if ( $length <= 4 ) {
			// Bad key, just return it.
			return $key;
		}

		$obfuscated_key = '************' . substr( $key, $length - 4 );
		return $obfuscated_key;
	}

	/**
	 * Test the license server connection.
	 *
	 * @since 3.0.0
	 */
	public static function test_connection() {
		$result = License_Server::check_server_health();
		return rest_ensure_response( $result );
	}
}
