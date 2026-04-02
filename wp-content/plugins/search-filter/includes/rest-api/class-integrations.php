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

use Search_Filter\Integrations as Search_Filter_Integrations;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integration-related operations via REST API.
 *
 * @since 3.0.0
 */
class Integrations {
	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get integrations data.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_REST_Response The integration data.
	 */
	public function get_integration_data() {
		$integrations = Search_Filter_Integrations::get_all();
		return rest_ensure_response( $integrations );
	}


	/**
	 * Update integration data
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response The updated integration data.
	 */
	public function update_integrations_data( \WP_REST_Request $request ) {
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
		Options::update( 'integrations', $updated_integration_data );

		return rest_ensure_response( $updated_integration_data );
	}

	/**
	 * Update integration data
	 *
	 * @since 3.0.6
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response The updated integration data.
	 */
	public function update_integration_data( \WP_REST_Request $request ) {
		$name  = $request->get_param( 'name' );
		$value = $request->get_param( 'value' );

		// Get the integrations options.
		$integrations = Search_Filter_Integrations::get_all();
		// Update the integration data.
		$integrations[ $name ] = $value;

		if ( $value ) {
			Search_Filter_Integrations::enable( $name );
		} else {
			Search_Filter_Integrations::disable( $name );
		}

		// Save the data as in the options table.
		Options::update( 'integrations', $integrations );

		return rest_ensure_response(
			array(
				'name'  => $name,
				'value' => $value,
			)
		);
	}

	/**
	 * Run the install an extension hook.
	 *
	 * @since 3.0.6
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response The updated integration data.
	 */
	public function install_extension_rest( \WP_REST_Request $request ) {
		return rest_ensure_response(
			$this->install_extension(
				$request->get_param( 'name' ) ?? ''
			)
		);
	}


	/**
	 * Run the install an extension hook.
	 *
	 * @since 3.0.6
	 *
	 * @param string $name The name of the extension to install.
	 *
	 * @return array|\WP_REST_Response The updated integration data or error response.
	 */
	public function install_extension( $name ) {
		// Get the integrations options.
		$integrations = Search_Filter_Integrations::get_all();
		// Enable the integration.
		$did_install           = apply_filters( 'search-filter/integrations/install-extension', false, $name );
		$integrations[ $name ] = $did_install;
		// Save the data as in the options table.
		Options::update( 'integrations', $integrations );

		$extension_setting = Integrations_Settings::get_setting( $name );
		if ( ! $extension_setting ) {
			return rest_convert_error_to_response( new \WP_Error( 'extension_not_found', 'Extension not found' ) );
		}
		$extension_setting->update(
			array(
				'isExtensionInstalled' => $did_install,
			)
		);

		$result = array(
			'value'   => $integrations,
			'setting' => $extension_setting->get_data(),
		);

		if ( ! $did_install ) {
			$result['error'] = __( 'Failed to install extension.', 'search-filter' );
		}

		return $result;
	}

		/**
		 * Run bulk update operations for integrations.
		 *
		 * @since 3.1.7
		 *
		 * @param \WP_REST_Request $request The request object.
		 *
		 * @return array The updated integrations data.
		 */
	public function bulk_update_integrations( \WP_REST_Request $request ) {
		// Keyed array of integration names and sub-arrays of options.
		$data         = $request->get_param( 'integrations' ) ?? array();
		$integrations = Search_Filter_Integrations::get_all();
		$result       = array(
			'settings' => $integrations,
			'updates'  => array(),
		);

		foreach ( $data as $name => $options ) {
			// Setup result array.
			$result['updates'][ $name ] = array();

			// If $options is a boolean, assume we mean to install it if it should be enabled.
			$install = is_bool( $options )
				? $options
				: $options['install'] ?? false;
			$enabled = is_bool( $options )
				? $options
				: $options['enabled'] ?? null;

			// Check if integration install should be attempted.
			if ( $install ) {
				$installation = $this->install_extension( $name );
				$error        = $installation['error'] ?? false;

				$result['updates'][ $name ]['install'] = $error ? false : true;
			}

			if ( is_bool( $enabled ) ) {
				$integrations[ $name ]                 = $enabled;
				$result['updates'][ $name ]['enabled'] = $enabled;

				if ( $enabled ) {
					Search_Filter_Integrations::enable( $name );
				} else {
					Search_Filter_Integrations::disable( $name );
				}
			}
		}

		$result['settings'] = $integrations;

		Options::update( 'integrations', $result['settings'] );

		return $result;
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		register_rest_route(
			'search-filter/v1',
			'/integrations/install',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install_extension_rest' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'name' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
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
					'callback'            => array( $this, 'update_integrations_data' ),
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
		register_rest_route(
			'search-filter/v1',
			'/integrations/bulk',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'bulk_update_integrations' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'integrations' => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
					),
				),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/integrations/(?P<name>[\w-]+)',
			array(
				'args' => array(
					'name' => array(
						'description'       => __( 'Unique integration name.', 'search-filter' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_integration_data' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'value' => array(
							'type'              => 'boolean',
							'required'          => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}
}
