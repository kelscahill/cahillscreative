<?php
/**
 * Upgrade routine for version 3.1.4.
 *
 * @package Search_Filter
 * @since 3.1.4
 */

namespace Search_Filter\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.1.4.
 *
 * @since 3.1.4
 */
class Upgrade_3_1_4 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.1.4.
	 *
	 * @since 3.1.4
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {
		// Because of the database issue when creating the options table in some setups
		// the default styles are never set, so let set it.
		$default_styles_id = \Search_Filter\Styles::get_default_styles_id();
		if ( absint( $default_styles_id ) === 0 ) {
			// Find the first style preset and set it as the default.
			$styles = \Search_Filter\Styles::find( array( 'number' => 1 ) );
			if ( $styles && count( $styles ) === 1 ) {
				$styles = $styles[0];
				if ( ! is_wp_error( $styles ) ) {
					\Search_Filter\Styles::set_default_styles_id( $styles->get_id() );
				}
			}
		}

		return Upgrade_Result::success();
	}
}
