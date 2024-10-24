<?php
namespace Search_Filter_Pro\Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store a copy of each instantiated indexer query to prevent
 * multiple instances of the same query being created.
 *
 * @since 3.0.0
 */
class Query_Store {

	/**
	 * List of instantiated indexer queries.
	 *
	 * @since 3.0.0
	 *
	 * @var Search_Filter\Queries\Query
	 */
	private static $queries = array();

	/**
	 * Add an instantiated indexer query.
	 *
	 * @since 3.0.0
	 *
	 * @param Search_Filter\Queries\Query $query The S&F query object.
	 */
	public static function add_query( $query ) {
		self::$queries[ $query->get_id() ] = $query;
	}

	/**
	 * Get an instantiated indexer query.
	 *
	 * @since 3.0.0
	 *
	 * @param int $query_id The S&F query ID.
	 * @return Search_Filter\Queries\Query|null The S&F query object or null if not found.
	 */
	public static function get_query( $query_id ) {
		if ( isset( self::$queries[ $query_id ] ) ) {
			return self::$queries[ $query_id ];
		}
		return null;
	}

	/**
	 * Remove an instantiated indexer query.
	 *
	 * @since 3.0.0
	 *
	 * @param int $query_id The S&F query ID.
	 */
	public static function remove_query( $query_id ) {
		if ( isset( self::$queries[ $query_id ] ) ) {
			unset( self::$queries[ $query_id ] );
		}
	}

	/**
	 * Update an instantiated indexer query.
	 *
	 * @since 3.0.0
	 *
	 * @param Search_Filter\Queries\Query $query The S&F query object.
	 */
	public static function update_query( $query ) {
		$query_id = $query->get_id();
		if ( isset( self::$queries[ $query_id ] ) ) {
			self::$queries[ $query_id ] = $query;
		}
	}

	/**
	 * Has query
	 *
	 * @since 3.0.0
	 *
	 * @param int $query_id The S&F query ID.
	 * @return bool True if the query exists.
	 */
	public static function has_query( $query_id ) {
		return isset( self::$queries[ $query_id ] );
	}
}
