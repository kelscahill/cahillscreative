<?php
/**
 * Upgrade routines for version 3.1.5
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.1.5
 */
class Upgrade_3_1_5 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.1.5
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Delete option 'license-server' as we no longer need it.
		\Search_Filter\Options::delete( 'license-server' );

		return Upgrade_Result::success();
	}
}
