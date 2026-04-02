<?php
/**
 * Query Optimizer Class.
 *
 * Optimizes WordPress queries with large post__in arrays using
 * temporary tables instead of IN clauses for better performance.
 *
 * @link       https://searchandfilter.com
 * @since      3.1.0
 * @package    Search_Filter_Pro/Database
 */

namespace Search_Filter_Pro\Database;

use Search_Filter_Pro\Util;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Optimizer: Optimizes large post__in queries.
 *
 * Automatically intercepts WP_Query with large post__in arrays and
 * uses temporary table JOINs instead of IN clauses for 10-100x
 * performance improvement.
 *
 * Supports:
 * - MEMORY engine temporary tables (fastest)
 * - InnoDB engine temporary tables (fallback)
 * - Query chunking (most restricted environments)
 *
 * @since 3.1.0
 */
class Query_Optimizer {

	/**
	 * Whether MEMORY engine is available.
	 *
	 * @since 3.1.0
	 * @var bool|null
	 */
	private $can_use_memory = null;

	/**
	 * Whether temporary tables are available.
	 *
	 * @since 3.1.0
	 * @var bool|null
	 */
	private $can_use_temp = null;

	/**
	 * Minimum post IDs to trigger optimization.
	 *
	 * @since 3.1.0
	 * @var int
	 */
	private $threshold = 2000;

	/**
	 * Minimum post IDs to trigger optimization.
	 *
	 * @since 3.1.0
	 * @var int
	 */
	private $batch_size = 1000;

	/**
	 * Temporary tables created (for cleanup).
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private $temp_tables = array();

	/**
	 * Singleton instance.
	 *
	 * @since 3.1.0
	 * @var Query_Optimizer|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.1.0
	 * @return Query_Optimizer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the optimizer.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		/**
		 * Filter the threshold for triggering query optimization.
		 *
		 * @since 3.1.0
		 *
		 * @param int $threshold Minimum post IDs to trigger optimization. Default 500.
		 */
		$this->threshold = apply_filters( 'search-filter-pro/database/query_optimizer/threshold', 500 );
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 3.1.0
	 */
	public function init() {
		// Hook into WordPress query system.
		add_filter( 'posts_pre_query', array( $this, 'optimize_query' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'cleanup_temp_tables' ) );
	}

	/**
	 * Main optimization handler.
	 *
	 * @since 3.1.0
	 * @param array|null $posts Posts array (null if not pre-fetched).
	 * @param \WP_Query  $query WP_Query instance.
	 * @return array|null Posts array or null to continue normal query.
	 */
	public function optimize_query( $posts, $query ) {

		// Only optimize S&F queries.
		if ( ! isset( $query->query_vars['search_filter_queries'] ) || count( $query->query_vars['search_filter_queries'] ) === 0 ) {
			return $posts;
		}

		$enabled = apply_filters(
			'search-filter-pro/database/query_optimizer/enable',
			false,
			$query
		);

		if ( ! $enabled ) {
			return $posts;
		}

		// Only optimize large post__in queries.
		if ( ! isset( $query->query_vars['post__in'] ) ||
			count( $query->query_vars['post__in'] ) < $this->threshold ) {
			return $posts;
		}
		Util::error_log(
			sprintf(
				'Optimizing query with %d post IDs',
				count( $query->query_vars['post__in'] )
			),
			'notice'
		);

		// Try optimization methods in order of performance.
		if ( $this->can_use_temp_tables() ) {
			return $this->optimize_with_temp_table( $posts, $query );
		} else {
			return $this->optimize_with_chunking( $posts, $query );
		}
	}

