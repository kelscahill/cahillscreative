<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\Notices;
use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Core\Plugin_Installer;
use Search_Filter_Pro\Core\Scripts;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles compatibility checks and notices.
 */
class Compatibility {

	public static function init() {
		self::wpengine();
	}

	public static function wpengine() {

		if ( ! defined( 'WPE_APIKEY' ) ) {
			return;
		}

		// Now we think we have WP Engine, try to see if the WPE_GOVERNOR constant is defined and true, if not, show a notice.
		if ( ! defined( 'WPE_GOVERNOR' ) || WPE_GOVERNOR !== true ) {
			// Display admin notice on Search & Filter screens.
			add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'add_wpengine_notice' ) );
		}
	}

	public static function add_wpengine_notice() {
		// Display a message to the user if there are any issues with the task runner.
		$notice_string = sprintf(
			// translators: 1. Link to the documentation on how to disable the long query governor.
			__( "<strong>WP Engine's long query governor</strong> causes issues with Search & Filter Pro, read <a href='%s' target='_blank'>how to disable it here</a>." ),
			esc_url( 'https://searchandfilter.com/documentation/known-issues/wp-engine/' )
		);
		Notices::add_notice( $notice_string, 'warning', 'search-filter-pro-wpengine-error' );
	}
}
