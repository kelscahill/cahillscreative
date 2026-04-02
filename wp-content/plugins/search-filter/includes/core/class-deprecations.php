<?php
/**
 * Deprecations handler class.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
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
 * Deprecations class
 *
 * @since 3.0.0
 */
class Deprecations {
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Intentional deprecation notice to developers with manage_options capability.
			trigger_error( esc_html( $message ), E_USER_NOTICE );
		} elseif ( Util::is_debug_logging_enabled() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional deprecation logging when WP_DEBUG_LOG is enabled.
			error_log( $message );
		}
	}

	/**
	 * Adds a deprecation notice for a filter.
	 *
	 * @since 3.0.0
	 *
	 * @param string $filter_name  The filter name.
	 * @param string $version      The version since which the filter is deprecated.
	 * @param string $replacement  The replacement filter name.
	 */
	public static function add_filter( $filter_name, $version = '', $replacement = '' ) {
		if ( has_filter( $filter_name ) ) {
			$message = '';
			if ( $version ) {
				$message = 'Using outdated filter `' . sanitize_text_field( $filter_name ) . '` (since ' . sanitize_text_field( $version ) . ') which will be deprecated soon.';
			} else {
				$message = 'Using outdated filter `' . sanitize_text_field( $filter_name ) . '` which will be deprecated soon.';
			}
			if ( $replacement ) {
				$message .= '  Use `' . sanitize_text_field( $replacement ) . '` instead.';
			}
			self::add( $message );
		}
	}

	/**
	 * Adds a deprecation notice for an action.
	 *
	 * @since 3.0.0
	 *
	 * @param string $action_name  The action name.
	 * @param string $version      The version since which the action is deprecated.
	 * @param string $replacement  The replacement action name.
	 */
	public static function add_action( $action_name, $version = '', $replacement = '' ) {
		if ( has_action( $action_name ) ) {
			$message = '';
			if ( $version ) {
				$message = 'Using outdated filter `' . sanitize_text_field( $action_name ) . '` (since ' . sanitize_text_field( $version ) . ') which will be deprecated soon.';
			} else {
				$message = 'Using outdated filter `' . sanitize_text_field( $action_name ) . '` which will be deprecated soon.';
			}
			if ( $replacement ) {
				$message .= '  Use `' . sanitize_text_field( $replacement ) . '` instead.';
			}
			self::add( $message );
		}
	}
}