	/**
	 * Check if temporary tables are available.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	private function can_use_temp_tables() {
		if ( $this->can_use_temp !== null ) {
			return $this->can_use_temp;
		}

		global $wpdb;

		// Test MEMORY engine first.
		$test_table = 'sf_test_opt_' . wp_generate_password( 8, false );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "CREATE TEMPORARY TABLE {$test_table} (id INT) ENGINE=MEMORY" );

		if ( $result !== false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TEMPORARY TABLE {$test_table}" );
			$this->can_use_temp   = true;
			$this->can_use_memory = true;
			return true;
		}

		// Test InnoDB as fallback.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "CREATE TEMPORARY TABLE {$test_table} (id INT) ENGINE=InnoDB" );
		if ( $result !== false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TEMPORARY TABLE {$test_table}" );
			$this->can_use_temp   = true;
			$this->can_use_memory = false;
			return true;
		}

		$this->can_use_temp = false;
		return false;
	}

	/**
	 * Optimization using temporary tables (fastest method).
	 *
	 * @since 3.1.0
	 * @param array|null $posts Posts array.
	 * @param \WP_Query  $query WP_Query instance.
	 * @return array Posts array.
	 */
	private function optimize_with_temp_table( $posts, $query ) {
		global $wpdb;

		$post_ids   = array_map( 'intval', $query->query_vars['post__in'] );
		$temp_table = 'sf_temp_' . wp_generate_password( 12, false );

		$this->temp_tables[] = $temp_table;

		// Use appropriate engine based on availability.
		$engine = $this->can_use_memory ? 'MEMORY' : 'InnoDB';

		// Create temporary table with index.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				'CREATE TEMPORARY TABLE %i (
					post_id BIGINT UNSIGNED PRIMARY KEY
				) ENGINE=%s',
				$temp_table,
				$engine
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Insert IDs in batches (prevent max_allowed_packet issues).
		foreach ( array_chunk( $post_ids, $this->batch_size ) as $batch ) {
			$values = implode(
				',',
				array_map(
					function ( $id ) {
						return "({$id})";
					},
					$batch
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "INSERT IGNORE INTO {$temp_table} VALUES {$values}" );
		}

		// Build the optimized query.
		$post_type      = $query->query_vars['post_type'] ?? 'post';
		$post_status    = $query->query_vars['post_status'] ?? 'publish';
		$posts_per_page = (int) ( $query->query_vars['posts_per_page'] ?? 10 );
		// Handle -1 (all posts) by setting to 0 (no limit).
		if ( $posts_per_page < 0 ) {
			$posts_per_page = 0;
		}
		$paged  = max( 1, absint( $query->get( 'paged' ) ) );
		$offset = ( $paged - 1 ) * $posts_per_page;

		// Handle additional query parameters.
		$join  = '';
		$where = '';

		// Add taxonomy queries if present.
		if ( ! empty( $query->tax_query->queries ) ) {
			$tax_clauses = $query->tax_query->get_sql( $wpdb->posts, 'ID' );
			$join       .= $tax_clauses['join'];
			$where      .= $tax_clauses['where'];
		}

		// Add meta queries if present.
		if ( ! empty( $query->meta_query->queries ) ) {
			$meta_clauses = $query->meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			$join        .= $meta_clauses['join'];
			$where       .= $meta_clauses['where'];
		}

		// Handle post_type array.
		if ( is_array( $post_type ) ) {
			// Filter to strings only and escape for SQL.
			$string_types    = array_filter( $post_type, 'is_string' );
			$escaped_types   = array_map( 'esc_sql', $string_types );
			$post_type_where = "IN ('" . implode( "','", $escaped_types ) . "')";
		} else {
			$post_type_where = "= '" . esc_sql( $post_type ) . "'";
		}

		// Handle post_status array.
		if ( is_array( $post_status ) ) {
			// Filter to strings only and escape for SQL.
			$string_statuses   = array_filter( $post_status, 'is_string' );
			$escaped_statuses  = array_map( 'esc_sql', $string_statuses );
			$post_status_where = "IN ('" . implode( "','", $escaped_statuses ) . "')";
		} else {
			$post_status_where = "= '" . esc_sql( $post_status ) . "'";
		}

		// Determine ordering.
		$orderby = $query->query_vars['orderby'] ?? 'date';
		$order   = strtoupper( $query->query_vars['order'] ?? 'DESC' );

		// Validate order direction.
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Build ORDER BY clause.
		$order_clause = $this->build_order_clause( $orderby, $order, $temp_table, $post_ids, $wpdb, $query );

		// Build LIMIT clause.
		$limit_clause = '';
		if ( $posts_per_page > 0 ) {
			$limit_clause = "LIMIT {$posts_per_page} OFFSET {$offset}";
		}

		// Execute optimized query.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "
			SELECT SQL_CALC_FOUND_ROWS {$wpdb->posts}.*
			FROM {$wpdb->posts}
			INNER JOIN {$temp_table} t ON {$wpdb->posts}.ID = t.post_id
			{$join}
			WHERE {$wpdb->posts}.post_type {$post_type_where}
			AND {$wpdb->posts}.post_status {$post_status_where}
			{$where}
			{$order_clause}
			{$limit_clause}
		";

		// Store the SQL in the query object for debugging/testing.
		$query->request = $sql;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$posts = $wpdb->get_results( $sql );

		// Set pagination data (critical for WP_Query compatibility).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query->found_posts = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		if ( $posts_per_page > 0 ) {
			$query->max_num_pages = (int) ceil( $query->found_posts / $posts_per_page );
		} else {
			$query->max_num_pages = 1;
		}

