<?php
/**
 * The indexer task runner.
 *
 * Handles task execution, progress reporting, and process control
 * for the indexer system.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Fields\Field;
use Search_Filter\Options;
use Search_Filter\Queries\Query as Search_Filter_Query;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Task_Runner as Base_Task_Runner;
use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;
use Search_Filter_Pro\Task_Runner\Task;
use Search_Filter_Pro\Task_Runner\Task_Signal;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Indexer\Parent_Map\Database\Query as Parent_Map_Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The indexer task runner class.
 *
 * Extends the base Task_Runner to provide indexer-specific task
 * execution, progress tracking, and process control.
 *
 * @since 3.0.0
 */
class Task_Runner extends Base_Task_Runner {

	/**
	 * The task type for the task runner.
	 *
	 * @var string
	 */
	protected static $type = 'indexer';

	/**
	 * The size of the page when fetching posts to index.
	 *
	 * @var int
	 */
	private static $query_page_size = 200;

	/**
	 * The size of the batches when batch processing is enabled.
	 *
	 * @var int
	 */
	private static $default_batch_size = 200;

	/**
	 * Whether to use batch processing.
	 *
	 * @var bool
	 */
	private static $use_batching = true;

	/**
	 * Seconds reserved for flush() at end of batch.
	 *
	 * @var int
	 */
	private static $flush_time_buffer = 5;

	/**
	 * Run a task.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	protected static function run_task( &$task ): int {

		$signal = Task_Signal::FINISHED;

		Util::error_log( 'Run task: ' . $task->get_action() . ' | ID: ' . $task->get_id() . ' | Object ID: ' . $task->get_object_id(), 'notice' );

		do_action( 'search-filter-pro/indexer/run_task/start', $task );

		switch ( $task->get_action() ) {
			case 'clear_index':
				Indexer::clear_index();
				$signal = Task_Signal::FINISHED;
				break;
			case 'rebuild':
				$signal = self::task_rebuild( $task );
				break;
			case 'rebuild_query':
				$signal = self::task_rebuild_query( $task );
				break;
			case 'remove_query':
				$signal = self::task_remove_query( $task );
				break;
			case 'rebuild_field':
				$signal = self::task_rebuild_field( $task );
				break;
			case 'remove_field':
				$signal = self::task_remove_field( $task );
				break;
			case 'rebuild_bucket':
				$signal = self::task_rebuild_bucket( $task );
				break;
			case 'migrate':
				$signal = self::task_migrate( $task );
				break;
			case 'sync_post':
				$signal = self::task_post_sync( $task );
				break;
			case 'sync_post_batch':
				$signal = self::task_post_batch_sync( $task );
				break;
			default:
				Util::error_log( 'Unknown task action: ' . $task->get_action(), 'error' );
				break;
		}

		do_action( 'search-filter-pro/indexer/run_task/finish', $task );

		return $signal;
	}

	/**
	 * Rebuild the query index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild_query( $task ): int {
		// Skip if preprocessing already complete (task is 'running', waiting for children to finish).
		$preprocessing_complete = $task->get_meta( 'preprocessing_complete', true );
		if ( $preprocessing_complete === 'yes' ) {
			return Task_Signal::FINISHED;
		}

		$query_id = $task->get_meta( 'query_id', true );
		if ( ! $query_id ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query ID not set for rebuild query task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Lookup the query and get the post types.
		$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for rebuild query task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Make sure there are post types set.
		$post_types = $query->get_attribute( 'postTypes' );
		if ( empty( $post_types ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Post types not found for rebuild query task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Check to see if we're just starting the rebuild.
		$page_number = $task->get_meta( 'page_number', true );
		if ( ! $page_number ) {
			// Clear any existing tasks and index, this should have been done
			// already, the first time wouldn't catch any tasks in progress.
			self::clear_all_query_data( $query, array( 'id__not_in' => array( $task->get_id() ) ) );
		}

		/*
		 * Add query ID to the task so we don't rebuild the indexes for
		 * all connected fields to an object.
		 */
		$additional_task_data = array(
			'meta' => array( 'query_id' => $query_id ),
		);
		self::generate_rebuild_index_tasks( $task, $post_types, $additional_task_data );

		// After preprocessing completes (status = 'running'), remove parent from
		// local queue so the loop can continue processing remaining tasks
		// (including the newly created child tasks after refresh).
		if ( $task->get_status() === 'running' ) {
			self::complete_next_task();
		}

