<?php
/**
 * Upgrade routines for version 3.2.0-beta-11
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.2.0 Beta 11.
 *
 * Migrates the `disableIndexerQueryCaching` setting from the debugger
 * to the new `enableCaching` setting in the caching feature.
 */
class Upgrade_3_2_0_Beta_11 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.2.0
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		if ( Asset_Loader::get_db_version() < 2 ) {
			// Disables saving CSS if the user is still on the previous assets version.
			add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );
		}

		// Migrate caching setting from debugger to new caching feature.
		self::migrate_caching_setting();

		return Upgrade_Result::success();
	}

	/**
	 * Migrate the caching setting from debugger to caching feature.
	 *
	 * The old setting `disableIndexerQueryCaching` in the debugger section
	 * is migrated to `enableCaching` in the caching section.
	 * The logic is inverted: 'yes' (disabled) → 'no' (not enabled)
	 *                        'no' (enabled)   → 'yes' (enabled)
	 *
	 * @since 3.2.0
	 */
	private static function migrate_caching_setting() {
		// Get the current debugger settings directly from database.
		$debugger_settings = Options::get_direct( 'debugger' );

		// If no debugger settings exist, nothing to migrate.
		if ( ! is_array( $debugger_settings ) ) {
			return;
		}

		// Check if the old setting exists.
		if ( ! isset( $debugger_settings['disableIndexerQueryCaching'] ) ) {
			return;
		}

		$was_caching_disabled = $debugger_settings['disableIndexerQueryCaching'] === 'yes';

		// Invert the logic: disabled → not enabled, enabled → enabled.
		$enable_database_caching = $was_caching_disabled ? 'no' : 'yes';

		// Get or create caching settings.
		$caching_settings = Options::get_direct( 'caching' );
		if ( ! is_array( $caching_settings ) ) {
			$caching_settings = array();
		}

		// Set the new setting.
		$caching_settings['enableCaching'] = $enable_database_caching;
		Options::update( 'caching', $caching_settings );

		// Remove the old setting from debugger.
		unset( $debugger_settings['disableIndexerQueryCaching'] );
		Options::update( 'debugger', $debugger_settings );
	}
}