		return $posts;
	}

	/**
	 * Build ORDER BY clause for the query.
	 *
	 * Supports all WP_Query orderby values including:
	 * - Column aliases: date, modified, title, name, author, parent, type
	 * - Direct columns: post_date, post_modified, post_title, etc.
	 * - Special: none, rand, RAND(seed), post__in, post_parent__in, post_name__in
	 * - Meta-based: meta_value, meta_value_num, custom meta clause keys
	 *
	 * @since 3.1.0
	 * @param string|array $orderby    Order by field(s).
	 * @param string       $order      Order direction (ASC/DESC) for string orderby.
	 * @param string       $temp_table Temp table name.
	 * @param array        $post_ids   Original post IDs (for post__in ordering).
	 * @param \wpdb        $wpdb       WordPress database object.
	 * @param \WP_Query    $query      WP_Query instance.
	 * @return string ORDER BY clause.
	 */
	private function build_order_clause( $orderby, $order, $temp_table, $post_ids, $wpdb, $query ) {
		// Handle 'none' - no ordering.
		if ( $orderby === 'none' ) {
			return '';
		}

		// Map shorthand aliases to full column names.
		$alias_map = array(
			'date'     => 'post_date',
			'modified' => 'post_modified',
			'title'    => 'post_title',
			'name'     => 'post_name',
			'author'   => 'post_author',
			'parent'   => 'post_parent',
			'type'     => 'post_type',
		);

		// Direct column names that map to wp_posts table.
		$column_map = array(
			'post_date'     => "{$wpdb->posts}.post_date",
			'post_modified' => "{$wpdb->posts}.post_modified",
			'post_title'    => "{$wpdb->posts}.post_title",
			'post_name'     => "{$wpdb->posts}.post_name",
			'post_author'   => "{$wpdb->posts}.post_author",
			'post_parent'   => "{$wpdb->posts}.post_parent",
			'post_type'     => "{$wpdb->posts}.post_type",
			'ID'            => "{$wpdb->posts}.ID",
			'menu_order'    => "{$wpdb->posts}.menu_order",
			'comment_count' => "{$wpdb->posts}.comment_count",
		);

		// Normalize to array format: 'date' => array('date' => 'DESC').
		if ( ! is_array( $orderby ) ) {
			$orderby = array( $orderby => $order );
		}

		// Get meta clauses if available (meta_query starts as false, then becomes WP_Meta_Query).
		$meta_clauses       = array();
		$primary_meta_query = false;
		if ( is_object( $query->meta_query ) && method_exists( $query->meta_query, 'get_clauses' ) ) {
			$meta_clauses = $query->meta_query->get_clauses();
			if ( ! empty( $meta_clauses ) ) {
				$primary_meta_query = reset( $meta_clauses );
			}
		}

		// Build ORDER BY parts.
		$order_parts = array();
		foreach ( $orderby as $field => $direction ) {
			// Handle 'none' in array context.
			if ( $field === 'none' ) {
				continue;
			}

			// Handle FIELD-based ordering.
			if ( $field === 'post__in' && ! empty( $post_ids ) ) {
				$ids_list      = implode( ',', array_map( 'absint', $post_ids ) );
				$order_parts[] = "FIELD({$wpdb->posts}.ID, {$ids_list})";
				continue;
			}

			if ( $field === 'post_parent__in' && ! empty( $query->query_vars['post_parent__in'] ) ) {
				$parent_ids    = implode( ',', array_map( 'absint', $query->query_vars['post_parent__in'] ) );
				$order_parts[] = "FIELD({$wpdb->posts}.post_parent, {$parent_ids})";
				continue;
			}

			if ( $field === 'post_name__in' && ! empty( $query->query_vars['post_name__in'] ) ) {
				$post_names    = array_map( 'sanitize_title_for_query', $query->query_vars['post_name__in'] );
				$names_string  = "'" . implode( "','", $post_names ) . "'";
				$order_parts[] = "FIELD({$wpdb->posts}.post_name, {$names_string})";
				continue;
			}

			// Handle random ordering.
			if ( $field === 'rand' ) {
				$order_parts[] = 'RAND()';
				continue;
			}

			// Handle RAND(seed).
			if ( preg_match( '/^RAND\(([0-9]+)\)$/i', $field, $matches ) ) {
				$order_parts[] = sprintf( 'RAND(%d)', (int) $matches[1] );
				continue;
			}

			// Handle meta_value ordering.
			if ( $field === 'meta_value' && $primary_meta_query ) {
				if ( ! empty( $primary_meta_query['type'] ) ) {
					$order_parts[] = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']}) {$this->sanitize_order( $direction )}";
				} else {
					$order_parts[] = "{$primary_meta_query['alias']}.meta_value {$this->sanitize_order( $direction )}";
				}
				continue;
			}

			if ( $field === 'meta_value_num' && $primary_meta_query ) {
				$order_parts[] = "{$primary_meta_query['alias']}.meta_value+0 {$this->sanitize_order( $direction )}";
				continue;
			}

			// Handle custom meta clause keys.
			if ( array_key_exists( $field, $meta_clauses ) ) {
				$meta_clause   = $meta_clauses[ $field ];
				$order_parts[] = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']}) {$this->sanitize_order( $direction )}";
				continue;
			}

			// Resolve alias to full column name.
			$resolved_field = $alias_map[ $field ] ?? $field;

			// Get the SQL column.
			$column = $column_map[ $resolved_field ] ?? "{$wpdb->posts}.post_date";

			$order_parts[] = "{$column} {$this->sanitize_order( $direction )}";
		}

		if ( empty( $order_parts ) ) {
			return "ORDER BY {$wpdb->posts}.post_date DESC";
		}

		return 'ORDER BY ' . implode( ', ', $order_parts );
	}

	/**
	 * Sanitize order direction.
	 *
	 * @since 3.1.0
	 * @param string $order Order direction.
	 * @return string ASC or DESC.
	 */
	private function sanitize_order( $order ) {
		$order = strtoupper( $order );
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Fallback: Process in chunks.
	 *
	 * @since 3.1.0
	 * @param array|null $posts Posts array.
	 * @param \WP_Query  $query WP_Query instance.
	 * @return array Posts array.
	 */
	private function optimize_with_chunking( $posts, $query ) {
		$post_ids = array_map( 'intval', $query->query_vars['post__in'] );
		$chunks   = array_chunk( $post_ids, 500 );

		$all_posts               = array();
		$original_posts_per_page = $query->query_vars['posts_per_page'];

		// Store original orderby to check for post__in ordering.
		$orderby = $query->query_vars['orderby'] ?? 'date';

		foreach ( $chunks as $chunk ) {
			$query->query_vars['post__in']       = $chunk;
			$query->query_vars['posts_per_page'] = -1; // Get all in chunk.
			$chunk_posts                         = $query->get_posts();
			$all_posts                           = array_merge( $all_posts, $chunk_posts );
		}

		// Remove duplicates and maintain order.
		$unique_posts = array();
		$seen_ids     = array();
		foreach ( $all_posts as $post ) {
			if ( ! in_array( $post->ID, $seen_ids, true ) ) {
				$unique_posts[] = $post;
				$seen_ids[]     = $post->ID;
			}
		}

		// If orderby is post__in, reorder to match original post__in array.
		if ( $orderby === 'post__in' ) {
			$id_to_post = array();
			foreach ( $unique_posts as $post ) {
				$id_to_post[ $post->ID ] = $post;
			}

			$ordered_posts = array();
			foreach ( $post_ids as $id ) {
				if ( isset( $id_to_post[ $id ] ) ) {
					$ordered_posts[] = $id_to_post[ $id ];
				}
			}
			$unique_posts = $ordered_posts;
		}

		// Apply pagination.
		$paged           = max( 1, absint( $query->get( 'paged' ) ) );
		$offset          = ( $paged - 1 ) * $original_posts_per_page;
		$paginated_posts = array_slice( $unique_posts, $offset, $original_posts_per_page );

		// Set pagination data.
		$query->found_posts   = count( $unique_posts );
		$query->max_num_pages = (int) ceil( $query->found_posts / $original_posts_per_page );

		return $paginated_posts;
	}

	/**
	 * Clean up temporary tables on shutdown.
	 *
	 * @since 3.1.0
	 */
	public function cleanup_temp_tables() {
		global $wpdb;
		foreach ( $this->temp_tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS {$table}" );
		}
		$this->temp_tables = array();
	}

	/**
	 * Get optimization statistics.
	 *
	 * @since 3.1.0
	 * @return array Statistics array.
	 */
	public function get_stats() {
		return array(
			'can_use_temp_tables'   => $this->can_use_temp_tables(),
			'can_use_memory_engine' => $this->can_use_memory,
			'threshold'             => $this->threshold,
			'method'                => $this->get_optimization_method(),
		);
	}

	/**
	 * Get current optimization method.
	 *
	 * @since 3.1.0
	 * @return string Method name.
	 */
	private function get_optimization_method() {
		if ( $this->can_use_temp_tables() ) {
			return $this->can_use_memory ? 'MEMORY Temp Table' : 'InnoDB Temp Table';
		} else {
			return 'Chunking';
		}
	}
}
