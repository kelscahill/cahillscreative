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
	 * @param mixed $unclean_var     Var to clean.
	 * @param bool  $keep_whitespace Whether to preserve whitespace during cleaning.
	 * @return scalar|array The cleaned var.
	 */
	public static function deep_clean( $unclean_var, $keep_whitespace = false ) {
		if ( is_array( $unclean_var ) ) {
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

			return $is_multline ? self::sanitize_text_fields( (string) $unclean_var, true, $keep_whitespace ) : self::sanitize_text_fields( (string) $unclean_var, false, $keep_whitespace );
		}
	}
	/**
	 * Sanitize text fields.
	 *
	 * A copy of the _sanitize_text_fields function from WP core but with
	 * the following changes:
	 * - Added $keep_whitespace parameter - to keep whitespace.
	 * - $has_only_whitespace check - if its only whitespace, lets not trim it.
	 * - Added a check that $filtered is over 2 characters before html encoding `<`
	 *   its not possible to have a tag with less than 3 characters and it allows
	 *   our comparison operators `<` and `<=` to pass through without being encoded.
	 *
	 * @param string $str The string to sanitize.
	 * @param bool   $keep_newlines Whether to keep newlines.
	 * @param bool   $keep_whitespace Whether to keep whitespace.
	 * @return string The sanitized string.
	 */
	public static function sanitize_text_fields( string $str, bool $keep_newlines = false, bool $keep_whitespace = false ) {

		// Check if the source string is only whitespace.
		$has_only_whitespace = trim( $str ) === '';

		$filtered = wp_check_invalid_utf8( $str );

		if ( str_contains( $filtered, '<' ) && strlen( $filtered ) > 2 ) {
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
			// This also removes tabs and spaces which might not be want we want.
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}

		if ( ! $has_only_whitespace && ! $keep_whitespace ) {
			$filtered = trim( $filtered );
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
