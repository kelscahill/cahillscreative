<?php
/**
 * Base Custom Database Table Range Query Class.
 */
namespace Search_Filter\Database\Queries;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Base;

/**
 * Class for generating SQL clauses that filter a primary query according to date.
 *
 * Is heavily inspired by the WP_Date_Query class in WordPress, with changes to make
 * it more flexible for custom tables and their columns.
 *
 * Date is a helper that allows primary query classes, such as WP_Query, to filter
 * their results by date columns, by generating `WHERE` subclauses to be attached to the
 * primary SQL query string.
 *
 * Attempting to filter by an invalid date value (eg month=13) will generate SQL that will
 * return no results. In these cases, a _doing_it_wrong() error notice is also thrown.
 * See Date::validate_date_values().
 *
 * @link https://developer.wordpress.org/reference/classes/wp_query/
 *
 * @since 1.0.0
 */
class Range extends Base {

	/**
	 * Array of date queries.
	 *
	 * See Date::__construct() for information on date query arguments.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $queries = array();

	/**
	 * The default relation between top-level queries. Can be either 'AND' or 'OR'.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $relation = 'AND';

	/**
	 * The column to query against. Can be changed via the query arguments.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $column = 'id';

	/**
	 * The value comparison operator. Can be changed via the query arguments.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $compare = '=';


	/**
	 * Supported comparison types
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $comparison_keys = array(
		'=',
		'!=',
		'>',
		'>=',
		'<',
		'<=',
		'IN',
		'NOT IN',
		'BETWEEN',
		'NOT BETWEEN',
	);

	/**
	 * Supported multi-value comparison types
	 *
	 * @since 1.1.0
	 * @var   array
	 */
	public $multi_value_keys = array(
		'IN',
		'NOT IN',
		'BETWEEN',
		'NOT BETWEEN',
	);

	/**
	 * Supported relation types
	 *
	 * @since 1.1.0
	 * @var   array
	 */
	public $relation_keys = array(
		'OR',
		'AND',
	);

