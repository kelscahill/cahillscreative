<?php
/**
 * Class for handling interacting with the options table.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Data_Store;
use Search_Filter\Database\Queries\Options as Options_Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class for managing the options table.
 */
class Options {
	/**
	 * Get an option by name.
	 *
	 * @param string $name The name of the option to get.
	 *
	 * @return mixed The option value.
	 */
	private static function get_option( $name ) {
		$option = Data_Store::get( 'options', $name );

		if ( $option ) {
			return $option;
		}

		$query_args = array(
			'number' => 1, // Only retrieve a single record.
			'name'   => $name,
		);
		/**
		 * TODO - we probably want to wrap this in our settings API
		 * so we never call the same field twice (maybe we need to
		 * update the API to support searching for fields without
		 * query ID)
		 */
		$query = new Options_Query( $query_args );

		// Bail if nothing found.
		if ( empty( $query->items ) ) {
			return false;
		}

		return $query->items[0];
	}

	/**
	 * Update an option value.
	 *
	 * @param string $name  The name of the option to update.
	 *
	 * @return mixed The option value.
	 */
	public static function get_option_value( $name ) {
		$option = self::get_option( $name );
		if ( $option ) {
			return $option->get_value();
		}
		return false;
	}

	/**
	 * Update an option value.
	 *
	 * @param string $name  The name of the option to update.
	 * @param mixed  $value The value to update the option to.
	 *
	 * @return mixed The result of the update.
	 */
	public static function update_option_value( $name, $value ) {

		$query = new Options_Query();

		// Any data thats not scalar will be json encoded.
		if ( ! is_scalar( $value ) ) {
			// Cast to object to preserve proper associative array indexes.
			// TODO - this means we can't use regular arrays.
			$value = wp_json_encode( (object) $value );
		}

		$updated_option = array(
			'name'  => $name,
			'value' => $value,
		);

		$option = self::get_option( $name );
		if ( $option ) {
			$result = $query->update_item( $option, $updated_option );
		} else {
			$result = $query->add_item( $updated_option );
		}
		return $result;
	}
	/**
	 * Create an option value.
	 *
	 * @param string $name  The name of the option to create.
	 * @param mixed  $value The value of the option to create.
	 *
	 * @return mixed The result of the update.
	 */
	public static function create_option_value( $name, $value ) {
		$query = new Options_Query();

		// Any data thats not scalar will be json encoded.
		if ( ! is_scalar( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$updated_option = array(
			'name'  => $name,
			'value' => $value,
		);

		$result = $query->add_item_with_exceptions( $updated_option );
		return $result;
	}

	/**
	 * Delete an option value.
	 *
	 * @param string $name The name of the option to delete.
	 *
	 * @return bool True if successfully deleted, false if not.
	 */
	public static function delete_option( $name ) {
		$option = self::get_option( $name );
		if ( ! $option ) {
			return false;
		}
		$query = new Options_Query();
		return $query->delete_item( $option->get_id() );
	}

	/**
	 * Check if the option transient time has expired.
	 *
	 * Used when we want to cache data for a short period of time to avoid
	 * expensive computations.
	 *
	 * @since 3.0.0
	 *
	 * @param string $option    The option to check.
	 * @param int    $expires_limit    The time limit in seconds.
	 * @return bool    True if the option transient time has expired.
	 */
	public static function has_option_transient_time_expired( $option, $expires_limit = 120 ) {
		if ( $option && isset( $option['time'] ) ) {
			$progress_time = absint( $option['time'] );
			$expire_time   = $progress_time + $expires_limit;
			// If its been less than 10 seconds, then used the stored value.
			if ( time() < $expire_time ) {
				return false;
			}
		}
		return true;
	}
}
