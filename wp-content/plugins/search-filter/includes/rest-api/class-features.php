<?php
/**
 * Description of class
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Rest_API;

use Search_Filter\Core\WP_Data;
use Search_Filter\Features as Search_Filter_Features;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Features {
	public function __construct() {}
	/**
	 * Check request permissions
	 *
	 * TODO
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get posts by query args
	 *
	 * @return void
	 */
	public function get_features_data( \WP_REST_Request $request ) {
		$features = Search_Filter_Features::get_features();
		return rest_ensure_response( $features );
	}
	public function update_features_data( \WP_REST_Request $request ) {
		$data = $request->get_param( 'data' );
		if ( ! is_array( $data ) ) {
			return rest_ensure_response( array( 'error' => 'Invalid data' ) );
		}

		$updated_feature_data = array();
		foreach ( $data as $feature => $value ) {
			if ( isset( $data[ $feature ] ) ) {
				if ( ! is_bool( $value ) ) {
					return rest_ensure_response( array( 'error' => 'Invalid data' ) );
				}
				$updated_feature_data[ $feature ] = $value;
			}
		}

		// Save the data as in the options table.
		Options::update_option_value( 'features', $updated_feature_data );

		return rest_ensure_response( $updated_feature_data );
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		register_rest_route(
			'search-filter/v1',
			'/features',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_features_data' ),
					'permission_callback' => array( $this, 'permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_features_data' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'data' => array(
							'type'              => 'object',
							'required'          => false,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
					),
				),
			)
		);
	}
}