	/**
	 * Supported range-related parameter keys.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $range_keys = array(
		'value',
	);

	/**
	 * Constructor.
	 *
	 * Time-related parameters that normally require integer values ('year', 'month', 'week', 'dayofyear', 'day',
	 * 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second') accept arrays of integers for some values of
	 * 'compare'. When 'compare' is 'IN' or 'NOT IN', arrays are accepted; when 'compare' is 'BETWEEN' or 'NOT
	 * BETWEEN', arrays of two valid values are required. See individual argument descriptions for accepted values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $range_query {
	 *     Array of date query clauses.
	 *
	 *     @type array ...$0 {
	 *         @type string $column           Optional. The column to query against. If undefined, inherits the value of
	 *                                        'date_created'. Accepts 'date_created', 'date_created_gmt',
	 *                                        'post_modified','post_modified_gmt', 'comment_date', 'comment_date_gmt'.
	 *                                        Default 'date_created'.
	 *         @type string $compare          Optional. The comparison operator. Accepts '=', '!=', '>', '>=', '<', '<=',
	 *                                        'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'. Default '='.
	 *         @type string $relation         Optional. The boolean relationship between the date queries. Accepts 'OR' or 'AND'.
	 *                                        Default 'OR'.
	 *
	 *         @type array  ...$0 {
	 *             Optional. An array of first-order clause parameters, or another fully-formed date query.
	 *
	 *             @type string|array $before {
	 *                 Optional. Date to retrieve posts before. Accepts `strtotime()`-compatible string,
	 *                 or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year. Default empty. Accepts any four-digit year.
	 *                 @type string $month Optional when passing array.The month of the year.
	 *                                     Default (string:empty)|(array:1). Accepts numbers 1-12.
	 *                 @type string $day   Optional when passing array.The day of the month.
	 *                                     Default (string:empty)|(array:1). Accepts numbers 1-31.
	 *             }
	 *             @type string|array $after {
	 *                 Optional. Date to retrieve posts after. Accepts `strtotime()`-compatible string,
	 *                 or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year. Accepts any four-digit year. Default empty.
	 *                 @type string $month Optional when passing array. The month of the year. Accepts numbers 1-12.
	 *                                     Default (string:empty)|(array:12).
	 *                 @type string $day   Optional when passing array.The day of the month. Accepts numbers 1-31.
	 *                                     Default (string:empty)|(array:last day of month).
	 *             }
	 *             @type string       $column        Optional. Used to add a clause comparing a column other than the
	 *                                               column specified in the top-level `$column` parameter. Accepts
	 *                                               'date_created', 'date_created_gmt', 'post_modified', 'post_modified_gmt',
	 *                                               'comment_date', 'comment_date_gmt'. Default is the value of
	 *                                               top-level `$column`.
	 *             @type string       $compare       Optional. The comparison operator. Accepts '=', '!=', '>', '>=',
	 *                                               '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'. 'IN',
	 *                                               'NOT IN', 'BETWEEN', and 'NOT BETWEEN'. Comparisons support
	 *                                               arrays in some time-related parameters. Default '='.
	 *
	 *             @type bool         $inclusive     Optional. Include results from dates specified in 'before' or
	 *                                               'after'. Default false.
	 *             @type int|array    $year          Optional. The four-digit year number. Accepts any four-digit year
	 *                                               or an array of years if `$compare` supports it. Default empty.
	 *             @type int|array    $month         Optional. The two-digit month number. Accepts numbers 1-12 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $week          Optional. The week number of the year. Accepts numbers 0-53 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $dayofyear     Optional. The day number of the year. Accepts numbers 1-366 or an
	 *                                               array of valid numbers if `$compare` supports it.
	 *             @type int|array    $day           Optional. The day of the month. Accepts numbers 1-31 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $dayofweek     Optional. The day number of the week. Accepts numbers 1-7 (1 is
	 *                                               Sunday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $dayofweek_iso Optional. The day number of the week (ISO). Accepts numbers 1-7
	 *                                               (1 is Monday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $hour          Optional. The hour of the day. Accepts numbers 0-23 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $minute        Optional. The minute of the hour. Accepts numbers 0-60 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $second        Optional. The second of the minute. Accepts numbers 0-60 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *         }
	 *     }
	 * }
	 */
	public function __construct( $range_query = array() ) {

		// Bail if empty or not an array.
		if ( empty( $range_query ) || ! is_array( $range_query ) ) {
			return;
		}

		// Set column, compare, relation.
		$this->column   = $this->get_column( $range_query );
		$this->compare  = $this->get_compare( $range_query );
		$this->relation = $this->get_relation( $range_query );

		// Set the queries
		$this->queries = $this->sanitize_query( $range_query );
	}

	/**
	 * Recursive-friendly query sanitizer.
	 *
	 * Ensures that each query-level clause has a 'relation' key, and that
	 * each first-order clause contains all the necessary keys from
	 * `$defaults`.
	 *
	 * @since 1.0.0
	 *
	 * @param array $queries
	 * @param array $parent_query
	 *
	 * @return array Sanitized queries.
	 */
	public function sanitize_query( $queries = array(), $parent_query = array() ) {

		// Default return value.
		$retval = array();

		// Setup defaults.
		$defaults = array(
			'column'   => $this->get_column(),
			'compare'  => $this->get_compare(),
			'relation' => $this->get_relation(),
		);

		// Numeric keys should always have array values.
		foreach ( $queries as $qkey => $qvalue ) {
			if ( is_numeric( $qkey ) && ! is_array( $qvalue ) ) {
				unset( $queries[ $qkey ] );
			}
		}

		// Each query should have a value for each default key.
		// Inherit from the parent when possible.
		foreach ( $defaults as $dkey => $dvalue ) {

			// Skip if already set.
			if ( isset( $queries[ $dkey ] ) ) {
				continue;
			}

			// Set the query.
			if ( isset( $parent_query[ $dkey ] ) ) {
				$queries[ $dkey ] = $parent_query[ $dkey ];
			} else {
				$queries[ $dkey ] = $dvalue;
			}
		}

		// Add queries to return array.
		foreach ( $queries as $key => $q ) {
			// This is a first-order query. Trust the values and sanitize when building SQL.
			if ( ! is_array( $q ) ) {
				$retval[ $key ] = $q;
				// Any array without a time key is another query, so we recurse.
			} else {
				$retval[] = $this->sanitize_query( $q, $queries );
			}
		}

		// Return sanitized queries.
		return $retval;
	}

