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
			foreach ( self::$upgrades as $version => $class ) {
				if ( version_compare( $previous_version, $version, '<' ) ) {
					$class::upgrade();
				}
			}
		}
		update_option( 'search-filter-pro-version', SEARCH_FILTER_PRO_VERSION );
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
Upgrader::register_upgrade( '3.0.4', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_0_4' );
