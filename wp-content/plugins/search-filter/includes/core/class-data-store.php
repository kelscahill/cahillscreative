<?php
/**
 * Scripts Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A class for storing data and re-using data, to avoid making repeated (more expensive) calls such as to the database.
 *
 * Aim to store the raw data/repsonse in the class.
 */
class Data_Store {
	/**
	 * The main object that holds a reference to all the data.
	 *
	 * @var array
	 */
	private static $data = array(
		'field'  => array(),
		'query'  => array(),
		'style'  => array(),
		'option' => array(),
	);
	/**
	 * Get data from the store.
	 *
	 * @param string     $type The type of data to get.
	 * @param string|int $key  The key to get.
	 *
	 * @return mixed|null The data if found, null if not.
	 */
	public static function get( string $type, $key ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			return null;
		}
		if ( ! isset( self::$data[ $type ][ $key ] ) ) {
			return null;
		}
		return self::$data[ $type ][ $key ];
	}
	/**
	 * Set data in the store.
	 *
	 * @param string     $type  The type of data to set.
	 * @param string|int $key   The key to set.
	 * @param mixed      $value The value to set.
	 */
	public static function set( string $type, $key, $value ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			self::$data[ $type ] = array();
		}
		self::$data[ $type ][ $key ] = $value;
	}

	/**
	 * Remove the an item with with given key.
	 *
	 * @param string     $type  The type of data to remove e.i field, query, style.
	 * @param string|int $key The key to remove.
	 * @return void
	 */
	public static function forget( string $type, $key ) {
		unset( self::$data[ $type ][ $key ] );
	}

	/**
	 * Remove all items with with given type.
	 *
	 * @param string $type  The type of data to remove e.i field, query, style.
	 * @return void
	 */
	public static function flush( string $type ) {
		unset( self::$data[ $type ] );
	}

	/**
	 * Reset the data store, clearing all cached data.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$data = array();
	}
}
