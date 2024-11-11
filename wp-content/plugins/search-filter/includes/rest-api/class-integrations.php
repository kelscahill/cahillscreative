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
use Search_Filter\Integrations as Search_Filter_Integrations;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Integrations {
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
	 * Get posts by query args.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return array The integration data.
	 */
	public function get_integration_data( \WP_REST_Request $request ) {
		$integrations = Search_Filter_Integrations::get_integrations();
		return rest_ensure_response( $integrations );
	}

	/**
	 * Update integration data
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return array The updated integration data.
	 */
	public function update_integration_data( \WP_REST_Request $request ) {
		$data = $request->get_param( 'data' );
		if ( ! is_array( $data ) ) {
			return rest_ensure_response( array( 'error' => 'Invalid data' ) );
		}

		$updated_integration_data = array();
		foreach ( $data as $integration => $value ) {
			if ( isset( $data[ $integration ] ) ) {
				if ( ! is_bool( $value ) ) {
					return rest_ensure_response( array( 'error' => 'Invalid data' ) );
				}
				$updated_integration_data[ $integration ] = $value;
			}
		}

		// Save the data as in the options table.
		Options::update_option_value( 'integrations', $updated_integration_data );

		return rest_ensure_response( $updated_integration_data );
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		register_rest_route(
			'search-filter/v1',
			'/integrations',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_integration_data' ),
					'permission_callback' => array( $this, 'permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_integration_data' ),
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
