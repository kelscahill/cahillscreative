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
		self::query_monitor();
	}
	/**
	 * Add a notice for WP Engine users if the long query governor is enabled.
	 *
	 * @return void
	 */
	public static function wpengine() {

		if ( ! defined( 'WPE_APIKEY' ) ) {
			return;
		}
		// Now we think we have WP Engine, try to see if the WPE_GOVERNOR constant is defined and true, if not, show a notice.
		if ( ! defined( 'WPE_GOVERNOR' ) || WPE_GOVERNOR !== false ) {
			// Display admin notice on Search & Filter screens.
			add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'add_wpengine_notice' ) );
		}
	}

	/**
	 * Add the WP Engine long query governor notice.
	 */
	public static function add_wpengine_notice() {
		// Display a message to the user if there are any issues with the task runner.
		$notice_string = sprintf(
			// translators: 1. Link to the documentation on how to disable the long query governor.
			__( "<strong>WP Engine's long query governor</strong> causes issues with Search & Filter Pro, read <a href='%s' target='_blank'>how to disable it here</a>." ),
			esc_url( 'https://searchandfilter.com/documentation/known-issues/wp-engine/' )
		);
		Notices::add_notice( $notice_string, 'warning', 'search-filter-pro-wpengine-error' );
	}

	/**
	 * Stop Query Monitor from running when our frontend JSON API is being called.
	 *
	 * Removing query monitor is not really needed anymore due to the updates
	 * to output buffering, but seen as the api requests can't be tracked by
	 * Query Monitor, we may aswell disable it to improve performance.
	 */
	public static function query_monitor() {
		if ( ! class_exists( '\QM_Activation' ) ) {
			return;
		}
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['search-filter-api'] ) ) {
			return;
		}

		do_action( 'qm/cease' );
		add_filter( 'qm/dispatch/html', '__return_false' );
		add_filter( 'qm/dispatch/ajax', '__return_false' );
		add_filter( 'qm/dispatch/redirect', '__return_false' );
		add_filter( 'qm/dispatch/rest', '__return_false' );
		add_filter( 'qm/dispatch/wp_die', '__return_false' );
	}
}
