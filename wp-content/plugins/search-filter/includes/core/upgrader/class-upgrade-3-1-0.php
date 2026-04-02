<?php
/**
 * Upgrade routine for version 3.1.0.
 *
 * @package Search_Filter
 * @since 3.1.0
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.1.0.
 *
 * @since 3.1.0
 */
class Upgrade_3_1_0 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.1.0.
	 *
	 * @since 3.1.0
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {

		delete_option( 'search_filter_css_mode' );
		delete_option( 'search_filter_css_version_id' );

		// Move some of the WP options into our own table.
		Options::update( 'css-mode', 'file-system' );
		Options::update( 'css-version-id', 1 );

		return Upgrade_Result::success();
	}

	/**
	 * Disables CSS save during upgrade to prevent rebuilding CSS for every change.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Always returns false to disable CSS save.
	 */
	public static function disable_css_save() {
		return false;
	}
}
