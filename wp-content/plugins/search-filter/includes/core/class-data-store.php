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
		'field'   => array(),
		'query'   => array(),
		'style'   => array(),
		'options' => array(),
	);
	/**
	 * Get data from the store.
	 *
	 * @param string $type The type of data to get.
	 * @param string $key  The key to get.
	 *
	 * @return mixed|false
	 */
	public static function get( $type, $key ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			return false;
		}
		if ( ! isset( self::$data[ $type ][ $key ] ) ) {
			return false;
		}
		return self::$data[ $type ][ $key ];
	}
	/**
	 * Set data in the store.
	 *
	 * @param string $type  The type of data to set.
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 */
	public static function set( $type, $key, $value ) {
		if ( ! isset( self::$data[ $type ] ) ) {
			self::$data[ $type ] = array();
		}
		self::$data[ $type ][ $key ] = $value;
	}

	/**
	 * Remove the an item with with given key.
	 *
	 * @param string $type  The type of data to remove e.i field, query, style.
	 * @param string $key The key to remove.
	 * @return void
	 */
	public static function forget( $type, $key ) {
		unset( self::$data[ $type ][ $key ] );
	}

	/**
	 * Remove all items with with given type.
	 *
	 * @param string $type  The type of data to remove e.i field, query, style.
	 * @return void
	 */
	public static function flush( $type ) {
		unset( self::$data[ $type ] );
	}

	/**
	 * Remove all data for all types.
	 *
	 * @return void
	 */
	public static function flush_all() {
		self::$data = array();
	}
}
