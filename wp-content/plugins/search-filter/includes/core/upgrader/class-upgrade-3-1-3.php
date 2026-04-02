<?php
/**
 * Upgrade routine for version 3.1.3.
 *
 * @package Search_Filter
 * @since 3.1.3
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.1.3.
 *
 * @since 3.1.3
 */
class Upgrade_3_1_3 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.1.3.
	 *
	 * @since 3.1.3
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {

		// Reset the WPML integration if its enabled to support the new WPML extension.
		$integrations = Options::get_direct( 'integrations' );
		if ( ! $integrations ) {
			return Upgrade_Result::success();
		}

		if ( isset( $integrations['wpml'] ) ) {
			if ( $integrations['wpml'] === true ) {
				$integrations['wpml'] = false;
				Options::update( 'integrations', $integrations );
			}
		}

		return Upgrade_Result::success();
	}

	/**
	 * Disables CSS save during upgrade to prevent rebuilding CSS for every change.
	 *
	 * @since 3.1.3
	 *
	 * @return bool Always returns false to disable CSS save.
	 */
	public static function disable_css_save() {
		return false;
	}
}
