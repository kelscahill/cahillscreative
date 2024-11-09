<?php
/**
 * Class for keeping track for the render data for queries.
 *
 * It can be necessary to keep track of data that is relevant to rendering, but not
 * necessarily part of the query itself, usually this is temporary (on page render)
 * data that is not saved to the DB, but is influenced by something else in the
 * current page.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Queries;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base field all queries.
 */
class Query_Render_Store {

	/**
	 * Keeps track of the render data for a query.
	 *
	 * @var array
	 */
	private static $render_data = array();

	/**
	 * Gets the render data for a query.
	 *
	 * @param string $query_id The query ID.
	 * @return array
	 */
	public static function get_render_data( $query_id ) {
		if ( ! isset( self::$render_data[ $query_id ] ) ) {
			return array();
		}
		return self::$render_data[ $query_id ];
	}
	/**
	 * Sets the render data for a query.
	 *
	 * @param string $query_id The query ID.
	 * @param string $key      The key to set.
	 * @param mixed  $data     The data to set.
	 */
	public static function set_render_data_value( $query_id, $key, $data ) {
		if ( ! isset( self::$render_data[ $query_id ] ) ) {
			self::$render_data[ $query_id ] = array();
		}
		self::$render_data[ $query_id ][ $key ] = $data;
	}

	/**
	 * Gets a value from the render data.
	 *
	 * @param string $query_id The query ID.
	 * @param string $key      The key to get.
	 * @return mixed
	 */
	public static function get_render_data_value( $query_id, $key ) {
		if ( ! isset( self::$render_data[ $query_id ] ) ) {
			return false;
		}
		if ( ! isset( self::$render_data[ $query_id ][ $key ] ) ) {
			return false;
		}
		return self::$render_data[ $query_id ][ $key ];
	}
}
