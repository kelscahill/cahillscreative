<?php
/**
 * Util class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Deprecations;
use Search_Filter\Database\Transaction;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A helper class with functions used across the plugin
 */
class Util {

	/**
	 * Logged messages for deduplication within a request.
	 *
	 * @var array
	 */
	private static $logged_messages = array();

	/**
	 * TODO - deprecate this function.
	 *
	 * @param string $file_ext  The extension of the file.
	 *
	 * @return string
	 */
	public static function get_file_ext( $file_ext ) {
		Deprecations::add( 'Using outdated method `get_file_ext` which will be deprecated soon.  Update Search & Filter and extensions to remove this notice.' );
		return $file_ext;
	}

	/**
	 * Get the data for the object that gets passed to JS app
	 *
	 * @return array
	 */
	public static function get_js_data() {
		return array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'restUrl'      => rest_url( 'search-filter' ),
			'homeUrl'      => home_url( '/' ),
			'dashboardUrl' => admin_url( 'admin.php?page=search-filter' ),
		);
	}

	/**
	 * Converts an associative array to a HTML attribute string
	 *
	 * @param array $attributes  An associative array of key -> value pairs.
	 *
	 * @return string
	 */
	public static function get_attributes_html( $attributes ) {
		$output = '';
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $attribute_name => $value ) {
				// Clean the value.
				$clean_value = '';
				if ( is_array( $value ) ) {
					$clean_value = esc_attr( wp_json_encode( $value ) );
				} else {
					$clean_value = esc_attr( $value );
				}
				// Make sure the attibute + value are not empty.
				if ( ( ! empty( $attribute_name ) ) && ( $clean_value !== '' ) ) {
					$output .= ' ' . sanitize_key( $attribute_name ) . '="' . $clean_value . '" ';
				} elseif ( ! empty( $attribute_name ) ) {
					$output .= ' ' . sanitize_key( $attribute_name ) . ' ';
				}
			}
		}
		return $output;
	}

	/**
	 * Sanitize a CSS box attribute.
	 *
	 * A box attribute is one that is a string of 4 values, separated by spaces.
	 *
	 * @param mixed $value An array of 4 values, with keys "top", "right", "bottom", "left".
	 *
	 * @return string Cleaned CSS box value.
	 */
	public static function sanitize_css_box( $value ) {

		if ( ! is_array( $value ) ) {
			return '';
		}

		// Make sure we have all 4 values.
		if ( 4 !== count( $value ) ) {
			return '';
		}

		$clean_value = '';
		$key_order   = array( 'top', 'right', 'bottom', 'left' );
		foreach ( $key_order as $key ) {
			if ( ! isset( $value[ $key ] ) ) {
				return '';
			}
			// Sanitize the value (can be a valid CSS unit).
			$value[ $key ] = sanitize_title_with_dashes( $value[ $key ] );
			$clean_value  .= $value[ $key ] . ' ';
		}

		return trim( $clean_value );
	}

	/**
	 * Log an error message to the error log.
	 *
	 * Only if WP_DEBUG is enabled. Automatically defers logging if called
	 * during an active database transaction to prevent DB access issues.
	 *
	 * This is a duplicate of the function in the parent plugin,
	 * because we need to use it when the parent plugin is not
	 * loaded.
	 *
	 * @param string $message The error message.
	 * @param string $level   The log level (error, warning, notice).
	 * @param bool   $once    If true, only log this message once per request.
	 */
	public static function error_log( $message, $level = 'error', $once = false ) {
		// If inside a transaction, defer logging to prevent DB access.
		if ( Transaction::is_active() ) {
			Transaction::defer(
				function () use ( $message, $level, $once ) {
					self::do_error_log( $message, $level, $once );
				}
			);
			return;
		}

		self::do_error_log( $message, $level, $once );
	}

	/**
	 * Actually perform the logging (internal, bypasses transaction check).
	 *
	 * @param string $message The error message.
	 * @param string $level   The log level (error, warning, notice).
	 * @param bool   $once    If true, only log this message once per request.
	 */
	private static function do_error_log( $message, $level = 'error', $once = false ) {
		// Handle once-per-request deduplication.
		if ( $once ) {
			$key = md5( $level . $message );
			if ( isset( self::$logged_messages[ $key ] ) ) {
				return;
			}
			self::$logged_messages[ $key ] = true;
		}

		$log_level       = 'errors';
		$log_to_database = 'no';

		if ( did_action( 'search-filter/settings/features/init' ) && Features::is_enabled( 'debugMode' ) && class_exists( '\Search_Filter\Debugger' ) ) {
			$log_level = \Search_Filter\Features::get_setting_value( 'debugger', 'logLevel' );
			if ( $log_level === null ) {
				$log_level = 'errors';
			}
			$log_to_database = \Search_Filter\Features::get_setting_value( 'debugger', 'logToDatabase' );
			if ( $log_to_database === null ) {
				$log_to_database = 'no';
			}
		}

		$log_matrix = array(
			'errors'   => array( 'error' ),
			'warnings' => array( 'warning', 'error' ),
			'all'      => array( 'notice', 'warning', 'error' ),
		);

		if ( ! in_array( $level, $log_matrix[ $log_level ], true ) ) {
			return;
		}

		$pid = '';
		// Some hosting companies like Kinsta disable this function.
		if ( function_exists( 'getmypid' ) ) {
			$pid = getmypid() . ' | ';
		}

		if ( self::is_debug_logging_enabled() ) {
			// Translators: %1$s is the process ID, %2$s is the message.
			$full_message = wp_kses_post( sprintf( '%1$sSearch & Filter: %2$s', $pid, $message ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $full_message );
		}

		if ( did_action( 'search-filter/settings/features/init' ) && Features::is_enabled( 'debugMode' ) && $log_to_database === 'yes' && class_exists( '\Search_Filter\Debugger' ) ) {
			$full_message = sprintf( '%1$sSearch & Filter: %2$s', $pid, $message );
			\Search_Filter\Debugger::create_log(
				array(
					'message' => sanitize_text_field( $full_message ),
					'level'   => $level,
				)
			);
		}
	}

	/**
	 * Is debug logging enabled?
	 *
	 * @return bool
	 */
	public static function is_debug_logging_enabled() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG === true && defined( 'WP_DEBUG_LOG' ) && ! empty( WP_DEBUG_LOG );
	}

	/**
	 * Sort an array.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $items The array to sort.
	 * @param string $order The order to sort by.
	 * @param string $order_direction The direction to sort by.
	 * @return array The sorted array.
	 */
	public static function sort_array( $items, $order = 'alphabetical', $order_direction = 'asc' ) {
		$sort_flag = SORT_STRING;
		if ( $order === 'numerical' ) {
			$sort_flag = SORT_NUMERIC;
		}
		if ( $order_direction === 'asc' ) {
			sort( $items, $sort_flag );
		} else {
			rsort( $items, $sort_flag );
		}

		return $items;
	}
	/**
	 * Sort an assoc array.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $items The array to sort.
	 * @param string $order The order to sort by.
	 * @param string $order_direction The direction to sort by.
	 * @return array The sorted array.
	 */
	public static function sort_assoc_array( $items, $order = 'alphabetical', $order_direction = 'asc' ) {
		$sort_flag = SORT_STRING;
		if ( $order === 'numerical' ) {
			$sort_flag = SORT_NUMERIC;
		}
		if ( $order_direction === 'asc' ) {
			asort( $items, $sort_flag );
		} else {
			arsort( $items, $sort_flag );
		}

		return $items;
	}

	/**
	 * Sort an array of objects by a property.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $items The array of objects to sort.
	 * @param string $property The property to sort by.
	 * @param string $order The order to sort by.
	 * @param string $order_direction The direction to sort by.
	 * @return array The sorted array.
	 */
	public static function sort_objects_by_property( $items, $property, $order = 'alphabetical', $order_direction = 'asc' ) {
		usort(
			$items,
			function ( $a, $b ) use ( $property, $order, $order_direction ) {

				if ( $order === 'numerical' ) {
					if ( $order_direction === 'asc' ) {
						return (int) $a->$property > (int) $b->$property ? 1 : -1;
					} else {
						return (int) $a->$property < (int) $b->$property ? 1 : -1;
					}
				} elseif ( $order_direction === 'asc' ) {
						return strcasecmp( $a->$property, $b->$property );
				} else {
					return strcasecmp( $b->$property, $a->$property );
				}
			}
		);
		return $items;
	}
	/**
	 * Sort an array of objects by a property.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $items The array of objects to sort.
	 * @param string $property The property to sort by.
	 * @param string $order The order to sort by.
	 * @param string $order_direction The direction to sort by.
	 * @return array The sorted array.
	 */
	public static function sort_assoc_array_by_property( $items, $property, $order = 'alphabetical', $order_direction = 'asc' ) {
		usort(
			$items,
			function ( $a, $b ) use ( $property, $order, $order_direction ) {

				if ( $order === 'numerical' ) {
					$val_a = isset( $a[ $property ] ) ? (int) $a[ $property ] : 0;
					$val_b = isset( $b[ $property ] ) ? (int) $b[ $property ] : 0;
					if ( $order_direction === 'asc' ) {
						return $val_a > $val_b ? 1 : -1;
					} else {
						return $val_a < $val_b ? 1 : -1;
					}
				} else {
					$val_a = isset( $a[ $property ] ) ? $a[ $property ] : '';
					$val_b = isset( $b[ $property ] ) ? $b[ $property ] : '';
					if ( $order_direction === 'asc' ) {
						return strcasecmp( $val_a, $val_b );
					} else {
						return strcasecmp( $val_b, $val_a );
					}
				}
			}
		);
		return $items;
	}

	/**
	 * Replace a string in a string.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $search The string to search for.
	 * @param  string $replace The string to replace with.
	 * @param  string $subject The string to search in.
	 * @return string
	 */
	public static function string_lreplace( $search, $replace, $subject ) {
		$pos = strrpos( $subject, $search );

		if ( $pos !== false ) {
			$subject = substr_replace( $subject, $replace, $pos, strlen( $search ) );
		}

		return $subject;
	}


	/**
	 * Get the global $_GET variable with fallback to $_POST to support more
	 * use cases out of the box.
	 *
	 * WARNING: these variables still need to be checked and sanitized.
	 *
	 * @param string $var_name The name of the variable to get.
	 * @return mixed
	 */
	public static function get_request_var( $var_name ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- This is a utility function that returns raw request data. Nonce verification and sanitization must be done by the caller.
		if ( isset( $_GET[ $var_name ] ) ) {
			return wp_unslash( $_GET[ $var_name ] );
		} elseif ( isset( $_POST[ $var_name ] ) ) {
			return wp_unslash( $_POST[ $var_name ] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		return null;
	}

	/**
	 * Check if an array is associative.
	 *
	 * @param array $check_array The array to check.
	 * @return bool
	 */
	public static function is_assoc_array( $check_array ) {
		return is_array( $check_array ) && array_keys( $check_array ) !== range( 0, count( $check_array ) - 1 );
	}

	/**
	 * Check if the current request is a frontend request.
	 *
	 * Excludes ajax, rest and cron requests explicitly.
	 *
	 * @return bool
	 */
	public static function is_frontend_request() {
		return ( ! is_admin() || wp_doing_ajax() ) && ! wp_is_serving_rest_request() && ! wp_doing_cron() && ! wp_is_json_request();
	}

	/**
	 * Check if we're only in the admin, exclude AJAX and REST requests.
	 *
	 * @return bool
	 */
	public static function is_admin_only() {
		return is_admin() && ! wp_doing_ajax() && ! wp_is_serving_rest_request() && ! wp_doing_cron() && ! wp_is_json_request();
	}

	/**
	 * Check if we're only on the frontend, exclude AJAX, REST, cron and JSON requests.
	 *
	 * @return bool
	 */
	public static function is_frontend_only() {
		return ! is_admin() && ! wp_doing_ajax() && ! wp_is_serving_rest_request() && ! wp_doing_cron() && ! wp_is_json_request();
	}
}
