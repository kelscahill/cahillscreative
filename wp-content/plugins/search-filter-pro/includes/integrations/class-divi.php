<?php
/**
 * Bricks Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Core\Dependants;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter_Pro\Core\Plugin_Installer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Divi integration handler.
 */
class Divi {

	/**
	 * The plugin file.
	 *
	 * @var string
	 */
	private static $plugin_file = 'search-filter-divi/search-filter-divi.php';
	/**
	 * Init
	 *
	 * @since    3.2.3
	 *
	 * @return void
	 */
	public static function init() {
		// Setup after integration settings have init.
		add_action( 'search-filter/settings/integrations/init', array( __CLASS__, 'init_integration' ) );

		// Needs to run before integrations are init to modify `is_installed`.
		add_filter( 'search-filter/integrations/is_installed', array( __CLASS__, 'integration_is_installed' ), 10, 2 );
	}

	/**
	 * Init
	 *
	 * @since    3.2.3
	 *
	 * @return void
	 */
	public static function init_integration() {

		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 10 );
		add_filter( 'search-filter/integrations/install-extension', array( __CLASS__, 'install_extension' ), 10, 2 );
		add_action( 'search-filter/integrations/enable', array( __CLASS__, 'enable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/disable', array( __CLASS__, 'disable_extension' ), 10, 1 );
		add_action( 'search-filter/integrations/validate', array( __CLASS__, 'validate_integration' ), 10, 1 );
		add_filter( 'search-filter/integrations/integration_status', array( __CLASS__, 'integration_status' ), 10, 2 );
	}


	/**
	 * Update the Bricks integration in the integrations section.
	 *
	 * @since 3.2.3
	 */
	public static function update_integration() {

		$divi_integration = Integrations_Settings::get_setting( 'divi' );
		if ( ! $divi_integration ) {
			return;
		}

		$is_divi_enabled = self::divi_enabled();

		$update_integration_settings = array(
			'isIntegrationEnabled' => $is_divi_enabled,
		);

		// If we detect Divi is enabled, then lets also set the plugin installed
		// property to true - because a plugin could be installed using a different
		// folder name and it we would initially detect is as not installed by using
		// by using `plugin_exists()` which is unreliable.
		if ( $is_divi_enabled ) {
			$update_integration_settings['isIntegrationInstalled'] = true;
		}

		// Also check if the extension plugin is installed.
		if ( Dependants::is_plugin_installed( self::$plugin_file ) ) {
			$update_integration_settings['isExtensionInstalled'] = true;
		}

		$divi_integration->update( $update_integration_settings );
	}

	/**
	 * Install the Divi extension.
	 *
	 * @since 3.2.3
	 *
	 * @param bool   $installed Whether the extension is already installed.
	 * @param string $extension The extension name.
	 * @return bool
	 */
	public static function install_extension( $installed, $extension ) {
		if ( $extension !== 'divi' ) {
			return $installed;
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Try to install the Divi extension.
		$plugin_installer = new Plugin_Installer();
		$result           = $plugin_installer->install_package_from_api( 197854 );

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

	/**
	 * Enable the Divi extension.
	 *
	 * @since 3.2.3
	 *
	 * @param string $extension The extension name.
	 */
	public static function enable_extension( $extension ) {
		if ( $extension !== 'divi' ) {
			return;
		}
		if ( ! Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::enable_plugin( self::$plugin_file );
		}
	}
	/**
	 * Disable the Divi extension.
	 *
	 * @since 3.2.3
	 *
	 * @param string $extension The extension name.
	 */
	public static function disable_extension( $extension ) {
		if ( $extension !== 'divi' ) {
			return;
		}

		// Then disable the extension plugin if its enabled.
		if ( Dependants::is_plugin_enabled( self::$plugin_file ) ) {
			Dependants::disable_plugin( self::$plugin_file );
		}
	}

	/**
	 * Check if Divi is enabled.
	 *
	 * @since 3.2.3
	 *
	 * @return bool True if Divi is enabled.
	 */
	private static function divi_enabled() {
		return defined( 'ET_CORE_VERSION' );
	}

	/**
	 * Validate the Divi integration settings.
	 *
	 * @since 3.2.3
	 */
	public static function validate_integration() {
		// First check the status of the integration.
		$is_enabled = \Search_Filter\Integrations::is_enabled( 'divi' );

		// Now check to make sure the Divi plugin and Divi extension are enabled.
		$is_plugin_enabled    = self::divi_enabled();
		$is_extension_enabled = Dependants::is_plugin_enabled( self::$plugin_file );

		// If the integration is enabled, but the plugin or extension is not, then disable the integration.
		if ( $is_enabled && ( ! $is_plugin_enabled || ! $is_extension_enabled ) ) {
			\Search_Filter\Integrations::disable( 'divi', true );
			// TODO - we want to add a notice to let the user know we had to disable the integration.
		}

		// If the integration is disabled, but the plugin and extension are enabled, then enable the integration.
		if ( ! $is_enabled && $is_plugin_enabled && $is_extension_enabled ) {
			\Search_Filter\Integrations::enable( 'divi', true );
		}
	}

	/**
	 * Manually override the integration status check for showing admin notices.
	 *
	 * Because Divi can be enabled via theme or plugin we'll override the integration status check
	 * and look for both.
	 *
	 * @since 3.2.3
	 *
	 * @param string $integration_status  The integration status.
	 * @param string $integration_slug    The integration slug.
	 *
	 * @return string  The updated status.
	 */
	public static function integration_status( $integration_status, $integration_slug ) {
		if ( $integration_slug !== 'divi' ) {
			return $integration_status;
		}

		// While integration_status supports `not_installed`, `installed` and `enabled`, it's currently
		// only used for showing admin notices, and we only need to know if Divi is enabled or not.
		if ( self::divi_enabled() ) {
			return 'enabled';
		}

		return $integration_status;
	}
	/**
	 * Manually override the integration installation check.
	 *
	 * Because Divi can be enabled via theme or plugin we need to look for both.
	 *
	 * @since 3.2.3
	 *
	 * @param bool   $is_installed      The integration status.
	 * @param string $integration_slug  The integration slug.
	 *
	 * @return bool  The updated status.
	 */
	public static function integration_is_installed( $is_installed, $integration_slug ) {
		if ( $integration_slug !== 'divi' ) {
			return $is_installed;
		}

		// We already check for the theme, if that passes, return early.
		if ( $is_installed ) {
			return true;
		}

		// Fast check, if its enabled, it must be installed.
		if ( self::divi_enabled() ) {
			return true;
		}

		// Check for the Divi Builder plugin.
		$plugin_path = 'divi-builder/divi-builder.php';

		return Dependants::is_plugin_installed( $plugin_path );
	}
}
