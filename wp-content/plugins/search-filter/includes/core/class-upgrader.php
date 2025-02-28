<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs actions on plugin activation (via the `activate` button in wp-admin)
 */
class Upgrader {

	private static $upgrades = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'run' ), 10 );
	}
	/**
	 * Register an upgrade.
	 *
	 * @param string $version The version to upgrade to.
	 * @param string $class   The class to run the upgrade from.
	 */
	public static function register_upgrade( $version, $class ) {
		self::$upgrades[ $version ] = $class;
	}

	/**
	 * Runs the upgrades.
	 */
	public static function run() {
		$previous_version = get_option( 'search-filter-version' );

		if ( version_compare( $previous_version, SEARCH_FILTER_VERSION, '=' ) ) {
			// Bail early if we're already on the latest version.
			return;
		}

		// If previous version check to see if we need to run any upgrades.
		// If its falsy, then we're on a new install.
		if ( $previous_version ) {
			foreach ( self::$upgrades as $version => $class ) {
				if ( version_compare( $previous_version, $version, '<' ) ) {
					$class::upgrade();
				}
			}
		}
		update_option( 'search-filter-version', SEARCH_FILTER_VERSION );
	}
}

// Important: these must be kept in order, from oldest to newest version,
// so that the upgrades can be run in the correct order.
Upgrader::register_upgrade( '3.0.0-beta-12', '\Search_Filter\Core\Upgrader\Upgrade_3_0_0_Beta_12' );
Upgrader::register_upgrade( '3.0.0-beta-16', '\Search_Filter\Core\Upgrader\Upgrade_3_0_0_Beta_16' );
Upgrader::register_upgrade( '3.0.2', '\Search_Filter\Core\Upgrader\Upgrade_3_0_2' );
Upgrader::register_upgrade( '3.0.4', '\Search_Filter\Core\Upgrader\Upgrade_3_0_4' );
Upgrader::register_upgrade( '3.0.6', '\Search_Filter\Core\Upgrader\Upgrade_3_0_6' );
Upgrader::register_upgrade( '3.0.7', '\Search_Filter\Core\Upgrader\Upgrade_3_0_7' );
Upgrader::register_upgrade( '3.1.0', '\Search_Filter\Core\Upgrader\Upgrade_3_1_0' );
Upgrader::register_upgrade( '3.1.3', '\Search_Filter\Core\Upgrader\Upgrade_3_1_3' );
Upgrader::register_upgrade( '3.1.4', '\Search_Filter\Core\Upgrader\Upgrade_3_1_4' );
