<?php
/**
 * Rest API for WooCommerce admin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.4
 *
 * @package    Search_Filter
 */

namespace Search_Filter\Integrations\WooCommerce;

use Search_Filter\Integrations\WooCommerce;
use Search_Filter\Settings;
use WP_REST_Request;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for settign up rest api routes.
 */
class Rest_API {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
	}
	/**
	 * Add rest routes for interacting with the settings data.
	 *
	 * @since 3.0.0
	 */
	public static function add_routes() {
		register_rest_route(
			'search-filter/v1',
			'/settings/options/woocommerce/taxonomy-terms',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_woocommerce_terms_options' ),
				'args'                => array(
					'dataWoocommerce' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( __CLASS__, 'permissions' ),
			)
		);
	}
	/**
	 * Get the available taxonomy terms for a particular taxonomy.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_woocommerce_terms_options( \WP_REST_Request $request ) {
		$data_source = $request->get_param( 'dataWoocommerce' );

		$taxonomy = WooCommerce::get_taxonomy_name_from_data_source( $data_source );

		if ( empty( $taxonomy ) ) {
			return rest_ensure_response( array( 'options' => array() ) );
		}

		$options = Settings::create_taxonomy_terms_options( $taxonomy );
		$json    = array(
			'options' => $options,
		);

		return rest_ensure_response( $json );
	}
	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}
}
