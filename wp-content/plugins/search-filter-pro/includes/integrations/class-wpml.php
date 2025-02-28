<?php
/**
 * WPML Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;
use Search_Filter_Pro\Core\Plugin_Installer;

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
		add_filter( 'search-filter/integrations/install-extension', array( __CLASS__, 'install_extension' ), 10, 2 );
	}

	/**
	 * Install the WPML extension.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $installed Whether the extension is installed.
	 * @param string $extension The extension to install.
	 * @return bool
	 */
	public static function install_extension( $installed, $extension ) {
		if ( $extension !== 'wpml' ) {
			return $installed;
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Try to install the WPML extension.
		$plugin_installer = new Plugin_Installer();
		$result           = $plugin_installer->install_package_from_api( 593441 );

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

}