		return Task_Signal::FINISHED;
	}

	/**
	 * Removes the index for a query (and clears up any related tasks).
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_remove_query( $task ): int {

		// Get the query ID.
		$query_id = $task->get_meta( 'query_id', true );
		if ( ! $query_id ) {
			Util::error_log( 'Query ID not found for remove query task.', 'error' );
			$task->set_status( 'error' );
			$task->save();
			return Task_Signal::FINISHED;
		}

		// Get the query.
		$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for remove query task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Clear any existing tasks and index, this should have been done
		// already, the first time wouldn't catch any tasks in progress.
		self::clear_all_query_data( $query, array( 'id__not_in' => array( $task->get_id() ) ) );

		$task->set_status( 'complete' );
		$task->save();

		return Task_Signal::FINISHED;
	}

	/**
	 * Rebuild the field index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild_field( $task ): int {
		// Skip if preprocessing already complete (task is 'running', waiting for children to finish).
		$preprocessing_complete = $task->get_meta( 'preprocessing_complete', true );
		if ( $preprocessing_complete === 'yes' ) {
			return Task_Signal::FINISHED;
		}

		$field_id = $task->get_meta( 'field_id', true );
		if ( ! $field_id ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field ID not set for rebuild field task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Lookup the field and get the post types.
		$field = Field::get_instance( absint( $field_id ) );
		if ( is_wp_error( $field ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field not found for rebuild field task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Lookup the field and get the post types.
		$query = Search_Filter_Query::find( array( 'id' => $field->get_query_id() ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for rebuild field task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Make sure there are post types set.
		$post_types = $query->get_attribute( 'postTypes' );
		if ( empty( $post_types ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Post types not found for rebuild field task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Check to see if we're just starting the rebuild.
		$page_number = $task->get_meta( 'page_number', true );
		if ( ! $page_number ) {
			// If so clear out the existing tasks and index.
			self::clear_all_field_data( $field, array( 'id__not_in' => array( $task->get_id() ) ) );
		}

		/*
		 * Add query ID to the task so we don't rebuild the indexes for
		 * all connected fields to an object.
		 */
		$additional_task_data = array(
			'meta' => array( 'field_id' => $field_id ),
		);
		self::generate_rebuild_index_tasks( $task, $post_types, $additional_task_data );

		// After preprocessing completes (status = 'running'), remove parent from
		// local queue so the loop can continue processing remaining tasks
		// (including the newly created child tasks after refresh).
		if ( $task->get_status() === 'running' ) {
			self::complete_next_task();
		}

		return Task_Signal::FINISHED;
	}

	/**
	 * Clear all the index data for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to clear the index for.
	 * @param    array $args     Optional arguments for clearing tasks.
	 */
	public static function clear_all_field_data( $field, $args = array() ) {
		// If so clear out the old data.
		Post_Sync::clear_field_index( $field );

		// Delete any existing tasks for this field.
		$clear_tasks_args = wp_parse_args(
			$args,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => array(
					array(
						'key'     => 'field_id',
						'value'   => $field->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		self::clear_tasks( $clear_tasks_args );

		// Increment generation to invalidate any opportunistic process that starts
		// during the window between clearing tasks and building new ones.
		// With multiple admin tabs polling, this window could be exploited.
		self::increment_generation();

		// Invalidate running process to prevent race conditions.
		// Any running process will detect the generation mismatch and exit.
		self::reset_process_locks();
	}

	/**
	 * Clear all the index data for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param    Search_Filter_Query $query    The query to clear the index for.
	 * @param    array               $args     Optional arguments for clearing tasks.
	 */
	public static function clear_all_query_data( $query, $args = array() ) {
		// Increment generation FIRST to invalidate any opportunistic process
		// that starts during the window between clearing tasks and building new ones.
		self::increment_generation();

		// Invalidate running process to prevent race conditions.
		// This ensures any running process will detect the change and exit
		// before we modify the task queue.
		self::reset_process_locks();

		// Loop through the queries fields, and delete tasks and index data.
		$query_fields = $query->get_fields(
			array(
				'status' => 'any',
			)
		);
		// Delete tasks for the query.
		$clear_tasks_args = wp_parse_args(
			$args,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => array(
					array(
						'key'     => 'query_id',
						'value'   => $query->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		self::clear_tasks( $clear_tasks_args );

		foreach ( $query_fields as $field ) {
			// Clear any existing tasks and index, this should have been done
			// already, the first time wouldn't catch any tasks in progress.
			if ( is_wp_error( $field ) ) {
				Util::error_log( 'Field error when clearing query fields.', 'error' );
				continue;
			}
			self::clear_all_field_data( $field );
		}
	}

	/**
	 * Removes the index for a field (and clears up any related tasks).
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_remove_field( $task ): int {

		// Get the query ID.
		$field_id = $task->get_meta( 'field_id', true );
		if ( ! $field_id ) {
			Util::error_log( 'Field ID not found for remove field task.', 'error' );
			$task->set_status( 'error' );
			$task->save();
			return Task_Signal::FINISHED;
		}

		// Get the field.
		$field = Field::get_instance( absint( $field_id ) );
		if ( is_wp_error( $field ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field not found for remove field task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Clear any existing tasks and index, this should have been done
		// already, the first time wouldn't catch any tasks in progress.
		self::clear_all_field_data( $field, array( 'id__not_in' => array( $task->get_id() ) ) );

		$task->set_status( 'complete' );
		$task->save();

		return Task_Signal::FINISHED;
	}

	/**
	 * Schedule bucket rebuild task.
	 *
	 * Callback for bucket rebuild action hooks. Adds task to queue
	 * for automated bucket rebuilding when overflow threshold exceeded.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID that needs rebuild.
	 */
	public static function schedule_bucket_rebuild( $field_id ) {
		// Add rebuild task to queue.
		$task_data = array(
			'action' => 'rebuild_bucket',
			'status' => 'pending',
			'meta'   => array(
				'field_id' => $field_id,
			),
		);

		self::add_task( $task_data );

		// Trigger async processing.
		Indexer::async_process_queue();
	}

	/**
	 * Rebuild bucket index for a field (automated via overflow threshold).
	 *
	 * Triggered when overflow exceeds threshold. Rebuilds buckets from
	 * existing buckets + overflow, then clears overflow.
	 *
	 * @since 3.0.9
	 *
	 * @param Task $task The task to process.
	 */
	private static function task_rebuild_bucket( $task ): int {
		$field_id = $task->get_meta( 'field_id', true );

		if ( ! $field_id ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field ID not set for rebuild bucket task.', 'error' );
			return Task_Signal::FINISHED;
		}

		// Rebuild buckets (merges overflow with existing).
		$result = Bucket\Updater::build_field_buckets(
			$field_id,
			array( 'force_rebuild' => true )
		);

		if ( isset( $result['error'] ) ) {
			// This could be an error, but we get this even when there is a small amount of data.
			$task->set_status( 'complete' );
			$task->save();
			Util::error_log( "Bucket rebuild failed for field {$field_id}: {$result['error']}", 'notice' );
			return Task_Signal::FINISHED;
		}

		$task->set_status( 'complete' );
		$task->save();

		// Invalidate stats cache after bucket rebuild.
		Stats::flag_refresh();

		Util::error_log( "Bucket rebuild complete for field {$field_id}: {$result['buckets_created']} buckets updated", 'notice' );

		return Task_Signal::FINISHED;
	}

	/**
	 * Migrate from legacy index to new index system.
	 *
	 * Performs a full rebuild using the new index tables while maintaining
	 * the legacy index for ongoing operations during migration.
	 *
	 * @since 3.2.0
	 *
	 * @param Task $task The task to process.
	 */
	private static function task_migrate( &$task ): int {
		// Early exit if already complete.
		if ( Indexer::migration_completed() ) {
			$task->set_status( 'complete' );
			$task->save();
			Util::error_log( 'Migration already completed, skipping', 'notice' );
			return Task_Signal::FINISHED;
		}

		// Skip if preprocessing already complete (task is 'running', waiting for children to finish).
		$preprocessing_complete = $task->get_meta( 'preprocessing_complete', true );
		if ( $preprocessing_complete === 'yes' ) {
			return Task_Signal::FINISHED;
		}

		Indexer::init_sync_data();

		$page_number = $task->get_meta( 'page_number', true );

		// First run only.
		if ( ! $page_number ) {
			Util::error_log( 'Starting index migration to new system', 'notice' );
		}

		// Generate sync_post tasks with migration flag.
		$additional_task_data = array(
			'meta' => array( 'is_migration' => 'yes' ),
		);

		self::generate_rebuild_index_tasks(
			$task,
			Indexer::get_indexed_post_types(),
			$additional_task_data
		);

		// After preprocessing completes (status = 'running'), remove parent from
		// local queue so the loop can continue processing remaining tasks
		// (including the newly created child tasks after refresh).
		if ( $task->get_status() === 'running' ) {
			self::complete_next_task();
		}

		// If migration task completed, finalize.
		if ( $task->get_status() === 'complete' ) {
			self::finalize_migration();
		}

		return Task_Signal::FINISHED;
	}

	/**
	 * Finalize migration after all posts indexed.
	 *
	 * Marks migration as complete and truncates the legacy index table.
	 *
	 * @since 3.2.0
	 */
	private static function finalize_migration() {
		// Mark migration complete.
		Options::update( 'indexer-migration-completed', 'yes' );

		// Truncate legacy table (no longer needed).
		$table = Legacy\Manager::get_table( false );
		if ( $table && $table->exists() ) {
			$table->uninstall();
		}

		// Invalidate stats cache to recalculate with new index.
		Stats::flag_refresh();

		Util::error_log( 'Index migration completed successfully', 'notice' );
	}

	/**
	 * Rebuild the index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild( &$task ): int {
		// Skip if preprocessing already complete (task is 'running', waiting for children to finish).
		$preprocessing_complete = $task->get_meta( 'preprocessing_complete', true );
		if ( $preprocessing_complete === 'yes' ) {
			return Task_Signal::FINISHED;
		}

		Indexer::init_sync_data();
		$page_number = $task->get_meta( 'page_number', true );

		if ( ! $page_number ) {
			// Clear index BEFORE updating migration option. This ensures legacy
			// table gets cleared while migration_completed() still returns false.
			Indexer::clear_index();

			// If migration in progress, mark it complete now.
			// User rebuild supersedes migration.
			if ( Options::get( 'indexer-migration-completed' ) === 'no' ) {
				Options::update( 'indexer-migration-completed', 'yes' );
				Util::error_log( 'User rebuild detected, completing migration', 'notice' );
			}

			// Clear any other indexer related tasks (including migration tasks).
			self::clear_tasks(
				array(
					'id__not_in' => array( $task->get_id() ),
				)
			);
		}
		self::generate_rebuild_index_tasks( $task, Indexer::get_indexed_post_types() );

		// After preprocessing completes (status = 'running'), remove parent from
		// local queue so the loop can continue processing remaining tasks
		// (including the newly created child tasks after refresh).
		if ( $task->get_status() === 'running' ) {
			self::complete_next_task();
		}

		return Task_Signal::FINISHED;
	}

	/**
	 * Query posts eligible for indexing.
	 *
	 * Retrieves posts matching the given post types that are eligible for indexing.
	 * Excludes posts with status: auto-draft, trash, inherit (matching WP_Query 'any' behavior).
	 *
	 * @since 3.2.0
	 *
	 * @param array $post_types Array of post type slugs to query.
	 * @param int   $page       Page number (1-indexed) for pagination. Default 1.
	 * @param int   $page_size  Optional. Number of posts per page. Default uses $query_page_size (200).
	 * @return array {
	 *     Query results with count and posts.
	 *
	 *     @type int   $found_posts  Total count of matching posts.
	 *     @type int   $max_pages    Maximum number of pages.
	 *     @type array $posts        Array of post data with ID, post_parent, post_type.
	 * }
	 */
	protected static function get_posts_for_indexing( $post_types, $page = 1, $page_size = null ) {
		global $wpdb;

		// Handle edge cases.
		if ( empty( $post_types ) ) {
			return array(
				'found_posts' => 0,
				'max_pages'   => 0,
				'posts'       => array(),
			);
		}

		// Use default page size if not specified.
		if ( $page_size === null ) {
			$page_size = self::$query_page_size;
		}
		$page_size = absint( $page_size );

		// Ensure valid page number.
		$page = max( 1, absint( $page ) );

		// Build post type placeholders for SQL queries.
		// This emulates WP_Query with 'fields' => 'id=>parent' but adds post_type column.
		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		// Match WP_Query 'post_status' => 'any' behavior (excludes auto-draft, trash, inherit).
		$status_exclusions = "post_status NOT IN ('auto-draft', 'trash', 'inherit')";

		// COUNT query - get total matching posts.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found_posts = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				 WHERE post_type IN ({$type_placeholders})
				   AND {$status_exclusions}",
				array_merge( array( $wpdb->posts ), $post_types )
			)
		);
		// phpcs:enable

		$max_pages = $found_posts > 0 ? (int) ceil( $found_posts / $page_size ) : 0;

		// SELECT query - paginated post retrieval.
		$offset = ( $page - 1 ) * $page_size;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_parent, post_type FROM %i
				 WHERE post_type IN ({$type_placeholders})
				   AND {$status_exclusions}
				 ORDER BY ID ASC
				 LIMIT %d OFFSET %d",
				array_merge( array( $wpdb->posts ), $post_types, array( $page_size, $offset ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'found_posts' => $found_posts,
			'max_pages'   => $max_pages,
			'posts'       => $posts ? $posts : array(),
		);
	}

	/**
	 * Count the effective number of indexed fields for a set of post types.
	 *
	 * Used to scale batch page size and prevent timeouts when many fields
	 * are indexed (e.g., 500+ fields).
	 *
	 * @since 3.3.3
	 *
	 * @param array $post_types           Post types being rebuilt.
	 * @param array $additional_task_data Additional task data with optional meta.
	 * @return int Number of effective fields.
	 */
	private static function get_effective_field_count( $post_types, $additional_task_data ) {
		// Field-specific rebuild = 1 field.
		if ( ! empty( $additional_task_data['meta']['field_id'] ) ) {
			return 1;
		}

		// Query-specific rebuild = that query's fields.
		if ( ! empty( $additional_task_data['meta']['query_id'] ) ) {
			$query = Search_Filter_Query::find( array( 'id' => $additional_task_data['meta']['query_id'] ) );
			if ( ! is_wp_error( $query ) ) {
				return count( $query->get_fields() );
			}
			return 0;
		}

		// Full rebuild/migrate: count unique fields across relevant post types.
		$indexed_fields   = Indexer::get_indexed_fields_by_post_type();
		$unique_field_ids = array();
		foreach ( $post_types as $post_type ) {
			if ( isset( $indexed_fields[ $post_type ] ) ) {
				foreach ( $indexed_fields[ $post_type ] as $field ) {
					$unique_field_ids[ $field->get_id() ] = true;
				}
			}
		}
		return count( $unique_field_ids );
	}

	/**
	 * Rebuild the index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task  $task                    The task to process.
	 * @param    array $post_types              The post types to rebuild.
	 * @param    array $additional_task_data    Additional data to pass to the task.
	 */
	private static function generate_rebuild_index_tasks( &$task, $post_types, $additional_task_data = array() ) {
		$page_number = $task->get_meta( 'page_number', true );
		$is_starting = false;
		if ( ! $page_number ) {
			$page_number = 1;
			$is_starting = true;
		}
		$page_number = absint( $page_number );

		// Scale page size based on field count to prevent timeouts with many fields.
		$page_size   = self::$query_page_size;
		$field_count = self::get_effective_field_count( $post_types, $additional_task_data );
		if ( $field_count > 1000 ) {
			$page_size = 50;
		} elseif ( $field_count >= 250 ) {
			$page_size = 100;
		}

		// Rate-based adjustment (if we have observed data from previous batches).
		// When rate data exists, allow up to $query_page_size (200) regardless of field count.
		// Field-count-based $page_size only applies as initial default when no rate data exists.
		$observed_rate      = Options::get( 'indexer-processing-rate' );
		$original_page_size = $page_size;
		if ( $observed_rate !== null && floatval( $observed_rate ) > 0 ) {
			$time_budget     = self::get_time_limit() - self::$flush_time_buffer;
			$rate_based_size = (int) floor( floatval( $observed_rate ) * $time_budget );

			// Clamp: min 10, max = absolute ceiling (ignore field-count limit with real data).
			$rate_based_size = max( 10, min( $rate_based_size, self::$query_page_size ) );
			$page_size       = $rate_based_size;

			Util::error_log(
				"Batch size adjusted: {$page_size} posts (rate: {$observed_rate}/s, budget: {$time_budget}s, field-count default: {$original_page_size})",
				'notice'
			);
		}

		// Query posts for this page using extracted method.
		// Gets live count every page (handles posts changing during indexing).
		$query_result  = self::get_posts_for_indexing( $post_types, $page_number, $page_size );
		$found_posts   = $query_result['found_posts'];
		$max_num_pages = $query_result['max_pages'];
		$results       = $query_result['posts'];

		// On first page, initialize progress tracking display data.
		if ( $is_starting ) {
			Stats::flag_refresh();

			// Store display info in task meta for UI progress display.
			$task->update_meta( 'total_pages', $max_num_pages );
			$task->update_meta( 'total_posts', $found_posts );
			$task->update_meta( 'posts_per_page', $page_size );
		}

		// Update current page for preprocessing progress.
		$task->update_meta( 'current_page', $page_number );

		// Get parent task ID for child task reference.
		$parent_task_id = $task->get_id();

		// Collect variation→parent mappings for batch insert.
		$parent_mappings = array();

		/**
		 * Filter to enable batch indexing mode.
		 *
		 * When enabled, creates a single sync_post_batch task per page instead of
		 * individual sync_post tasks. This can significantly reduce database operations
		 * during large index rebuilds (80-90% reduction).
		 *
		 * @since 3.1.0
		 *
		 * @param bool $use_batch_indexing Whether to use batch indexing. Default false.
		 */
		$use_batch_indexing = apply_filters( 'search-filter-pro/indexer/use_batching', self::$use_batching );

		// Build tasks array for batch insert.
		$tasks_batch = array();
		$post_ids    = array();

		foreach ( $results as $row ) {
			$post_id     = (int) $row['ID'];
			$post_parent = (int) $row['post_parent'];
			$post_type   = $row['post_type'];

			$post_ids[] = $post_id;

			// Build parent mapping with source derived from post type.
			if ( $post_parent > 0 ) {
				$parent_mappings[] = array(
					'child_id'  => $post_id,
					'parent_id' => $post_parent,
					'source'    => 'post-' . $post_type,
				);
			}
		}

		// Build common metadata that applies to all tasks.
		$shared_meta = array(
			'parent_task' => $task->get_action(),
		);

		// Merge with additional task metadata.
		if ( ! empty( $additional_task_data['meta'] ) ) {
			$shared_meta = array_merge( $additional_task_data['meta'], $shared_meta );
		}

		if ( $use_batch_indexing && ! empty( $post_ids ) ) {
			// Batch mode: Create a single sync_post_batch task for all posts in this page.
			$tasks_batch[] = array(
				'action'    => 'sync_post_batch',
				'status'    => 'pending',
				'parent_id' => $parent_task_id,
			);
			// Add post_ids to metadata for batch processing.
			$shared_meta['post_ids'] = $post_ids;
		} else {
			// Individual mode: Create individual sync_post tasks (existing behavior).
			foreach ( $post_ids as $post_id ) {
				$tasks_batch[] = array(
					'action'    => 'sync_post',
					'status'    => 'pending',
					'object_id' => $post_id,
					'parent_id' => $parent_task_id,
				);
			}
		}

		// Batch insert all tasks with common metadata.
		if ( ! empty( $tasks_batch ) ) {
			self::batch_add_tasks( $tasks_batch, $shared_meta );
		}

		// Batch insert parent mappings for this page (200 posts at a time).
		if ( ! empty( $parent_mappings ) ) {
			Parent_Map_Query::store_mappings_batch( $parent_mappings );
		}

		// If we've reached the max number of pages, then we're done generating children.
		if ( $page_number >= $max_num_pages ) {
			// Mark preprocessing as complete.
			$task->update_meta( 'preprocessing_complete', 'yes' );
			$task->delete_meta( 'page_number' );
			// Keep current_page and total_pages for reference.

			// Check if any posts were found.
			$total_posts = (int) $task->get_meta( 'total_posts', true );
			if ( $total_posts === 0 ) {
				// No children created - complete immediately.
				$task->set_status( 'complete' );
			} else {
				// Children exist - wait for them to finish.
				$task->set_status( 'running' );
			}

			$task->save();

			// Ensure async processing is triggered for newly created child tasks.
			// This handles edge cases where the current process may exit before
			// processing children (time/memory limits), ensuring a new process
			// is spawned to continue.
			if ( $task->get_status() === 'running' ) {
				Indexer::async_process_queue();
			}
			return;
		}

		// Else, update the page number and run again.
		$task->update_meta(
			'page_number',
			$page_number + 1
		);
		$task->save();
	}

	/**
	 * Sync a post.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_post_sync( $task ): int {
		// Consolidation check for standalone tasks only (parent_id = 0).
		// If batching is enabled and threshold met, consolidate into batch task.
		if ( $task->get_parent_id() === 0 ) {
			$batch_task = self::maybe_consolidate_standalone_sync_tasks( $task );
			if ( $batch_task ) {
				// Current task was included in batch and deleted.
				// Mark as complete so task runner moves on.
				$task->set_status( 'complete' );
				return Task_Signal::FINISHED;
			}
		}

		$post_id = $task->get_object_id();
		$fields  = array();

		$field_id     = $task->get_meta( 'field_id', true );
		$query_id     = $task->get_meta( 'query_id', true );
		$is_migration = ( $task->get_meta( 'is_migration', true ) === 'yes' );

		// Check field ID before query ID, because field resync attaches
		// the query_id for easier management and deletion.
		if ( $field_id ) {
			$field = Field::get_instance( absint( $field_id ) );
			if ( ! is_wp_error( $field ) ) {
				$fields[] = $field;
			}
		} elseif ( $query_id ) {
			// If no specific field IDs found, then see if we have a query.
			$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
			if ( ! is_wp_error( $query ) ) {
				$query_fields = $query->get_fields();
				foreach ( $query_fields as $query_field ) {
					$fields[] = $query_field;
				}
			}
		} else {
			Indexer::init_sync_data();
			$indexed_fields_by_post_type = Indexer::get_indexed_fields_by_post_type();
			$post                        = get_post( $post_id );
			if ( $post && isset( $indexed_fields_by_post_type[ $post->post_type ] ) ) {
				$fields = $indexed_fields_by_post_type[ $post->post_type ];
			}
		}

		$args = array(
			'fields'       => $fields,
			'is_migration' => $is_migration,
		);

		Post_Sync::resync_post( $post_id, $args, false );

		$task->set_status( 'complete' );
		$task->save();

		// Check if parent task should be completed.
		$parent_task_id = $task->get_parent_id();
		if ( $parent_task_id && $parent_task_id > 0 ) {
			// Check for other pending siblings (exclude self).
			if ( ! self::has_sibling_tasks( $parent_task_id, $task->get_id() ) ) {
				// Last child - complete the parent.
				$query      = new Tasks_Query();
				$parent_row = $query->get_item( $parent_task_id );

				if ( $parent_row ) {
					$parent = new Task();
					$parent->load_record( $parent_row );

					if ( $parent->get_status() === 'running' ) {
						$parent->set_status( 'complete' );
						$parent->save();
						Util::error_log( 'Parent task completed: ' . $parent->get_action() . ' | ID: ' . $parent_task_id, 'notice' );

						// Finalize migration if this was a migrate task.
						if ( $parent->get_action() === 'migrate' ) {
							self::finalize_migration();
						}
					}
				}
			}
		}

		return Task_Signal::FINISHED;
	}

	/**
	 * Process a batch of posts for syncing.
	 *
	 * Uses Batch_Writer for efficient batch writes to indexes.
	 *
	 * @since 3.2.0
	 *
	 * @param Task $task The task to process.
	 */
	private static function task_post_batch_sync( $task ): int {
		$post_ids         = $task->get_meta( 'post_ids', true );
		$processed_fields = $task->get_meta( 'processed_fields', true );
		$processed_fields = $processed_fields ? $processed_fields : array();
		$retry_count      = absint( $task->get_meta( 'retry_count', true ) );

		if ( empty( $post_ids ) ) {
			$task->set_status( 'complete' );
			$task->save();
			return Task_Signal::FINISHED;
		}

		wp_suspend_cache_addition( false );

		// Initialize sync data to get field information.
		Indexer::init_sync_data();

		// Get optional query_id or field_id from task meta.
		$field_id = $task->get_meta( 'field_id', true );
		$query_id = $task->get_meta( 'query_id', true );

		// Create batch writer.
		$batch_writer = new Batch_Writer();

		// Get indexed fields by post type.
		$indexed_fields_by_post_type = Indexer::get_indexed_fields_by_post_type();

		$post_counter     = 0;
		$total_posts      = count( $post_ids );
		$batch_start_time = time();
		$process_elapsed  = time() - static::$process_start_time;
		$time_budget      = max( 0, self::get_time_limit() - $process_elapsed - self::$flush_time_buffer );
		$early_exit       = false;

		Util::error_log(
			"Batch start: {$total_posts} posts, time_budget: {$time_budget}s "
			. '(limit: ' . self::get_time_limit() . "s, process_elapsed: {$process_elapsed}s, buffer: " . self::$flush_time_buffer . 's)',
			'notice'
		);

		// Bail if no time budget remains — process already consumed most of PHP lifetime.
		if ( $time_budget <= 0 ) {
			Util::error_log( 'Batch: no time budget remaining — deferring.', 'notice' );
			wp_suspend_cache_addition( true );
			return Task_Signal::TIME_LIMITED;
		}

		// Phase 1: Collect values for all posts (time-aware).
		// Process in chunks of 10 — each chunk primes caches upfront and frees memory after.
		$chunks            = array_chunk( $post_ids, 10 );
		$elapsed           = 0;
		$last_lock_refresh = time();

		foreach ( $chunks as $chunk ) {
			// Prime WP object caches for this chunk (posts, meta, terms).
			// Reduces DB round-trips from 3×N to 3 per chunk.
			_prime_post_caches( $chunk, true, true );

			foreach ( $chunk as $post_id ) {
				++$post_counter;
				$post_start_time = microtime( true );

				$post = get_post( $post_id );
				if ( ! $post ) {
					continue;
				}

				// Determine which fields to sync.
				$fields_to_sync = array();

				if ( $field_id ) {
					$field = Field::get_instance( absint( $field_id ) );
					if ( ! is_wp_error( $field ) ) {
						$fields_to_sync[] = $field;
					}
				} elseif ( $query_id ) {
					$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
					if ( ! is_wp_error( $query ) ) {
						$fields_to_sync = $query->get_fields();
					}
				} else {
					// Use all indexed fields for this post type.
					$fields_to_sync = $indexed_fields_by_post_type[ $post->post_type ] ?? array();
				}

				$post_parent_id = absint( wp_get_post_parent_id( $post_id ) );

				// Collect values for each field.
				foreach ( $fields_to_sync as $field ) {
					// Skip already processed fields (for resume support).
					if ( in_array( $field->get_id(), $processed_fields, true ) ) {
						continue;
					}

					// Get field values.
					$values = Post_Sync::get_field_values_for_batch( $field, $post_id );

					if ( ! empty( $values ) ) {
						$batch_writer->add_post_values(
							$field->get_id(),
							$post_id,
							$post_parent_id,
							$values
						);
					}
				}

				// Allow integrations to hook into this process after each post is processed
				// to clear any caching or run custom code.
				do_action( 'search-filter-pro/indexer/task_batch_post_sync/process_post', $post_id );

				$post_duration = round( microtime( true ) - $post_start_time, 3 );
				$elapsed       = time() - $batch_start_time;
				Util::error_log( "Post {$post_id}: {$post_duration}s ({$post_counter}/{$total_posts}, elapsed: {$elapsed}s)", 'notice' );

				// Time budget check — always process at least 1 post before checking.
				if ( $elapsed >= $time_budget ) {
					$early_exit = true;
					break 2;
				}

				// Refresh process lock every 5s to prevent expiry on slow hosts.
				if ( ( time() - $last_lock_refresh ) >= 5 ) {
					self::refresh_process_lock_time();
					$last_lock_refresh = time();
					Util::error_log( "Lock refreshed at post {$post_counter}/{$total_posts} (elapsed: {$elapsed}s)", 'notice' );
				}
			}

			// Free memory between chunks to prevent exhaustion.
			// Skip after the last chunk — post-loop code handles final cleanup.
			if ( $post_counter < $total_posts ) {
				self::free_memory();
				Util::error_log( "Memory freed at post {$post_counter}/{$total_posts} (elapsed: {$elapsed}s)", 'notice' );
			}
		}

		// Track post-loop overhead timing.
		$postloop_start = microtime( true );

		// Store observed processing rate (dampened to prevent oscillation).
		$elapsed = time() - $batch_start_time;
		if ( $elapsed > 0 && $post_counter > 0 ) {
			$observed_rate = round( $post_counter / $elapsed, 2 );
			$previous_rate = Options::get( 'indexer-processing-rate' );

			if ( $previous_rate !== null && floatval( $previous_rate ) > 0 ) {
				// Dampen: blend 50% previous + 50% observed to prevent oscillation.
				$new_rate = round( ( floatval( $previous_rate ) + $observed_rate ) / 2, 2 );
			} else {
				// First run — use observed rate directly.
				$new_rate = $observed_rate;
			}

			Options::update( 'indexer-processing-rate', $new_rate );
			Util::error_log(
				'Indexer rate updated: ' . $new_rate . ' posts/sec (observed: ' . $observed_rate . ', was: ' . ( $previous_rate ?? 'none' ) . ')',
				'notice'
			);
		}

		// Handle early exit — defer remaining posts to next cycle.
		if ( $early_exit ) {
			$remaining_post_ids = array_slice( $post_ids, $post_counter );
			$remaining_count    = count( $remaining_post_ids );

			Util::error_log(
				"Batch early exit: processed {$post_counter}/{$total_posts} posts in {$elapsed}s (budget: {$time_budget}s). {$remaining_count} posts deferred.",
				'notice'
			);

			// Skip flush if process was invalidated (lock expired or generation changed).
			// The new process will re-process this task since it's still pending.
			if ( ! self::is_current_process() ) {
				Util::error_log( 'Batch: process invalidated during collection — skipping flush.', 'notice' );
				wp_suspend_cache_addition( true );
				return Task_Signal::FINISHED;
			}

			// Flush collected data if any.
			if ( $batch_writer->has_data() ) {
				self::refresh_process_lock_time();
				Util::clear_object_caches();
				$flush_start = microtime( true );
				$result      = $batch_writer->flush();
				$flush_dur   = round( microtime( true ) - $flush_start, 3 );

				if ( ! $result->is_fully_successful() ) {
					$task->update_meta( 'processed_fields', $batch_writer->get_processed_fields() );
					$task->update_meta( 'last_error', $result->get_error_summary() );
					Util::error_log( 'Batch partial flush had errors: ' . $result->get_error_summary(), 'warning' );
				}
			}

			// Update task with remaining post IDs and keep pending.
			$task->update_meta( 'post_ids', $remaining_post_ids );
			$task->set_status( 'pending' );
			$task->save();

			$postloop_dur = round( microtime( true ) - $postloop_start, 3 );
			$flush_dur    = $flush_dur ?? 0;
			Util::error_log( "Early exit post-loop: {$postloop_dur}s total (flush: {$flush_dur}s)", 'notice' );
			wp_suspend_cache_addition( true );
			return Task_Signal::FINISHED;
		}

		// Final cache cleanup after loop.
		Util::clear_object_caches();

		// Log full batch completion.
		if ( $elapsed > 0 ) {
			$rate = round( $post_counter / $elapsed, 2 );
			Util::error_log( "Batch complete: {$post_counter} posts in {$elapsed}s (rate: {$rate}/s)", 'notice' );
		}

		// Skip flush if process was invalidated (lock expired or generation changed).
		// The new process will re-process this task since it's still pending.
		if ( ! self::is_current_process() ) {
			Util::error_log( 'Batch: process invalidated during collection — skipping flush.', 'notice' );
			wp_suspend_cache_addition( true );
			return Task_Signal::FINISHED;
		}

		// Phase 2: Batch write and handle results.
		self::refresh_process_lock_time();
		$flush_start = microtime( true );
		$result      = $batch_writer->flush();
		$flush_dur   = round( microtime( true ) - $flush_start, 3 );

		if ( $result->is_fully_successful() ) {
			$task->set_status( 'complete' );
			$task->delete_meta( 'retry_count' );
			$task->delete_meta( 'processed_fields' );
		} else {
			// Handle failures.
			$task->update_meta( 'processed_fields', $batch_writer->get_processed_fields() );

			if ( $retry_count < 3 ) {
				$task->set_status( 'pending' ); // Re-queue for retry.
				$task->update_meta( 'retry_count', $retry_count + 1 );
				$task->update_meta( 'last_error', $result->get_error_summary() );

				Util::error_log(
					'Batch sync failed (attempt ' . ( $retry_count + 1 ) . '/3): ' . $result->get_error_summary(),
					'warning'
				);
			} else {
				$task->set_status( 'error' );
				$task->update_meta( 'last_error', $result->get_error_summary() );

				// Queue failed posts as individual tasks for manual review.
				$failed_post_ids = $result->get_failed_post_ids();
				if ( ! empty( $failed_post_ids ) ) {
					self::queue_failed_posts_for_retry( $failed_post_ids, $task );
				}

				Util::error_log(
					'Batch sync permanently failed after 3 attempts. Failed posts: ' . count( $failed_post_ids ),
					'error'
				);
			}
		}

		$task->save();

		$postloop_dur = round( microtime( true ) - $postloop_start, 3 );
		Util::error_log( "Batch complete post-loop: {$postloop_dur}s total (flush: {$flush_dur}s)", 'notice' );

		// Check if parent task should be completed.
		$parent_task_id = $task->get_parent_id();
		if ( $parent_task_id && $parent_task_id > 0 ) {
			$has_siblings = self::has_sibling_tasks( $parent_task_id, $task->get_id() );

			if ( ! $has_siblings ) {
				$query      = new Tasks_Query();
				$parent_row = $query->get_item( $parent_task_id );

				if ( $parent_row ) {
					$parent = new Task();
					$parent->load_record( $parent_row );

					if ( $parent->get_status() === 'running' ) {
						$parent->set_status( 'complete' );
						$parent->save();
						Util::error_log( 'Parent task completed: ' . $parent->get_action() . ' | ID: ' . $parent_task_id, 'notice' );

						// Finalize migration if this was a migrate task.
						if ( $parent->get_action() === 'migrate' ) {
							self::finalize_migration();
						}
					}
				}
			}
		}
		wp_suspend_cache_addition( true );
		return Task_Signal::FINISHED;
	}


	/**
	 * Free up memory by clearing caches and query logs.
	 *
	 * @since 3.0.0
	 */
	public static function free_memory() {

		Util::clear_object_caches();

		// Clear wpdb query log if SAVEQUERIES is enabled (prevents memory accumulation).
		global $wpdb;
		$wpdb->queries = array();
	}

	/**
	 * Queue failed posts for individual retry.
	 *
	 * Creates individual sync_post tasks for posts that failed batch processing.
	 *
	 * @since 3.2.0
	 *
	 * @param array $post_ids    Array of failed post IDs.
	 * @param Task  $parent_task The parent batch task.
	 */
	private static function queue_failed_posts_for_retry( $post_ids, $parent_task ) {
		if ( empty( $post_ids ) ) {
			return;
		}

		$tasks_batch = array();
		foreach ( $post_ids as $post_id ) {
			$tasks_batch[] = array(
				'action'    => 'sync_post',
				'status'    => 'pending',
				'object_id' => $post_id,
			);
		}

		// Get metadata from parent task.
		$shared_meta = array();
		$field_id    = $parent_task->get_meta( 'field_id', true );
		$query_id    = $parent_task->get_meta( 'query_id', true );

		if ( $field_id ) {
			$shared_meta['field_id'] = $field_id;
		}
		if ( $query_id ) {
			$shared_meta['query_id'] = $query_id;
		}

		self::batch_add_tasks( $tasks_batch, $shared_meta );

		Util::error_log( 'Queued ' . count( $post_ids ) . ' failed posts for individual retry.', 'notice' );
	}

	// =========================================================================
	// Progress and Status Methods
	// =========================================================================

	/**
	 * Check if parent task has any remaining children.
	 *
	 * Optimized query with LIMIT 1 using indexed parent_id column.
	 * Supports both individual sync_post and batch sync_post_batch tasks.
	 *
	 * @since 3.2.0
	 *
	 * @param int $parent_task_id Parent task ID.
	 * @param int $exclude_task_id Optional current child task ID to exclude.
	 * @return bool True if siblings still pending.
	 */
	private static function has_sibling_tasks( $parent_task_id, $exclude_task_id = null ) {
		// Check both individual and batch task types.
		$task_actions = self::get_sync_task_actions();

		$query_args = array(
			'type'       => 'indexer',
			'action__in' => $task_actions,
			'status'     => 'pending',
			'parent_id'  => $parent_task_id,
			'number'     => 1, // Fast exit on first match.
		);

		// Exclude current task for safety.
		if ( $exclude_task_id ) {
			$query_args['id__not_in'] = array( $exclude_task_id );
		}

		$query = new Tasks_Query( $query_args );

		return ! empty( $query->items );
	}

	/**
	 * Get the sync task action(s) to use based on batch indexing setting.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of task action names to query.
	 */
	private static function get_sync_task_actions() {
		/**
		 * Filter to enable batch indexing mode.
		 *
		 * @since 3.1.0
		 * @param bool $use_batch_indexing Whether to use batch indexing. Default false.
		 */
		$use_batch_indexing = apply_filters( 'search-filter-pro/indexer/use_batching', self::$use_batching );

		// Return both action types to support mixed scenarios (e.g., during transition).
		// If batch mode is enabled, batch tasks are primary; if disabled, individual tasks.
		if ( $use_batch_indexing ) {
			return array( 'sync_post_batch', 'sync_post' );
		}

		return array( 'sync_post' );
	}

	/**
	 * Get progress for a specific task type.
	 *
	 * In individual mode: counts sync_post tasks (1 task = 1 post).
	 * In batch mode: uses parent task metadata to show actual post counts.
	 *
	 * @since 3.2.0
	 *
	 * @param int        $parent_task_id The parent task ID.
	 * @param array|null $context        Optional context ID (query_id or field_id).
	 * @param Task       $parent_task    Parent task instance for metadata access.
	 * @return array Progress data with current, total, and percent.
	 */
	private static function get_parent_task_progress( $parent_task_id, $context = null, $parent_task = null ) {
		$task_actions       = self::get_sync_task_actions();
		$use_batch_indexing = apply_filters( 'search-filter-pro/indexer/use_batching', self::$use_batching );

		// Build query for child tasks.
		$query_args = array(
			'type'       => 'indexer',
			'action__in' => $task_actions,
			'parent_id'  => $parent_task_id,
			'number'     => 0,
		);

		if ( $context ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for context-specific task queries.
			$query_args['meta_query'] = array(
				array(
					'key'     => $context['key'],
					'value'   => $context['id'],
					'compare' => '=',
				),
			);
		}

		// Count completed tasks.
		$query_args['status'] = 'complete';
		$completed_query      = new Tasks_Query( $query_args );
		$completed_tasks      = $completed_query->found_items;

		// Batch mode: calculate post counts from metadata.
		if ( $use_batch_indexing && $parent_task ) {
			$total_posts    = absint( $parent_task->get_meta( 'total_posts', true ) );
			$posts_per_page = absint( $parent_task->get_meta( 'posts_per_page', true ) );

			$completed_posts = min( $completed_tasks * $posts_per_page, $total_posts );
			$percent         = $total_posts > 0 ? round( ( $completed_posts / $total_posts ) * 100, 1 ) : 0;

			return array(
				'current' => $completed_posts,
				'total'   => $total_posts,
				'percent' => $percent,
				'time'    => time(),
			);
		}

		// Individual mode: task count = post count.
		unset( $query_args['status'] );
		$total_query = new Tasks_Query( $query_args );
		$total_tasks = $total_query->found_items;
		$percent     = $total_tasks > 0 ? round( ( $completed_tasks / $total_tasks ) * 100, 1 ) : 0;

		return array(
			'current' => $completed_tasks,
			'total'   => $total_tasks,
			'percent' => $percent,
			'time'    => time(),
		);
	}

	/**
	 * Get comprehensive status for a specific task type.
	 *
	 * @since 3.2.0
	 *
	 * @param string     $task_action Task action (rebuild, migrate, rebuild_query, rebuild_field).
	 * @param array|null $context  Optional context (key + ID).
	 * @return array Task status data including phase and progress.
	 */
	public static function get_task_status( $task_action, $context = null ) {
		// Special handling for standalone sync_post tasks (no parent).
		if ( $task_action === 'sync_post' ) {
			return self::get_standalone_sync_status();
		}

		// Build query args.
		// Check for both 'pending' and 'running' statuses.
		$task_query_args = array(
			'type'   => 'indexer',
			'action' => $task_action,
			'status' => array( 'pending', 'running' ),
			'number' => 1,
			// If leave default order, we should get the first in the queue.
		);

		// Add context filter if provided.
		if ( $context ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for context-specific task queries.
			$task_query_args['meta_query'] = array(
				array(
					'key'     => $context['key'],
					'value'   => $context['id'],
					'compare' => '=',
				),
			);
		}

		// Check for active task.
		$task_query = new Tasks_Query( $task_query_args );

		if ( empty( $task_query->items ) ) {
			return array(
				'status'   => 'idle',
				'phase'    => null,
				'progress' => array(
					'current' => 0,
					'total'   => 0,
					'percent' => 0,
				),
			);
		}

		// Convert Task_Row to Task instance to access get_meta().
		$task_row      = $task_query->items[0];
		$task_instance = new Task();
		$task_instance->load_record( $task_row );

		// Determine phase from metadata.
		$current_page           = $task_instance->get_meta( 'current_page', true );
		$total_pages            = $task_instance->get_meta( 'total_pages', true );
		$preprocessing_complete = $task_instance->get_meta( 'preprocessing_complete', true );

		// Default: task created but not started yet.
		$phase                  = 'initializing';
		$preprocessing_progress = array();

		// Check preprocessing status.
		if ( $preprocessing_complete === 'yes' ) {
			// Preprocessing finished, now syncing children.
			$phase = 'active';
		} elseif ( $current_page && $total_pages ) {
			// Currently generating tasks with progress.
			$phase                  = 'preprocessing';
			$preprocessing_progress = array(
				'pages_done'  => absint( $current_page ),
				'pages_total' => absint( $total_pages ),
				'percent'     => round( ( absint( $current_page ) / absint( $total_pages ) ) * 100, 1 ),
			);
		}
		// Else: stays 'initializing'.

		// Get progress from task counts (pass task instance for batch mode metadata).
		$progress = self::get_parent_task_progress( $task_instance->get_id(), $context, $task_instance );

		return array(
			'status'        => 'active',
			'phase'         => $phase,
			'progress'      => $progress,
			'preprocessing' => $preprocessing_progress,
		);
	}

	/**
	 * Count pending standalone sync_post tasks efficiently.
	 *
	 * Used for consolidation threshold check.
	 *
	 * @since 3.2.0
	 *
	 * @return int Number of pending standalone sync_post tasks.
	 */
	private static function count_pending_standalone_sync_tasks() {
		global $wpdb;
		$table = Base_Task_Runner::get_table_name( 'tasks' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i
					WHERE type = %s AND action = %s AND parent_id = 0 AND status = %s',
					$table,
					'indexer',
					'sync_post',
					'pending'
				)
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Attempt consolidation if threshold met and batching is enabled.
	 *
	 * Called at the start of task_post_sync() for standalone tasks.
	 * Returns a batch task if consolidation was performed, false otherwise.
	 *
	 * @since 3.2.0
	 *
	 * @param Task $current_task The current task being processed.
	 * @return bool True if consolidation happened, false otherwise.
	 */
	private static function maybe_consolidate_standalone_sync_tasks( $current_task ) {
		// Only consolidate if batching is enabled.
		$use_batching = apply_filters( 'search-filter-pro/indexer/use_batching', self::$use_batching );
		if ( ! $use_batching ) {
			return false;
		}

		$threshold = apply_filters( 'search-filter-pro/indexer/batch_consolidation_threshold', 10 );

		if ( self::count_pending_standalone_sync_tasks() < $threshold ) {
			return false;
		}

		return self::consolidate_standalone_sync_tasks();
	}

	/**
	 * Consolidate pending standalone sync_post tasks into a batch task.
	 *
	 * Uses database transaction for atomicity:
	 * 1. Locks pending sync_post tasks with FOR UPDATE
	 * 2. Extracts unique post IDs
	 * 3. Creates a sync_post_batch task with post_ids metadata
	 * 4. Deletes the individual sync_post tasks
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if consolidation happened, false on failure or insufficient tasks.
	 */
	private static function consolidate_standalone_sync_tasks() {
		global $wpdb;
		$table      = Base_Task_Runner::get_table_name( 'tasks' );
		$meta_table = Base_Task_Runner::get_table_name( 'meta' );

		// 1. Fetch pending tasks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, object_id FROM %i
				WHERE type = %s AND action = %s AND parent_id = 0 AND status = %s
				ORDER BY id ASC',
				$table,
				'indexer',
				'sync_post',
				'pending'
			)
		);

		// Need at least 2 tasks to make consolidation worthwhile.
		if ( count( $tasks ) < 2 ) {
			return false;
		}

		// 2. Extract unique post IDs and task IDs.
		$post_ids = array_unique( array_map( 'absint', array_column( $tasks, 'object_id' ) ) );
		$task_ids = array_map( 'absint', array_column( $tasks, 'id' ) );

		// 3. Limit batch size (uses same default as rebuild).
		$max_size = apply_filters( 'search-filter-pro/indexer/batch_consolidation_max_size', self::$default_batch_size );
		if ( count( $post_ids ) > $max_size ) {
			$post_ids = array_slice( $post_ids, 0, $max_size );
			// Only delete tasks for posts we're including.
			$task_ids = array_slice( $task_ids, 0, $max_size );
		}

		// 4. Delete individual tasks first (before creating batch to avoid counting them twice).
		$ids_placeholder = implode( ',', array_fill( 0, count( $task_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$meta_table} WHERE search_filter_task_id IN ({$ids_placeholder})",
				$task_ids
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id IN ({$ids_placeholder})",
				$task_ids
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery

		// 5. Create batch task using standard add_task method.
		$task_data = array(
			'action' => 'sync_post_batch',
			'status' => 'pending',
			'meta'   => array(
				'post_ids'     => $post_ids,
				'post_count'   => count( $post_ids ),
				'consolidated' => 'yes',
			),
		);

		self::add_task( $task_data );

		Util::error_log( 'Consolidated ' . count( $task_ids ) . ' sync_post tasks into batch task', 'notice' );

		// Return true to indicate consolidation happened.
		// The batch task will be processed normally by the task runner.
		return true;
	}

	/**
	 * Get status for standalone sync tasks (without a parent task).
	 *
	 * Unlike parent tasks (rebuild, migrate), standalone sync tasks
	 * are created when individual posts are edited. This method tracks
	 * progress by counting completed vs pending tasks.
	 *
	 * Supports both sync_post and sync_post_batch task types.
	 * For batch tasks, uses post_count metadata for accurate progress.
	 *
	 * @since 3.2.0
	 *
	 * @return array Sync task status data.
	 */
	private static function get_standalone_sync_status() {
		global $wpdb;

		// Disable object caching to ensure fresh database reads.
		wp_using_ext_object_cache( false );

		$table      = Base_Task_Runner::get_table_name( 'tasks' );
		$meta_table = Base_Task_Runner::get_table_name( 'meta' );

		// Count individual sync_post tasks (1 task = 1 post).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$individual = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(*) as count FROM %i
				WHERE type = %s AND action = %s AND parent_id = 0
				AND status IN (%s, %s)
				GROUP BY status',
				$table,
				'indexer',
				'sync_post',
				'pending',
				'complete'
			)
		);

		$individual_pending  = 0;
		$individual_complete = 0;
		if ( $individual ) {
			foreach ( $individual as $row ) {
				if ( $row->status === 'pending' ) {
					$individual_pending = absint( $row->count );
				} elseif ( $row->status === 'complete' ) {
					$individual_complete = absint( $row->count );
				}
			}
		}

		// Sum post_count from standalone batch tasks (1 task = N posts).
		// This handles consolidated batches where post_count metadata stores actual post count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$batch = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					SUM(CASE WHEN t.status = %s THEN CAST(m.meta_value AS UNSIGNED) ELSE 0 END) as pending,
					SUM(CASE WHEN t.status = %s THEN CAST(m.meta_value AS UNSIGNED) ELSE 0 END) as complete
				FROM %i t
				INNER JOIN %i m ON t.id = m.search_filter_task_id AND m.meta_key = %s
				WHERE t.type = %s AND t.action = %s AND t.parent_id = 0
				AND t.status IN (%s, %s)',
				'pending',
				'complete',
				$table,
				$meta_table,
				'post_count',
				'indexer',
				'sync_post_batch',
				'pending',
				'complete'
			)
		);

		$batch_pending  = $batch ? absint( $batch->pending ) : 0;
		$batch_complete = $batch ? absint( $batch->complete ) : 0;

		// Combine: individual tasks + batch post counts.
		$pending_count   = $individual_pending + $batch_pending;
		$completed_count = $individual_complete + $batch_complete;

		$total   = $pending_count + $completed_count;
		$current = $completed_count;

		// No standalone sync tasks.
		if ( $total === 0 ) {
			return array(
				'status'   => 'idle',
				'phase'    => null,
				'progress' => array(
					'current' => 0,
					'total'   => 0,
					'percent' => 0,
				),
			);
		}

		$percent = round( ( $current / $total ) * 100, 1 );

		return array(
			'status'   => 'active',
			'phase'    => 'active',
			'progress' => array(
				'current' => $current,
				'total'   => $total,
				'percent' => $percent,
			),
		);
	}

	/**
	 * Get the current active task.
	 *
	 * Determines which task is currently being processed by checking
	 * parent tasks first, then falling back to standalone sync tasks.
	 *
	 * Supports both individual (sync_post) and batch (sync_post_batch) modes.
	 *
	 * @since 3.2.0
	 *
	 * @return array|null Current task data or null if idle.
	 */
	public static function get_current_task() {
		// Check parent tasks in priority order.
		// Parent tasks stay visible with 'running' status until children finish.
		$task_types = array( 'rebuild', 'migrate', 'rebuild_query', 'rebuild_field' );

		foreach ( $task_types as $task_type ) {
			$status = self::get_task_status( $task_type );

			if ( $status['status'] === 'active' ) {
				return array(
					'type'  => $task_type,
					'phase' => $status['phase'],
				);
			}
		}

		// Fallback: Check for standalone sync tasks (e.g., from post edits).
		// Uses get_standalone_sync_status which checks both sync_post and sync_post_batch.
		$sync_status = self::get_task_status( 'sync_post' );

		if ( $sync_status['status'] === 'active' ) {
			// Determine if batch mode is active for UI display.
			$use_batch_indexing = apply_filters( 'search-filter-pro/indexer/use_batching', self::$use_batching );
			return array(
				'type'  => $use_batch_indexing ? 'sync_post_batch' : 'sync_post',
				'phase' => $sync_status['phase'],
			);
		}

		return null;
	}

	/**
	 * Get migration status.
	 *
	 * Detects migration state and handles edge cases.
	 *
	 * @since 3.2.0
	 *
	 * @return array Migration status data.
	 */
	public static function get_migration_status() {
		$completed = Indexer::migration_completed();

		if ( $completed ) {
			return array(
				'state'   => 'complete',
				'message' => __( 'Using new index system', 'search-filter-pro' ),
			);
		}

		// Check for active migration task.
		$has_task = self::has_task(
			array(
				'action' => 'migrate',
				'status' => array( 'pending', 'running' ),
			)
		);

		if ( $has_task ) {
			return array(
				'state'   => 'in_progress',
				'message' => __( 'Migrating to new index system', 'search-filter-pro' ),
			);
		}

		// Edge case: migration incomplete but no task exists.
		return array(
			'state'   => 'stuck',
			'message' => __( 'Migration incomplete. Click to start migration.', 'search-filter-pro' ),
			'action'  => 'start_migration',
		);
	}

	// =========================================================================
	// Process Control Methods
	// =========================================================================

	/**
	 * Reset the indexer and related tasks.
	 *
	 * @since 3.0.0
	 */
	public static function reset() {
		// CRITICAL: Increment generation FIRST to invalidate any running processes.
		// This ensures stale processes detect the generation change and exit
		// without setting status to 'finished'.
		self::increment_generation();

		self::reset_tasks();
		Indexer::clear_index();
		self::reset_error_count();
		self::reset_process_locks();

		// Reset status to pending when starting fresh.
		self::set_status( 'pending' );
	}

	/**
	 * Check if indexer is in a stop status (paused, error, finished).
	 *
	 * Used to determine if processing should continue or be prevented.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $force_fresh Whether to force a fresh read from database by clearing Data_Store cache.
	 *
	 * @return bool True if stopped (paused/error/finished), false otherwise.
	 */
	public static function is_stopped( $force_fresh = false ) {
		$status = self::get_status( $force_fresh );
		return in_array( $status, self::$stop_statuses, true );
	}

	/**
	 * Run the indexer process.
	 *
	 * @since 3.0.0
	 *
	 * @param string $process_key Optional process key to run.
	 */
	public static function run_processing( $process_key = null ) {

		if ( $process_key === null ) {
			$process_key = self::create_process_key();
		}

		if ( $process_key ) {

			self::set_status( 'processing' );

			// Important: create_process_lock_time() should be called as
			// soon as possible after create_process_key() so that both
			// always exist.
			self::create_process_lock_time();

			$index_method = self::get_processing_method();

			if ( $index_method === 'background' ) {
				self::spawn_run_process( $process_key );
			} else {
				// Run manually.
				self::run_tasks( $process_key );
				self::reset_process_locks();
				self::mark_clean_exit();
			}
		}
	}

	/**
	 * Get the indexer processing method.
	 *
	 * @since 3.0.6
	 *
	 * @return string The indexer method.
	 */
	public static function get_processing_method() {

		if ( ! self::can_use_background_processing() ) {
			return 'manual';
		}

		return self::get_background_processing_option();
	}

	/**
	 * Get the background processing option value.
	 *
	 * @since 3.0.6
	 *
	 * @return string 'background' or 'manual'.
	 */
	public static function get_background_processing_option() {

		$default_method = 'background';

		$indexer_options_value = Options::get( 'indexer', array() );
		if ( isset( $indexer_options_value['useBackgroundProcessing'] ) ) {
			return $indexer_options_value['useBackgroundProcessing'] === 'yes' ? 'background' : 'manual';
		}

		return $default_method;
	}

	/**
	 * Check if the indexer is enabled on the frontend.
	 *
	 * @since 3.0.6
	 *
	 * @return bool Whether the indexer is enabled on the frontend.
	 */
	public static function is_enabled_on_frontend() {
		$indexer_options_value = Options::get( 'indexer', array() );
		if ( isset( $indexer_options_value['enableOnFrontend'] ) ) {
			return $indexer_options_value['enableOnFrontend'] === 'yes' ? true : false;
		}
		return true;
	}

	/**
	 * Spawn a new indexer process.
	 *
	 * @since 3.0.0
	 *
	 * @param string $process_key The process key to spawn.
	 */
	public static function spawn_run_process( $process_key ) {

		// Abort if we have errored.
		if ( self::get_status() === 'error' ) {
			return;
		}

		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Try get and pass any http auth credentials if they exist to send in our rest api request.
		$credentials = \Search_Filter_Pro\Core\Authentication::get_http_auth_credentials();
		if ( ! empty( $credentials ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . base64_encode( $credentials['username'] . ':' . $credentials['password'] );
		}

		$options = array(
			'method'    => 'GET',
			'headers'   => $headers,
			'timeout'   => 0.01,
			'blocking'  => false,
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$rest_url = add_query_arg( 'process_key', $process_key, get_rest_url( null, 'search-filter-pro/v1/indexer/process' ) );
		$result   = wp_remote_post( $rest_url, $options );
		return $result;
	}

	/**
	 * Check for errors.
	 *
	 * @since 3.0.0
	 */
	public static function check_for_errors() {
		self::validate_process();
	}

	/**
	 * Check if a process should be started and if so, start it.
	 *
	 * @since 3.0.0
	 */
	public static function maybe_start_process() {

		wp_using_ext_object_cache( false );

		// No tasks so don't do anything.
		if ( self::has_finished_tasks() ) {
			return;
		}

		if ( self::get_status() === 'paused' ) {
			return;
		}

		// Already a lock in place, so a process is running.
		if ( self::has_process_key() ) {
			return;
		}

		// Try to spawn a new process.
		self::run_processing();
	}

	/**
	 * Is calculating returns whether the indexer is currently calculating
	 * which posts to index.
	 *
	 * @since 3.0.0
	 *
	 * @return string The task type.
	 */
	public static function get_task_type() {
		$current_indexer_task = self::get_next_task();

		if ( ! $current_indexer_task ) {
			return '';
		}

		$task_action = $current_indexer_task->get_action();

		$rebuild_tasks = array( 'rebuild', 'rebuild_field', 'rebuild_query' );
		if ( in_array( $task_action, $rebuild_tasks, true ) ) {
			return 'rebuild';
		}

		$remove_tasks = array( 'remove_query', 'remove_field' );
		if ( in_array( $task_action, $remove_tasks, true ) ) {
			return 'remove';
		}

		$sync_tasks = array( 'sync_post' );
		if ( in_array( $current_indexer_task->get_action(), $sync_tasks, true ) ) {
			return 'sync';
		}

		return '';
	}
}
