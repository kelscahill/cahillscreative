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
use Search_Filter\Database\Queries\Options_Direct;
use Search_Filter\Database\Rows\Option as Option_Row;
use Search_Filter\Database\Table_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class for managing the options table.
 */
class Options {
	/**
	 * Default options.
	 *
	 * Store default/fallback values so we don't need to re-run queries
	 * allowing us to return early once the default value has been set.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private static $last_defaults = array();
	/**
	 * Initialize the Options class.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		// Register table with Table_Manager.
		add_action( 'search-filter/schema/register', array( __CLASS__, 'register_tables' ) );
	}
	/**
	 * Register and init the options tables.
	 *
	 * @since    3.2.0
	 */
	public static function register_tables() {
		Table_Manager::register( 'options', \Search_Filter\Database\Tables\Options::class, true );
	}

	/**
	 * Reset the Options class state.
	 *
	 * Clears the last_defaults cache. Used for testing to ensure
	 * fresh state between tests.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$last_defaults = array();
	}

	/**
	 * Get an option by name.
	 *
	 * @param string $name The name of the option to get.
	 *
	 * @return mixed The option value.
	 */
	private static function get_record( $name ) {
		$option = Data_Store::get( 'option', $name );

		if ( $option ) {
			return $option;
		}

		$query_args = array(
			'number' => 1, // Only retrieve a single record.
			'name'   => $name,
		);

		$query = new Options_Query( $query_args );

		// Bail if nothing found.
		if ( empty( $query->items ) ) {
			return null;
		}

		return $query->items[0];
	}

	/**
	 * Update an option value.
	 *
	 * @param string $name  The name of the option to update.
	 * @param mixed  $default_value The default fallback value.
	 * @param bool   $should_create Whether to create the option if it doesn't exist - requires a default value.
	 *
	 * @return mixed The option value.
	 */
	public static function get( string $name, $default_value = null, $should_create = false ) {

		// The default value only ever gets set if the option never existed
		// so its safe to return early if we have it already.
		// TODO - we should allow an override to ensure a fresh refetch.
		if ( isset( self::$last_defaults[ $name ] ) ) {
			return self::$last_defaults[ $name ];
		}

		$option = self::get_record( $name );
		if ( $option ) {
			return $option->get_value();
		}

		if ( $should_create && $default_value !== null ) {
			self::create( $name, $default_value );

			// Now try to get the proper option record again.
			$option = self::get_record( $name );
			if ( $option ) {
				return $option->get_value();
			}
		}

		// If no option found, return default value.
		if ( $default_value !== null ) {
			self::$last_defaults[ $name ] = $default_value;
			return $default_value;
		}

		return null;
	}

	/**
	 * Get an option directly from the database, bypassing all caches.
	 *
	 * Use this for upgrade routines where preloaded defaults would interfere.
	 *
	 * @since 3.2.0
	 *
	 * @param string $name The option name.
	 * @return mixed|null The option value or null if not found.
	 */
	public static function get_direct( string $name ) {
		$option = Options_Direct::get( $name );
		if ( ! $option ) {
			return null;
		}
		return $option->get_value();
	}

	/**
	 * Legacy: get an option by name.
	 *
	 * Fallback to support the old api.
	 *
	 * @param string $name  The name of the option to retrieve.
	 *
	 * @deprecated 3.2.0
	 *
	 * @return mixed The option value.
	 */
	public static function get_option_value( string $name ) {
		return self::get( $name );
	}

	/**
	 * Update an option value.
	 *
	 * @param string $name  The name of the option to update.
	 * @param mixed  $value The value to update the option to.
	 *
	 * @return mixed The result of the update.
	 */
	public static function update( string $name, $value ) {
		// Clear the last_defaults cache for this option.
		unset( self::$last_defaults[ $name ] );

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

		$option = self::get_record( $name );

		$option_id = null;
		if ( $option ) {
			$result    = $query->update_item( $option, $updated_option );
			$option_id = $option->get_id();
		} else {
			$result    = $query->add_item( $updated_option );
			$option_id = $result;
		}
		if ( $option_id ) {
			self::create_option_row( $option_id, $name, $value );
		}
		return $result;
	}

	/**
	 * Legacy: update an option by name.
	 *
	 * Fallback to support the old api.
	 *
	 * @deprecated 3.2.0
	 *
	 * @param string $name  The name of the option to update.
	 * @param mixed  $value The default fallback value.
	 *
	 * @return mixed The option value.
	 */
	public static function update_option_value( string $name, $value ) {
		return self::update( $name, $value );
	}

