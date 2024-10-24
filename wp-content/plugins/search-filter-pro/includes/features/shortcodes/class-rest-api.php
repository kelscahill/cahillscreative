<?php
namespace Search_Filter_Pro\Features\Shortcodes;

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

	/**
	 * Init the rest api.
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
			'search-filter/v1',
			'/settings/results-shortcode',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_results_shortcode' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Get the results shortcode.
	 *
	 * @param \WP_REST_Request $request The request.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_results_shortcode( \WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		$info_type = 'code';
		if ( $id === 0 ) {
			$info_type = 'text';
			$shortcode = __( 'Save this query to generate the shortcode.', 'search-filter-pro' );
		} else {
			$shortcode = sprintf( '[searchandfilter query="%d" action="show-results"]', $id );
		}

		// TODO - reformat response, don't use `data` props, just return
		// the data or send an error.
		$json = array(
			'data' => array(
				'type'  => $info_type,
				'label' => $shortcode,
			),
		);
		return rest_ensure_response( $json );
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
}
