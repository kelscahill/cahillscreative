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

namespace Search_Filter_Pro\Core;

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Runs actions on plugin activation (via the `activate` button in wp-admin)
 */
class Upgrader {

	// Array of upgrades to run.
	private static $upgrades = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'run' ), 10 );
	}
	/**
	 * Register an upgrade.
	 *
	 * @param string      $version The version to upgrade to.
	 * @param string      $class   The class to run the upgrade from.
	 * @param bool|string $requires_free_version Whether the upgrade requires the free version to be upgraded first.
	 */
	public static function register_upgrade( $version, $class, $requires_free_version = false ) {
		self::$upgrades[ $version ] = array(
			'class'                 => $class,
			'requires_free_version' => $requires_free_version,
		);
	}

	/**
	 * Runs the upgrades.
	 */
	public static function run() {
		$previous_version = get_option( 'search-filter-pro-version' );

		if ( version_compare( $previous_version, SEARCH_FILTER_PRO_VERSION, '=' ) ) {
			// Bail early if we're already on the latest version.
			return;
		}
		/**
		 * There is a tricky scenario where there was no no version # until 3.0.4.
		 * So we'll check to see if there are any fields, queries or styles, if so
		 * lets pass it through the upgrade process.
		 *
		 * TODO - remove this check once we're at 3.1.0.
		 */
		if ( ! $previous_version ) {
			if ( self::is_first_upgrade_version() ) {
				$previous_version = '0.0.0';
			}
		}

		// If previous version check to see if we need to run any upgrades.
		// If its falsy, then we're on a new install.
		if ( $previous_version ) {
			foreach ( self::$upgrades as $version => $upgrade ) {
				$class                 = $upgrade['class'];
				$requires_free_version = $upgrade['requires_free_version'];

				// If we require the free version to be updated, then check which version
				// of free we're on, but don't use the constant, that won't reflect which
				// upgrades have been run, get it directly from the database.

				if ( $requires_free_version ) {
					$free_version = get_option( 'search-filter-version' );
					if ( version_compare( $free_version, $requires_free_version, '<' ) ) {
						// If we haven't met the requirements at this stage, leave the upgrader process
						// and don't update S&F pro version, but lets update the pro version to match
						// which version we've upgraded to.
						return;
					}
				}

				if ( version_compare( $previous_version, $version, '<' ) ) {
					$class::upgrade();

					// Update the pro version to match the version we've upgraded to.
					// We need this in case we had to bail out of the upgrader process
					// at some point, and want to resume without running all the old
					// updates.
					update_option( 'search-filter-pro-version', $version );
				}
			}
		}
		update_option( 'search-filter-pro-version', SEARCH_FILTER_PRO_VERSION );

		// Clear caches for all S&F plugins & extensions.
		Update_Manager::invalidate_updater_caches();
	}
	/**
	 * Checks to see if we're on a pre beta-12 version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private static function is_first_upgrade_version() {
		$version = get_option( 'search-filter-pro-version' );
		if ( $version ) {
			return false;
		}

		// Look for a field.
		$field = Field::find(
			array(
				'number' => 1,
			),
			'record'
		);
		if ( ! is_wp_error( $field ) ) {
			return true;
		}

		// Look for a query.
		$query = \Search_Filter\Queries\Query::find(
			array(
				'number' => 1,
			),
			'record'
		);

		if ( ! is_wp_error( $query ) ) {
			return true;
		}

		return false;
	}
}

// Important: these must be kept in order, from oldest to newest version,
// so that the upgrades can be run in the correct order.
Upgrader::register_upgrade( '3.0.4', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_0_4', '3.0.4' );
Upgrader::register_upgrade( '3.0.5', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_0_5', '3.0.5' );
Upgrader::register_upgrade( '3.0.6', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_0_6', '3.0.6' );
Upgrader::register_upgrade( '3.1.0', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_1_0', '3.1.0' );
Upgrader::register_upgrade( '3.1.5', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_1_5' );
