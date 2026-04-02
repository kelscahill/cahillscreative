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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles feature management operations via REST API.
 *
 * @since 3.0.0
 */
class Features {
	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
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
	 * Get features data.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_REST_Response REST response.
	 */
	public function get_features_data() {
		$features = \Search_Filter\Features::get_all();
		return rest_ensure_response( $features );
	}

	/**
	 * Update features data.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
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
		\Search_Filter\Features::update_all( $updated_feature_data );

		return rest_ensure_response( $updated_feature_data );
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {
	}
}
