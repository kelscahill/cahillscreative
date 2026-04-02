<?php
/**
 * Handles license server endpoint selection and health checks.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

use Search_Filter\Integrations;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles license server availability checks and endpoint selection.
 */
class License_Server {

	/**
	 * License server endpoints
	 */
	const SERVER_ENDPOINTS = array(
		'license' => 'https://license.searchandfilter.com',
		'main'    => 'https://searchandfilter.com',
	);

	/**
	 * The option name for storing the server test results.
	 */
	const OPTION_TEST_RESULTS = 'license-server-test';

	/**
	 * Initialize the license server checks.
	 *
	 * Attaches to the base plugin's centralized maintenance cron.
	 */
	public static function init() {
		// Attach to the centralized maintenance cron (from base plugin).
		add_action( 'search-filter/cron/maintenance', array( __CLASS__, 'schedule_check_server_health' ) );

		// Add notices when there are errors with connecting to the servers.
		add_action( 'admin_init', array( __CLASS__, 'add_notices' ) );

		// Add the connection info to the admin data.
		add_filter( 'search-filter/rest-api/get_admin_data', array( __CLASS__, 'get_admin_data' ) );
	}

	/**
	 * Get the preferred server endpoint.
	 *
	 * @param string $preferred_server The preferred server endpoint.
	 * @return string The server endpoint URL
	 */
	public static function get_endpoint( $preferred_server = 'license' ) {
		return self::SERVER_ENDPOINTS[ $preferred_server ];
	}


	/**
	 * Hook the task into shutdown so we don't affect the request.
	 */
	public static function schedule_check_server_health() {
		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'check_server_health' ) );
	}

	/**
	 * Check the health of both servers and update the preferred endpoint.
	 */
	public static function check_server_health() {

		$license_server_healthy = self::refresh_health();

		$result = array(
			'license' => $license_server_healthy,
			'main'    => false,
		);

		// Store the results in the options table.
		\Search_Filter\Options::update( self::OPTION_TEST_RESULTS, $result );

		return $result;
	}

	/**
	 * Get the PHP version.
	 *
	 * @since 3.0.0
	 *
	 * @return string The PHP version.
	 */
	private static function get_php_version() {
		if ( function_exists( 'phpversion' ) ) {
			return phpversion();
		} elseif ( defined( 'PHP_VERSION' ) ) {
			return PHP_VERSION;
		}
		return '';
	}
	/**
	 * Get site information.
	 *
	 * @since 3.0.0
	 *
	 * @return array The site information.
	 */
	public static function get_site_info() {
		$site_meta_data = array(
			'integrations'  => Integrations::get_enabled_integrations(),
			'version'       => SEARCH_FILTER_PRO_VERSION,
			'php_version'   => self::get_php_version(),
			'wp_version'    => get_bloginfo( 'version' ),
			'site_language' => get_bloginfo( 'language' ),
			'is_multisite'  => is_multisite(),
		);
		return $site_meta_data;
	}
	/**
	 * Refresh health.
	 *
	 * @param string $preferred_server The preferred server to use.
	 */
	public static function refresh_health( $preferred_server = 'license' ) {

		$api_params = array(
			'edd_action' => 'check_license',
			'item_id'    => 526297,
			'url'        => home_url(),
			'license'    => '',
			'info'       => self::get_site_info(),
		);

		$license_data = self::get_license_data();
		if ( ! empty( $license_data['license'] ) ) {
			$api_params['license'] = $license_data['license'];
		}

		$endpoint = self::get_endpoint( $preferred_server );

		// Call the custom API.
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		$is_healthy = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

		if ( is_wp_error( $response ) ) {
			return $is_healthy;
		}

		$body             = wp_remote_retrieve_body( $response );
		$request_response = json_decode( $body, true );

		if ( ! $request_response ) {
			return $is_healthy;
		}

		if ( ! isset( $request_response['success'] ) ) {
			return $is_healthy;
		}

		if ( ! isset( $request_response['license'] ) ) {
			return $is_healthy;
		}

		$expires = isset( $request_response['expires'] ) ? $request_response['expires'] : '';
		self::update_license_data(
			array(
				'expires' => $expires,
				'status'  => $request_response['license'],
			)
		);

		return $is_healthy;
	}
	/**
	 * Get the license data from the options table.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The license data.
	 */
	public static function get_license_data() {

		$default_license_data = array(
			'status'       => '',
			'expires'      => '',
			'license'      => '',
			'error'        => '',
			'errorMessage' => '',
		);

		$license_data = Options::get( 'license-data' );

		if ( $license_data ) {
			$license_data = wp_parse_args( $license_data, $default_license_data );
		} else {
			$license_data = $default_license_data;
		}

		return $license_data;
	}

	/**
	 * Update license data.
	 *
	 * @since 3.0.0
	 *
	 * @param array $new_license_data The new license data to update.
	 */
	public static function update_license_data( $new_license_data ) {
		$existing_data        = self::get_license_data();
		$updated_license_data = wp_parse_args( $new_license_data, $existing_data );
		Options::update( 'license-data', $updated_license_data );
	}

	/**
	 * Add error notices if the license server cannot be reached.
	 */
	public static function add_notices() {

		if ( ! class_exists( '\Search_Filter\Options' ) ) {
			return;
		}

		// Show a notice to the user if there are errors with both servers.
		$test_result = \Search_Filter\Options::get( self::OPTION_TEST_RESULTS );

		// If the options are empty, then we don't have any test results yet.
		if ( empty( $test_result ) ) {
			return;
		}

		// If the license server is healthy, then we don't need to show a notice.
		if ( $test_result['license'] === false ) {
			// Add WP notice, not S&F notice:.
			add_action( 'admin_notices', array( __CLASS__, 'display_wp_admin_connection_error_notice' ) );
		}
	}


	/**
	 * Display connection error notice in WordPress admin.
	 *
	 * @since 3.0.0
	 */
	public static function display_wp_admin_connection_error_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just unsetting GET param to prevent re-display, no data modification.
		if ( isset( $_GET['activate'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing -- Just unsetting GET param to prevent re-display.
			unset( $_GET['activate'] );
		}

		$notice_string = sprintf(
			// translators: %s: Support URL.
			__( 'Unable to connect to Search & Filter update servers. Please check your internet connection or firewall settings. <a href="%1$s">Test your connection settings</a> or <a href="%2$s" target="_blank">contact support for help</a>.', 'search-filter-pro' ),
			admin_url( 'admin.php?page=search-filter' ),
			'https://searchandfilter.com/account/support/'
		);

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $notice_string ) );
	}


	/**
	 * Get admin data including connection status.
	 *
	 * @since 3.0.0
	 *
	 * @param array $admin_data The existing admin data.
	 * @return array The admin data with connection status.
	 */
	public static function get_admin_data( $admin_data ) {
		$test_result = \Search_Filter\Options::get( self::OPTION_TEST_RESULTS );
		// If the options are empty, then we don't have any test results yet.
		if ( empty( $test_result ) ) {
			return $admin_data;
		}
		$admin_data['connection'] = array(
			'license' => $test_result['license'],
			'main'    => false,
		);
		return $admin_data;
	}
}
