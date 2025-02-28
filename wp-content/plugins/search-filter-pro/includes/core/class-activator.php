<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

use Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs actions on plugin activation (via the `activate` button in wp-admin)
 */
class Activator {
	/**
	 * Run actions on plugin activation.
	 *
	 * @since    3.0.0
	 */
	public static function activate() {

		\Search_Filter_Pro\Indexer\Cron::init();
		\Search_Filter_Pro\Indexer\Query_Cache::init_cron();
		\Search_Filter_Pro\Core\License_Server::init();
		\Search_Filter_Pro\Task_Runner\Cron::init();
		\Search_Filter_Pro\Core\Remote_Notices::init();
		do_action( 'search-filter-pro/core/activator/activate' );

		// Check to see if S&F old version from .org is installed - bail if so.
		if ( Dependencies::has_legacy_base_plugin() ) {
			return;
		}

		// Install the free version of S&F from searchandfilter.com if
		// it is not already installed, and if it is, activate it.
		$plugin_file = 'search-filter/search-filter.php';
		$plugin_id   = 514539;

		if ( ! Dependencies::is_plugin_installed( $plugin_file ) ) {
			$plugin_installer = new Plugin_Installer();
			$result           = $plugin_installer->install_package_from_api( $plugin_id );

			if ( $result['status'] === 'success' && current_user_can( 'activate_plugins' ) && ! Dependencies::is_search_filter_enabled() ) {
				activate_plugin( $plugin_file );
			}
		} elseif ( ! Dependencies::is_search_filter_enabled() ) {
			// If it is installed, then activate it.
			activate_plugin( $plugin_file );
		}

		if ( ! Dependencies::is_search_filter_enabled() ) {
			return;
		}
	}

}
