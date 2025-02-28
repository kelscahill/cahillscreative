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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A helper class with functions used across the plugin
 */
class Util {
	/**
	 * Stores a copy of any options retrieved to save additional calls to the same options later
	 * in page processing
	 *
	 * @var array
	 */
	private static $options = array();

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
	 * Wrapper for the WP `get_option` function, implementing defaults if they do not exist yet
	 *
	 * @param string $option_name  The option key required.
	 *
	 * @return mixed  The value for the option
	 */
	public static function get_option( $option_name ) {

		// check to see if we've looked this up before, if so return the existing value.
		if ( isset( self::$options[ $option_name ] ) ) {
			return self::$options[ $option_name ];
		}

		// TODO - set defaults externally.
		$option_defaults = array(
			'search_filter_lazy_load_js' => 0,
			'search_filter_load_js_css'  => 1,
		);

		$option_value = get_option( $option_name );

		// if option is not set, and there is a default for it, use the default.
		if ( ( false === $option_value ) && ( isset( $option_defaults[ $option_name ] ) ) ) {
			$option_value = $option_defaults[ $option_name ];
		}

		self::$options[ $option_name ] = $option_value;

		return $option_value;
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
	 * Only if WP_DEBUG is enabled.
	 *
	 * TODO - we could start tracking the issues in the DB to
	 * present to the user via admin or export file.
	 *
	 * @param string $message The error message.
	 */
	public static function error_log( $message, $level = 'error' ) {

		$log_level       = 'errors';
		$log_to_database = 'no';

		if ( did_action( 'search-filter/settings/features/init' ) && Features::is_enabled( 'debugMode' ) ) {
			$log_level = Debugger::get_setting_value( 'logLevel' );
			if ( $log_level === null ) {
				$log_level = 'errors';
			}

			$log_to_database = Debugger::get_setting_value( 'logToDatabase' );
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

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// Translators: %1$s is the process ID, %2$s is the message.
			$full_message = wp_kses_post( sprintf( '%1$sSearch & Filter: %2$s', $pid, $message ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $full_message );
		}

		if ( did_action( 'search-filter/settings/features/init' ) && Features::is_enabled( 'debugMode' ) && $log_to_database === 'yes' ) {
			$full_message = sprintf( '%1$sSearch & Filter: %2$s', $pid, $message );
			Debugger::create_log(
				array(
					'message' => sanitize_text_field( $full_message ),
					'level'   => $level,
				)
			);
		}
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
		if ( isset( $_GET[ $var_name ] ) ) {
			return $_GET[ $var_name ];
		} elseif ( isset( $_POST[ $var_name ] ) ) {
			return $_POST[ $var_name ];
		}
		return null;
	}
}
