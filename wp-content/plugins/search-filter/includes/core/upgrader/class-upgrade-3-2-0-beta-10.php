<?php
/**
 * Upgrade routine for version 3.2.0 Beta.
 *
 * @package Search_Filter
 * @since 3.2.0
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.2.0 Beta 10.
 *
 * @since 3.2.0
 */
class Upgrade_3_2_0_Beta_10 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.2.0 Beta.
	 *
	 * @since 3.2.0
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {

		if ( Asset_Loader::get_db_version() < 2 ) {
			// Disables saving CSS if the user is still on the previous assets version.
			add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );
		}

		// Handle feature defaults for exising users.
		$features = Options::get( 'features', array() );
		if ( is_array( $features ) ) {

			// Ensure dynamic asset loading is disabled by default for existing users.
			$features['dynamicAssetLoading'] = false;

			Options::update( 'features', $features );
		}

		return Upgrade_Result::success();
	}
}