	/**
	 * Determines and validates what comparison operator to use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query A date query or a date subquery.
	 *
	 * @return string The comparison operator.
	 */
	public function get_column( $query = array() ) {

		// Use column if passed
		$retval = ! empty( $query['column'] )
			? esc_sql( $this->validate_column( $query['column'] ) )
			: $this->column;

		return $retval;
	}

	/**
	 * Determines and validates what comparison operator to use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query A date query or a date subquery.
	 *
	 * @return string The comparison operator.
	 */
	public function get_compare( $query = array() ) {

		// Compare must be in the allowed array.
		$retval = ! empty( $query['compare'] ) && in_array( $query['compare'], $this->comparison_keys, true )
			? strtoupper( $query['compare'] )
			: $this->compare;

		return $retval;
	}

	/**
	 * Determines and validates what relation to use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query A date query or a date subquery.
	 * @return string The relation operator.
	 */
	public function get_relation( $query = array() ) {

		// Relation must be in the allowed array
		$retval = ! empty( $query['relation'] ) && in_array( $query['relation'], $this->relation_keys, true )
			? strtoupper( $query['relation'] )
			: $this->relation;

		return $retval;
	}

	/**
	 * Validates a column name parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column The user-supplied column name.
	 *
	 * @return string A validated column name value.
	 */
	public function validate_column( $column = '' ) {
		return preg_replace( '/[^a-zA-Z0-9_$\.]/', '', $column );
	}

