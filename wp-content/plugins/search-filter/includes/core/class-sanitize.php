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

			return $is_multline ? self::sanitize_text_fields( $unclean_var, true ) : self::sanitize_text_fields( $unclean_var, false );
		}
	}
	/**
	 * Sanitize text fields.
	 *
	 * A copy of the _sanitize_text_fields function from WP core but with
	 * the following changes:
	 * - Added $keep_whitespace parameter - to keep whitespace.
	 * - $has_only_whitespace check - if its only whitespace, lets not trim it.
	 *
	 * @param mixed $unclean_var The variable to sanitize.
	 * @return mixed
	 */
	public static function sanitize_text_fields( $str, $keep_newlines = false, $keep_whitespace = false ) {
		if ( is_object( $str ) || is_array( $str ) ) {
			return '';
		}
	
		$str = (string) $str;
	
		// Check if the source string is only whitespace.
		$has_only_whitespace = trim( $str ) === '';

		$filtered = wp_check_invalid_utf8( $str );
	
		if ( str_contains( $filtered, '<' ) ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, false );
	
			/*
				* Use HTML entities in a special case to make sure that
				* later newline stripping stages cannot lead to a functional tag.
				*/
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}
	
		if ( ! $keep_newlines ) {
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}

		if ( ! $has_only_whitespace && ! $keep_whitespace ) {
			$filtered = trim( $filtered );
		}
	
		// Remove percent-encoded characters.
		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}
	
		if ( $found ) {
			// Strip out the whitespace that may now exist after removing percent-encoded characters.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}
	
		return $filtered;
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