	/**
	 * Create an option value.
	 *
	 * @param string $name  The name of the option to create.
	 * @param mixed  $value The value of the option to create.
	 *
	 * @return mixed The result of the update.
	 */
	public static function create( string $name, $value ) {
		$query = new Options_Query();

		// Any data thats not scalar will be json encoded.
		if ( ! is_scalar( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$updated_option = array(
			'name'  => $name,
			'value' => $value,
		);

		$result    = $query->add_item_with_exceptions( $updated_option );
		$option_id = $result;
		if ( is_int( $option_id ) && $option_id > 0 ) {
			self::create_option_row( $option_id, $name, $value );
			// Clear the last_defaults cache for this option.
			unset( self::$last_defaults[ $name ] );
		}
		return $result;
	}

	/**
	 * Legacy: create an option by name.
	 *
	 * Fallback to support the old api.
	 *
	 * @deprecated 3.2.0
	 *
	 * @param string $name  The name of the option to update.
	 * @param mixed  $value The default fallback value.
	 *
	 * @return mixed The option value.
	 */
	public static function create_option_value( string $name, $value ) {
		return self::create( $name, $value );
	}


	/**
	 * Delete an option value.
	 *
	 * @param string $name The name of the option to delete.
	 *
	 * @return bool True if successfully deleted, false if not.
	 */
	public static function delete( $name ) {
		$option = self::get_record( $name );
		if ( ! $option ) {
			return false;
		}
		$query = new Options_Query();

		Data_Store::forget( 'option', $name );
		// Clear the last_defaults cache for this option.
		unset( self::$last_defaults[ $name ] );

		return $query->delete_item( $option->get_id() );
	}

	/**
	 * Legacy: delete an option by name.
	 *
	 * Fallback to support the old api.
	 *
	 * @deprecated 3.2.0
	 *
	 * @param string $name  The name of the option to delete.
	 *
	 * @return bool True if successfully deleted, false if not.
	 */
	public static function delete_option( string $name ) {
		return self::delete( $name );
	}

	/**
	 * Create an option row.
	 *
	 * @param int    $id    The ID of the option.
	 * @param string $name  The name of the option.
	 * @param mixed  $value The value of the option.
	 *
	 * @return Option_Row The option row.
	 */
	private static function create_option_row( int $id, string $name, $value ) {
		$option_item        = new \stdClass();
		$option_item->id    = $id;
		$option_item->name  = $name;
		$option_item->value = $value;
		// Creating a new option row automatically adds it to the Data_Store
		// for re-use later.
		$option_row = new Option_Row( $option_item );
		return $option_row;
	}

	/**
	 * Preload multiple options at once.
	 *
	 * This function fetches multiple options in a single database query
	 * and stores them in the Data_Store for later use.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options_to_preload Array of options with defaults to preload.
	 *                  Each item can be either a string (option name) or an array with
	 *                  the first element as the option name and the second as the default value.
	 * @return array Array of loaded options.
	 */
	public static function preload( $options_to_preload = array() ) {
		$options_to_preload = apply_filters( 'search-filter/options/preload', $options_to_preload );

		// Setup the options using their defaults.
		$option_values    = array();
		$options_to_fetch = array();
		foreach ( $options_to_preload as $option_config ) {
			// Handle string / option name only.
			if ( is_string( $option_config ) ) {
				$options_to_fetch[] = $option_config;
				continue;
			}
			if ( is_array( $option_config ) ) {
				$name               = $option_config[0];
				$options_to_fetch[] = $name;

				// If there is a default,  update the cache and preload the value.
				if ( array_key_exists( 1, $option_config ) ) {
					self::$last_defaults[ $name ] = $option_config[1];
					$option_values[ $name ]       = $option_config[1];
				}
			}
		}

		$query_args = array(
			'name__in' => $options_to_fetch,
		);

		// Running the query will initialise the rows for each result,
		// storing the option in the data store automatically.
		$query = new Options_Query( $query_args );

		if ( ! empty( $query->items ) ) {
			foreach ( $query->items as $option ) {
				$name = $option->get_name();
				// Update the value with the found value.
				$option_values[ $name ] = $option->get_value();
				// If the actual option existed, unset the default cache.
				unset( self::$last_defaults[ $name ] );
			}
		}

		return $option_values;
	}
}
