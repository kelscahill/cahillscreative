<?php
/**
 * Fetch and install a plugin from a zip file.
 *
 * Handles both the WordPress.org repo and our own api on searchandfilter.com.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs the plugin from the WP.org repo.
 *
 * @since 3.0.0
 */
class Plugin_Installer {

	/**
	 * Get the package from the API.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $plugin_id    The plugin ID.
	 * @param string $license    The license.
	 * @return array    The package.
	 */
	public function get_package_from_api( $plugin_id, $license ) {
		// $free_plugin_id = 514539;
		// $plugin_id = 278073; // Elementor extension, for debugging
		// Setup the updater.
		$response        = array();
		$plugin_data_api = new Plugin_Data(
			License_Server::get_endpoint(),
			array(
				'license' => $license,
				'item_id' => $plugin_id,
				'author'  => 'Search & Filter',
				'beta'    => false,
			)
		);
		$fetched_data    = $plugin_data_api->get();
		if ( property_exists( $fetched_data, 'package' ) ) {
			// Then we have the package URL for the plugin.
			$response['status'] = 'success';
			$response['name']   = $fetched_data->name;
			$response['url']    = $fetched_data->package;
		} else {
			$response['status']        = 'error';
			$response['error_message'] = __( 'Could not find plugin', 'search-filter-pro' );
		}
		return $response;
	}

	/**
	 * Install the package from the API.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $plugin_id    The plugin ID.
	 * @param string $license    The license.
	 * @return array    The package.
	 */
	public function install_package_from_api( $plugin_id, $license = 'search-filter-extension-free' ) {
		return $this->install_package( $this->get_package_from_api( $plugin_id, $license ) );
	}

	/**
	 * Get the package from the WP.org repo.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_slug    The plugin slug.
	 * @return array    The package.
	 */
	public function get_package_from_wp_org( $plugin_slug ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$response = array();
		$api      = \plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$response['error_message'] = $api->get_error_message();
			$response['status']        = 'error';
		} else {
			$response['status'] = 'success';
			$response['name']   = $api->name;
			$response['url']    = $api->download_link;
		}
		return $response;
	}

	/**
	 * Install the package from the WP.org repo.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug    The plugin slug.
	 * @return array    The package.
	 */
	public function install_package_from_wp_org( $slug ) {
		return $this->install_package( $this->get_package_from_wp_org( $slug ) );
	}

	/**
	 * Install the package.
	 *
	 * @since 3.0.0
	 *
	 * @param array $package_data    The package data.
	 * @return array    The package.
	 */
	public function install_package( $package_data ) {

		if ( ! function_exists( '\request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( $package_data['status'] === 'error' ) {
			return $package_data;
		}

		$status = array();

		$status['name'] = $package_data['name'];

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $package_data['url'] );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['error_code']    = $result->get_error_code();
			$status['error_message'] = $result->get_error_message();
			$status['status']        = 'error';
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['error_code']    = $skin->result->get_error_code();
			$status['error_message'] = $skin->result->get_error_message();
			$status['status']        = 'error';
		} elseif ( $skin->get_errors()->has_errors() ) {
			$status['error_message'] = $skin->get_error_messages();
			$status['status']        = 'error';
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['error_code']    = 'unable_to_connect_to_filesystem';
			$status['error_message'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );
			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
				$status['error_message'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}
			$status['status'] = 'error';
		} else {
			$status['status'] = 'success';
		}

		return $status;
	}
}

