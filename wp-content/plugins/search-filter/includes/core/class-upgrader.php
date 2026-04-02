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

use Search_Filter\Core\Upgrader\Upgrade_Status;
use Search_Filter\Fields\Field;
use Search_Filter\Queries\Query;
use Search_Filter\Styles\Style;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs actions on plugin activation (via the `activate` button in wp-admin)
 */
class Upgrader {

	/**
	 * Maximum number of upgrade attempts before suspending.
	 */
	const MAX_UPGRADE_ATTEMPTS = 5;

	/**
	 * Array of registered upgrades.
	 *
	 * @var array
	 */
	private static $upgrades = array();

	/**
	 * Initializes the upgrader by hooking into WordPress init action.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'run' ), 10 );
		Upgrader\Rest_API::init();
	}

	/**
	 * Register an upgrade.
	 *
	 * @param string $version    The version to upgrade to.
	 * @param string $class_name The class to run the upgrade from.
	 */
	public static function register_upgrade( $version, $class_name ) {
		self::$upgrades[ $version ] = $class_name;
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

		/**
		 * There is a tricky scenario where there was no version # until 3.0.0-beta-12.
		 * So we'll check to see if there are any fields, queries or styles, if so
		 * lets pass it through the upgrade process.
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
					// Check if suspended (reached max attempts).
					$attempts = Upgrade_Status::get_attempts( $version );
					if ( $attempts >= self::MAX_UPGRADE_ATTEMPTS ) {
						// Already at max - skip this upgrade entirely.
						self::add_suspended_notice( $version, $attempts );
						return;
					}

					// Increment attempts before running.
					Upgrade_Status::increment_attempts( $version );

					// Run upgrade - base class handles all error catching internally.
					$result = $class::upgrade();

					// Track status for diagnostics.
					Upgrade_Status::set(
						$version,
						$result->status,
						$result->message,
						$result->errors
					);

					if ( ! $result->is_success() ) {
						// Stop here - don't continue to next upgrade, don't update version.
						// Will retry on next page load (until max attempts reached).
						self::add_failure_notice( $version, $result );
						return;
					}

					// Success - update version checkpoint.
					// We need this in case we had to bail out of the upgrader process
					// at some point, and want to resume without running all the old
					// updates.
					update_option( 'search-filter-version', $version );
				}
			}
		}
		update_option( 'search-filter-version', SEARCH_FILTER_VERSION );
	}

	/**
	 * Add a failure notice for a failed upgrade.
	 *
	 * @param string                                      $version The version that failed.
	 * @param \Search_Filter\Core\Upgrader\Upgrade_Result $result  The upgrade result.
	 */
	private static function add_failure_notice( $version, $result ) {
		Notices::add_notice(
			sprintf(
				/* translators: 1: version number, 2: error message */
				__( 'Search & Filter upgrade to %1$s failed: %2$s. Please contact support.', 'search-filter' ),
				$version,
				$result->message ? $result->message : __( 'Unknown error', 'search-filter' )
			),
			'error',
			'sf-upgrade-failed-' . $version
		);
	}

	/**
	 * Add a suspended notice for an upgrade that reached max attempts.
	 *
	 * @param string $version  The version that was suspended.
	 * @param int    $attempts The number of attempts made.
	 */
	private static function add_suspended_notice( $version, $attempts ) {
		Notices::add_notice(
			sprintf(
				/* translators: 1: version number, 2: number of attempts */
				__( 'Search & Filter upgrade to %1$s suspended after %2$d attempts. Please contact support.', 'search-filter' ),
				$version,
				$attempts
			),
			'error',
			'sf-upgrade-suspended-' . $version
		);
	}

	/**
	 * Checks to see if we're on a pre beta-12 version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_first_upgrade_version() {
		$version = get_option( 'search-filter-version' );
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
		$query = Query::find(
			array(
				'number' => 1,
			),
			'record'
		);

		if ( ! is_wp_error( $query ) ) {
			return true;
		}

		// Look for a style.
		$style = Style::find(
			array(
				'number' => 1,
			),
			'record'
		);

		if ( ! is_wp_error( $style ) ) {
			return true;
		}

		return false;
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
Upgrader::register_upgrade( '3.2.0-beta-9', '\Search_Filter\Core\Upgrader\Upgrade_3_2_0_Beta_9' );
Upgrader::register_upgrade( '3.2.0-beta-10', '\Search_Filter\Core\Upgrader\Upgrade_3_2_0_Beta_10' );
Upgrader::register_upgrade( '3.2.0-beta-11', '\Search_Filter\Core\Upgrader\Upgrade_3_2_0_Beta_11' );
Upgrader::register_upgrade( '3.2.3', '\Search_Filter\Core\Upgrader\Upgrade_3_2_3' );
