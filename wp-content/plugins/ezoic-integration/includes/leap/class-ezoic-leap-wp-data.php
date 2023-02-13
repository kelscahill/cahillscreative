<?php

namespace Ezoic_Namespace;

class Ezoic_Leap_Wp_Data {

	public static function set_debug_to_ezoic() {
		set_transient( 'ezoic_send_debug', array( 1, 1 ) );
	}

	public function send_debug_to_ezoic($force_send = false) {

		$ezoic_send_debug = get_transient( 'ezoic_send_debug' );
		if ( $force_send || $ezoic_send_debug ) {

			if ( ! is_array( $ezoic_send_debug ) ) {
				$ezoic_send_debug = array( 1, 1 );
			}

			if ( ! class_exists( 'WP_Debug_Data' ) ) {
				// return if file not found for older WP versions
				if ( ! file_exists( ABSPATH . 'wp-admin/includes/class-wp-debug-data.php' ) ) {
					delete_transient( 'ezoic_send_debug' );

					return;
				}
				require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
			}
			if ( ! class_exists( 'WP_Site_Health' ) ) {
				// return if file not found for older WP versions
				if ( ! file_exists( ABSPATH . 'wp-admin/includes/class-wp-site-health.php' ) ) {
					delete_transient( 'ezoic_send_debug' );

					return;
				}
				require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
			}

			if ( class_exists( 'WP_Debug_Data' ) ) {
				$debug = new \WP_Debug_Data();
				$debug::check_for_updates();
				$info = ( $debug::debug_data() );

				$info['wp-get-plugins'] = $this->get_plugin_data();

				$request = $this->build_data_request( $info, $ezoic_send_debug[1] );

				//Ezoic_Integration_Request_Utils::get_ezoic_server_address()
				$response = wp_remote_post("https://publisherbe.ezoic.com/pub/v1/wordpressintegration/v1/wp/debug", $request);
			}
			delete_transient( 'ezoic_send_debug' );

		}
	}

	/**
	 * Get list of wordpress plugins with status
	 *
	 * @return array[]
	 */
	public function get_plugin_data() {

		// Get all plugins
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		// Get active plugins
		$active_plugins = get_option( 'active_plugins' );

		// Add 'Active' boolean to $all_plugins array.
		foreach ( $all_plugins as $key => $value ) {
			$is_active                     = in_array( $key, $active_plugins );
			$all_plugins[ $key ]['Active'] = $is_active;

			$plugin_slug = dirname( plugin_basename( $key ) );
			if ( $plugin_slug == "." ) {
				$plugin_slug = basename( $key, '.php' );
			}
			$all_plugins[ $key ]['Slug'] = $plugin_slug;
		}

		return $all_plugins;
	}


	/**
	 * @param $data
	 * @param bool $status
	 *
	 * @return array
	 */
	public function build_data_request( $data, $status = 1 ) {
		global $wp;

		$domain = home_url( $wp->request );
		$domain = wp_parse_url( $domain )['host'];

		$request_params = array(
			'domain'    => $domain,
			'title'     => get_bloginfo( 'name' ),
			'url'       => get_bloginfo( 'url' ),
			'data'      => $data,
			'is_active' => (bool) $status,
		);

		$request = array(
			'timeout' => 30,
			'body'    => json_encode( $request_params ),
			'headers' => array(
				'X-Wordpress-Integration' => 'true',
				'Expect'                  => '',
				'X-From-Req'              => 'wp'
			),
		);

		return $request;
	}


}
