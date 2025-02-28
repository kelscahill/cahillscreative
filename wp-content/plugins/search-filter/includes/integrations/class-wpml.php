<?php
/**
 * ACF Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter\Integrations;

use Search_Filter\Core\Dependants;
use Search_Filter\Integrations\Settings as Integrations_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 */
class Wpml {

	private static $plugin_file = 'search-filter-wpml/search-filter-wpml.php';
	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 10 );
		add_action( 'search-filter/integrations/enable', array( __CLASS__, 'enable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/disable', array( __CLASS__, 'disable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/validate', array( __CLASS__, 'validate_integration' ), 10, 1 );
	}

	/**
	 * Update the ACF integration in the integrations section.
	 *
	 * @since 3.0.0
	 */
	public static function update_integration() {
		// We want to disable coming soon notice and enable the integration toggle.
		$wpml_integration = Integrations_Settings::get_setting( 'wpml' );
		if ( ! $wpml_integration ) {
			return;
		}

		$is_wpml_enabled = self::is_wpml_enabled();

		$update_integration_settings = array(
			'isPluginEnabled' => $is_wpml_enabled,
		);

		// If we detect WPML is enabled, then lets also set the plugin installed
		// property to true - because a plugin could be installed using a different
		// folder name and it we would initially detect is as not installed by using
		// by using `plugin_exists()` which is unreliable.
		if ( $is_wpml_enabled ) {
			$update_integration_settings['isPluginInstalled'] = true;
		}

		// Also check if the extension plugin is installed.
		if ( Dependants::is_plugin_installed( self::$plugin_file ) ) {
			$update_integration_settings['isExtensionInstalled'] = true;
		}

		$wpml_integration->update( $update_integration_settings );
	}

	public static function enable_extension( $extension ) {
		if ( $extension !== 'wpml' ) {
			return;
		}
		if ( ! Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::enable_plugin( self::$plugin_file );
		}
	}
	public static function disable_extension( $extension ) {
		if ( $extension !== 'wpml' ) {
			return;
		}

		// Then disable the extension plugin if its enabled.
		if ( Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::disable_plugin( self::$plugin_file );
		}
	}

	/**
	 * Check if WPML is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if Relevanssi is enabled.
	 */
	public static function is_wpml_enabled() {
		return has_filter( 'wpml_object_id' ) || function_exists( 'icl_object_id' );
	}

	/**
	 * Validate the WPML integration settings.
	 *
	 * @since 3.0.0
	 */
	public static function validate_integration() {
		// First check the status of the integration.
		$is_enabled = \Search_Filter\Integrations::is_enabled( 'wpml' );

		// Now check to make sure the WPML plugin and WPML extensions are enabled.
		$is_plugin_enabled    = self::is_wpml_enabled();
		$is_extension_enabled = Dependants::is_plugin_enabled( self::$plugin_file );

		// If the integration is enabled, but the plugin or extension is not, then disable the integration.
		if ( $is_enabled && ( ! $is_plugin_enabled || ! $is_extension_enabled ) ) {
			\Search_Filter\Integrations::disable( 'wpml', true );
			// TODO - we want to add a notice to let the user know we had to disable the integration.
		}

		// If the integration is disabled, but the plugin and extension are enabled, then enable the integration.
		if ( ! $is_enabled && $is_plugin_enabled && $is_extension_enabled ) {
			\Search_Filter\Integrations::enable( 'wpml', true );
		}
	}
}
