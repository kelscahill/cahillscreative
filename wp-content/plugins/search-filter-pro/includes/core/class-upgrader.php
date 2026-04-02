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
use Search_Filter_Pro\Core\Upgrader\Upgrade_Status;

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
	 * Array of upgrades to run.
	 *
	 * @var array
	 */
	private static $upgrades = array();

	/**
	 * Initialize the upgrader.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'run' ), 10 );
		Upgrader\Rest_API::init();
	}
	/**
	 * Register an upgrade.
	 *
	 * @param string      $version The version to upgrade to.
	 * @param string      $class_name   The class to run the upgrade from.
	 * @param bool|string $requires_free_version Whether the upgrade requires the free version to be upgraded first.
	 */
	public static function register_upgrade( $version, $class_name, $requires_free_version = false ) {
		self::$upgrades[ $version ] = array(
			'class'                 => $class_name,
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
		 * TODO - remove this check once we're at 3.2.0.
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
					update_option( 'search-filter-pro-version', $version );
				}
			}
		}
		update_option( 'search-filter-pro-version', SEARCH_FILTER_PRO_VERSION );

		// Clear caches for all S&F plugins & extensions.
		Update_Manager::invalidate_updater_caches();
	}

	/**
	 * Add a failure notice for a failed upgrade.
	 *
	 * @param string                                          $version The version that failed.
	 * @param \Search_Filter_Pro\Core\Upgrader\Upgrade_Result $result  The upgrade result.
	 */
	private static function add_failure_notice( $version, $result ) {
		\Search_Filter\Core\Notices::add_notice(
			sprintf(
				/* translators: 1: version number, 2: error message */
				__( 'Search & Filter Pro upgrade to %1$s failed: %2$s. Please contact support.', 'search-filter-pro' ),
				$version,
				$result->message ? $result->message : __( 'Unknown error', 'search-filter-pro' )
			),
			'error',
			'sf-pro-upgrade-failed-' . $version
		);
	}

	/**
	 * Add a suspended notice for an upgrade that reached max attempts.
	 *
	 * @param string $version  The version that was suspended.
	 * @param int    $attempts The number of attempts made.
	 */
	private static function add_suspended_notice( $version, $attempts ) {
		\Search_Filter\Core\Notices::add_notice(
			sprintf(
				/* translators: 1: version number, 2: number of attempts */
				__( 'Search & Filter Pro upgrade to %1$s suspended after %2$d attempts.', 'search-filter-pro' ),
				$version,
				$attempts
			),
			'error',
			'sf-pro-upgrade-suspended-' . $version
		);
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
Upgrader::register_upgrade( '3.2.0-beta-9', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_2_0_Beta_9', '3.2.0-beta-9' );
Upgrader::register_upgrade( '3.2.0-beta-10', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_2_0_Beta_10', '3.2.0-beta-10' );
Upgrader::register_upgrade( '3.2.0-beta-11', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_2_0_Beta_11', '3.2.0-beta-11' );
Upgrader::register_upgrade( '3.2.0-beta-12', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_2_0_Beta_12', '3.2.0-beta-11' );
Upgrader::register_upgrade( '3.2.3', '\Search_Filter_Pro\Core\Upgrader\Upgrade_3_2_3', '3.2.0' );
