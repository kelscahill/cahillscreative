<?php
namespace Search_Filter_Pro\Task_Runner;

use Search_Filter_Pro\Task_Runner;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the indexer dashboard widget REST API endpoints.
 *
 * @since 3.0.0
 */
class Rest_API {

	/**
	 * Init the cron class.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Don't add the hook if it's already added.
		if ( has_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) ) ) {
			return;
		}

		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
	}

	/**
	 * Add rest routes.
	 *
	 * @since    3.0.0
	 */
	public static function add_routes() {
		// Add test for background processing.
		register_rest_route(
			'search-filter-pro/v1',
			'/task-runner/endpoint',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'test_endpoint' ),
					'permission_callback' => '__return_true',
				),
			)
		);
		// Add test for background processing.
		register_rest_route(
			'search-filter-pro/v1',
			'/task-runner/test-connection',
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

	public static function test_endpoint() {
		return rest_ensure_response( "1" );
	}

	public static function test_connection() {
		$result = Task_Runner::test_background_process();
		return rest_ensure_response( $result );
	}
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}
}
