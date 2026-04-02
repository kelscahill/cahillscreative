<?php
/**
 * Errors handling class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Errors class
 *
 * @since 3.0.0
 */
class Errors {
	/**
	 * Add a deprecation.
	 *
	 * @param string $message The message to add.
	 */
	public static function add( $message ) {
		// Prevent notices from being logged when installing or updating.
		if ( wp_installing() || wp_is_maintenance_mode() ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Intentional error reporting to admins with manage_options capability.
			trigger_error( esc_html( $message ), E_USER_NOTICE );
		} elseif ( Util::is_debug_logging_enabled() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional error logging when WP_DEBUG_LOG is enabled.
			error_log( $message );
		}
	}
}
