<?php
/**
 * A Class for WP functions to prevent repeated calls for the same information
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
 * A simple class for requesting commonly used WP data, first
 * storing a copy of the result, and then re-using that copy
 * to save overhead.
 *
 * Gains especially visible when not using server based caching
 * and calling large amounts of data (ie large lists of taxonomy
 * terms)
 */
class WP_Data {

	/**
	 * Internal copy of post types requested.
	 *
	 * @var array
	 */
	private static $post_types = array();
	/**
	 * Internal copy of post statis requested.
	 *
	 * @var array
	 */
	private static $post_stati = array();
	/**
	 * Internal copy of taxonomy + their terms.
	 *
	 * @var array
	 */
	private static $terms = array();
	/**
	 * Internal copy of taxonomies.
	 *
	 * @var array
	 */
	private static $taxonomies = array();

	/**
	 * A wrapper for the WP function `get_post_types`
	 *
	 * @return array
	 */
	public static function get_post_types( $args = array(), $operator = 'and' ) {

		$default_args = array( 'public' => true );
		$args         = wp_parse_args( $args, $default_args );
		$key          = md5( serialize( $args ) ) . '_' . $operator;
		if ( ! isset( self::$post_types[ $key ] ) ) {
			self::$post_types[ $key ] = get_post_types( $args, 'objects', $operator );
		}
		return self::$post_types[ $key ];
	}
	/**
	 * A wrapper for the WP function `get_post_stati`
	 *
	 * @return array
	 */
	public static function get_post_stati() {

		if ( empty( self::$post_stati ) ) {

			$post_stati_all    = get_post_stati( array(), 'objects' );
			$post_stati_ignore = array( 'auto-draft', 'inherit' );
			$post_stati        = array();

			foreach ( $post_stati_all as $post_status_key => $post_status ) {

				// Don't add any from the ignore list.
				if ( ! in_array( $post_status_key, $post_stati_ignore, true ) ) {
					array_push( $post_stati, $post_status );
				}
			}

			self::$post_stati = $post_stati;
		}

		return self::$post_stati;
	}

	/**
	 * A wrapper for the WP function `get_users`
	 *
	 * @return array
	 */
	public static function get_post_authors( $args = array() ) {
		$post_authors = array();

		$author_args = array(
			'fields'  => array( 'ID', 'display_name', 'user_nicename' ),
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'number'  => -1,
		);

		$author_args = wp_parse_args( $args, $author_args );

		$post_authors = get_users(
			$author_args
		);

		return $post_authors;
	}
	/**
	 * A wrapper for the WP function `get_post_types`
	 *
	 * @return array
	 */
	public static function get_taxonomies() {
		if ( empty( self::$taxonomies ) ) {
			$args             = array();
			self::$taxonomies = get_taxonomies( $args, 'objects' );
		}

		return self::$taxonomies;
	}
	/**
	 * A wrapper for the WP function `get_terms`
	 *
	 * @param array $args  The args passed to get_terms.
	 *
	 * @return array
	 */
	public static function get_terms( $args ) {
		$key = md5( serialize( $args ) );
		if ( ! isset( self::$terms[ $key ] ) ) {
			self::$terms[ $key ] = get_terms( $args );
		}
		return self::$terms[ $key ];
	}
}
