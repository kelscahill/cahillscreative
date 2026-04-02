<?php
/**
 * Upgrader REST API
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API endpoints for the upgrader.
 */
class Rest_API {

	/**
	 * Initialize the REST API.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function add_routes() {
		register_rest_route(
			'search-filter-pro/v1',
			'/upgrader/retry',
			array(
				'args' => array(
					'version' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'retry_upgrade' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);
	}

	/**
	 * Check permissions for upgrader endpoints.
	 *
	 * @return bool
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Clear upgrade status to allow retry.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public static function retry_upgrade( \WP_REST_Request $request ) {
		$version = $request->get_param( 'version' );
		Upgrade_Status::clear( $version );
		return rest_ensure_response( array( 'success' => true ) );
	}
}
