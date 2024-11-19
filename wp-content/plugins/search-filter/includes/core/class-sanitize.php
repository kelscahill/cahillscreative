<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Gutenberg integration functionality
 */
class Sanitize {
	/**
	 * Deep cleans a var
	 *
	 * Loops through arrays recursively, sanitizing scalar values only.
	 *
	 * @param mixed $unclean_var  Var to clean.
	 * @return scalar|array The cleaned var.
	 */
	public static function deep_clean( $unclean_var ) {
		if ( is_array( $unclean_var ) ) {
			// Don't we need to sanitize the key as well?
			$cleaned = array();
			foreach ( $unclean_var as $key => $val ) {
				$cleaned[ sanitize_text_field( $key ) ] = self::deep_clean( $val );
			}
			return $cleaned;
		} else {
			if ( is_bool( $unclean_var ) ) {
				return (bool) $unclean_var;
			}
			if ( is_int( $unclean_var ) ) {
				return (int) $unclean_var;
			}
			if ( is_float( $unclean_var ) ) {
				return (float) $unclean_var;
			}
			// Don't allow anything except scalar or array.
			if ( ! is_scalar( $unclean_var ) ) {
				return '';
			}
			// Check if var is multiline or not.
			$is_multline = false;
			if ( strstr( $unclean_var, PHP_EOL ) ) {
				$is_multline = true;
			}

			return $is_multline ? sanitize_textarea_field( $unclean_var ) : sanitize_text_field( $unclean_var );
		}
	}

	/**
	 * Pass through function for field options, use sparingly.
	 *
	 * Used for optimisation to avoid repeating loops but it could be
	 * a security risk if used incorrectly.
	 *
	 * @param mixed $pass_through_var The variable to pass through.
	 * @return mixed
	 */
	public static function esc_pass_through( $pass_through_var ) {
		return $pass_through_var;
	}
}
