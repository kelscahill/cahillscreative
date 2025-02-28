<?php
/**
 * Generateblocks Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter_Pro\Core\Plugin_Installer;
use Search_Filter\Core\Dependants;
use Search_Filter\Integrations\Settings as Integrations_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 */
class Generate_Blocks {

	private static $plugin_file = 'search-filter-generate-blocks/search-filter-generate-blocks.php';
	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 10 );
		add_filter( 'search-filter/integrations/install-extension', array( __CLASS__, 'install_extension' ), 10, 2 );
		add_action( 'search-filter/integrations/enable', array( __CLASS__, 'enable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/disable', array( __CLASS__, 'disable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/validate', array( __CLASS__, 'validate_integration' ), 10, 1 );
	}


	/**
	 * Update the Generateblocks integration in the integrations section.
	 *
	 * @since 3.0.0
	 */
	public static function update_integration() {
		// We want to disable coming soon notice and enable the integration toggle.
		$generateblocks_integration = Integrations_Settings::get_setting( 'generateblocks' );
		if ( ! $generateblocks_integration ) {
			return;
		}

		$is_generateblocks_enabled = self::generateblocks_enabled();

		$update_integration_settings = array(
			'isPluginEnabled' => $is_generateblocks_enabled,
		);

		// If we detect Generateblocks is enabled, then lets also set the plugin installed
		// property to true - because a plugin could be installed using a different
		// folder name and it we would initially detect is as not installed by using
		// by using `plugin_exists()` which is unreliable.
		if ( $is_generateblocks_enabled ) {
			$update_integration_settings['isPluginInstalled'] = true;
		}

		// Also check if the extension plugin is installed.
		if ( Dependants::is_plugin_installed( self::$plugin_file ) ) {
			$update_integration_settings['isExtensionInstalled'] = true;
		}

		$generateblocks_integration->update( $update_integration_settings );

	}

	public static function install_extension( $installed, $extension ) {
		if ( $extension !== 'generateblocks' ) {
			return $installed;
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Try to install the Generateblocks extension.
		$plugin_installer = new Plugin_Installer();
		$result           = $plugin_installer->install_package_from_api( 594981 );

		if ( $result['status'] !== 'success' ) {
			return false;
		}

		if ( current_user_can( 'activate_plugins' ) ) {
			$activate_plugin = activate_plugin( self::$plugin_file );
			if ( is_wp_error( $activate_plugin ) ) {
				return false;
			}
			return true;
		}

		return false;
	}

	public static function enable_extension( $extension ) {
		if ( $extension !== 'generateblocks' ) {
			return;
		}
		if ( ! Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::enable_plugin( self::$plugin_file );
		}
	}
	public static function disable_extension( $extension ) {
		if ( $extension !== 'generateblocks' ) {
			return;
		}

		// Then disable the extension plugin if its enabled.
		if ( Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::disable_plugin( self::$plugin_file );
		}
	}

	/**
	 * Validate the Generateblocks integration settings.
	 *
	 * @since 3.0.0
	 */
	public static function validate_integration() {
		// First check the status of the integration.
		$is_enabled = \Search_Filter\Integrations::is_enabled( 'generateblocks' );

		// Now check to make sure the Generateblocks plugin and Generateblocks extensions are enabled.
		$is_plugin_enabled    = self::generateblocks_enabled();
		$is_extension_enabled = Dependants::is_plugin_enabled( self::$plugin_file );

		// If the integration is enabled, but the plugin or extension is not, then disable the integration.
		if ( $is_enabled && ( ! $is_plugin_enabled || ! $is_extension_enabled ) ) {
			\Search_Filter\Integrations::disable( 'generateblocks', true );
			// TODO - we want to add a notice to let the user know we had to disable the integration.
		}

		// If the integration is disabled, but the plugin and extension are enabled, then enable the integration.
		if ( ! $is_enabled && $is_plugin_enabled && $is_extension_enabled ) {
			\Search_Filter\Integrations::enable( 'generateblocks', true );
		}

	}


	/**
	 * Check if Generateblocks is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if Generateblocks is enabled.
	 */
	private static function generateblocks_enabled() {
		return defined( 'GENERATEBLOCKS_VERSION' ) || defined( 'GENERATEBLOCKS_PRO_VERSION' );
	}

}