	/**
	 * Generate WHERE clause to be appended to a main query.
	 *
	 * @since 1.0.0
	 *
	 * @return string MySQL WHERE clauses.
	 */
	public function get_sql() {
		$sql = $this->get_sql_clauses();

		/**
		 * Filters the date query clauses.
		 *
		 * @since 1.0.0
		 *
		 * @param string $sql Clauses of the date query.
		 * @param Date   $this  The Date query instance.
		 */
		return apply_filters( 'get_range_sql', $sql, $this );
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public Date::get_sql(), this method is abstracted
	 * out to maintain parity with the other Query classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses() {
		$sql = $this->get_sql_for_query( $this->queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return apply_filters( 'get_range_sql_clauses', $sql, $this );
	}
	/**
	 * Determine whether this is a first-order clause.
	 *
	 * Checks to see if the current clause has any range-related keys.
	 * If so, it's first-order.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $query Query clause.
	 *
	 * @return bool True if this is a first-order clause.
	 */
	protected function is_first_order_clause( $query = array() ) {
		$range_keys = array_intersect( $this->range_keys, array_keys( $query ) );
		return ! empty( $range_keys );
	}
	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query Query to parse.
	 * @param int   $depth Optional. Number of tree levels deep we currently are.
	 *                     Used to calculate indentation. Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( $query = array(), $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= '  ';
		}

		foreach ( $query as $key => $clause ) {

			if ( 'relation' === $key ) {
				$relation = $query['relation'];

			} elseif ( is_array( $clause ) ) {
				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) {
					// Get clauses & where count
					$clause_sql  = $this->get_sql_for_clause( $clause, $query );
					$where_count = count( $clause_sql['where'] );

					if ( 0 === $where_count ) {
						$sql_chunks['where'][] = '';

					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];

					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );

					// This is a subquery, so we recurse.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		// Filter and return
		return apply_filters( 'get_range_sql_for_query', $sql, $query, $depth, $this );
	}

	/**
	 * Turns a first-order date query into SQL for a WHERE clause.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $query        Date query clause.
	 * @param  array $parent_query Parent query of the current date query.
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_clause( $query = array(), $parent_query = array() ) {

		// The sub-parts of a $where part.
		$where_parts = array();

		// Get first-order clauses
		$column    = $this->get_column( $query );
		$compare   = $this->get_compare( $query );
		$inclusive = ! empty( $query['inclusive'] );

		// Assign greater-than and less-than values.
		$lt = '<';
		$gt = '>';

		if ( true === $inclusive ) {
			$lt .= '=';
			$gt .= '=';
		}

		// Straight value compare.
		if ( isset( $query['value'] ) ) {
			$cast_type = 'SIGNED';
			if ( isset( $query['type'] ) ) {
				$cast_type = sanitize_text_field( $query['type'] );
			} elseif ( isset( $query['decimals'] ) ) {
				$decimal_places = absint( $query['decimals'] );
				if ( $decimal_places > 0 ) {
					$cast_type = "DECIMAL(10, $decimal_places)";
				}
			}
			$value         = $this->build_value( $compare, $query['value'] );
			$where_parts[] = "CAST( {$column} AS $cast_type ) {$compare} $value";
		}

		/*
		 * Return an array of 'join' and 'where' for compatibility
		 * with other query classes.
		 */
		return array(
			'where' => $where_parts,
			'join'  => array(),
		);
	}

	/**
	 * Builds and validates a value string based on the comparison operator.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $compare The compare operator to use
	 * @param string|array $value The value
	 *
	 * @return string|false|int The value to be used in SQL or false on error.
	 */
	public function build_numeric_value( $compare = '=', $value = null ) {

		// Bail if null value.
		if ( is_null( $value ) ) {
			return false;
		}

		switch ( $compare ) {
			case 'IN':
			case 'NOT IN':
				$value = (array) $value;

				// Remove non-numeric values.
				$value = array_filter( $value, 'is_numeric' );

				if ( empty( $value ) ) {
					return false;
				}

				return '(' . implode( ',', array_map( 'intval', $value ) ) . ')';

			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) || ( 2 !== count( $value ) ) ) {
					$value = array( $value, $value );
				} else {
					$value = array_values( $value );
				}

				// If either value is non-numeric, bail.
				foreach ( $value as $v ) {
					if ( ! is_numeric( $v ) ) {
						return false;
					}
				}

				$value = array_map( 'intval', $value );

				return $value[0] . ' AND ' . $value[1];

			default:
				if ( ! is_numeric( $value ) ) {
					return false;
				}

				return (int) $value;
		}
	}

	/**
	 * Builds and validates a value string based on the comparison operator.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $compare The compare operator to use
	 * @param string|array $value The value
	 *
	 * @return string|false|int The value to be used in SQL or false on error.
	 */
	public function build_value( $compare = '=', $value = null ) {

		if ( in_array( $compare, $this->multi_value_keys, true ) ) {
			if ( ! is_array( $value ) ) {
				$value = preg_split( '/[,\s]+/', $value );
			}
		} else {
			$value = trim( $value );
		}

		switch ( $compare ) {
			case 'IN':
			case 'NOT IN':
				$compare_string = '(' . substr( str_repeat( ',%s', count( $value ) ), 1 ) . ')';
				$where          = $this->get_db()->prepare( $compare_string, $value );
				break;

			case 'BETWEEN':
			case 'NOT BETWEEN':
				$value = array_slice( $value, 0, 2 );
				$where = $this->get_db()->prepare( '%s AND %s', $value );
				break;

			case 'LIKE':
			case 'NOT LIKE':
				$value = '%' . $this->get_db()->esc_like( $value ) . '%';
				$where = $this->get_db()->prepare( '%s', $value );
				break;

			// EXISTS with a value is interpreted as '='.
			case 'EXISTS':
				$compare = '=';
				$where   = $this->get_db()->prepare( '%s', $value );
				break;

			// 'value' is ignored for NOT EXISTS.
			case 'NOT EXISTS':
				$where = '';
				break;

			default:
				$where = $this->get_db()->prepare( '%s', $value );
				break;
		}

		return $where;
	}
}
