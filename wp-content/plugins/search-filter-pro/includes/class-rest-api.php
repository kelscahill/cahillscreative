<?php
/**
 * The file that defines the core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter_Pro\License\Rest_API as License_Rest_API;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Interface for settign up rest api routes.
 */
class Rest_API {

	/**
	 * Attach main hook.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_routes' ) );
		License_Rest_API::init();
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		register_rest_route(
			'search-filter-pro/v1',
			'/data/meta_keys',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_meta_keys' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		// Extend the search-filter/v1 settings routes to add author roles and capabilities.

		register_rest_route(
			'search-filter/v1',
			'/settings/options/post-authors',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_post_authors_options' ),
				'permission_callback' => array( $this, 'permissions' ),
				'args'                => array(
					'queryId' => array(
						'type'              => 'number',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
				),
				'allow_batch'         => true,
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/options/post-author-roles',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_author_roles_options' ),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/options/post-author-capabilities',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_author_capabilities_options' ),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);
	}

	/**
	 * Get all post meta keys.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $search_term    The search term to filter the keys by.
	 * @return   array                  The array of post meta keys.
	 */
	public static function get_all_post_meta_keys( $search_term = '' ) {

		global $wpdb;
		$data = array();

		$where = '';
		if ( $search_term !== '' ) {
			$where = $wpdb->prepare( " WHERE meta_key LIKE '%s' ", $search_term . '%' );
		}

		$query = $wpdb->query(
			"
			SELECT DISTINCT(BINARY `meta_key`) as meta_key_binary, `meta_key`
			FROM $wpdb->postmeta
			$where
			ORDER BY `meta_key` ASC
			LIMIT 0, 15
		"
		);

		foreach ( $wpdb->last_result as $k => $v ) {
			$data[] = $v->meta_key;
		}

		return $data;
	}

	/**
	 * Get all unique custom field keys
	 *
	 * @param \WP_REST_Request $request   The request object.
	 * @return \WP_REST_Response|WP_Error The response object.
	 */
	public function get_meta_keys( \WP_REST_Request $request ) {
		$search_term  = '';
		$query_params = $request->get_params();

		if ( isset( $query_params['search'] ) ) {
			$search_term = stripslashes_deep( $query_params['search'] );
		}
		$result = self::get_all_post_meta_keys( $search_term );

		return rest_ensure_response( $result );
	}

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions() {
		// TODO - need to create proper roles.
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the available post authors based on the current query settings.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_post_authors_options( WP_REST_Request $request ) {
		// Post stati are generic (not assigned to post types etc), so get them all.
		$json = array(
			'options' => Helpers::get_post_authors(),
		);

		return rest_ensure_response( $json );
	}
	/**
	 * Get the available post author roles.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_post_author_roles_options( WP_REST_Request $request ) {
		$json = array(
			'options' => Helpers::get_user_roles(),
		);

		return rest_ensure_response( $json );
	}
	/**
	 * Get the available post author capabilities.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_post_author_capabilities_options( WP_REST_Request $request ) {
		$json = array(
			'options' => Helpers::get_user_capabilities(),
		);

		return rest_ensure_response( $json );
	}
}
