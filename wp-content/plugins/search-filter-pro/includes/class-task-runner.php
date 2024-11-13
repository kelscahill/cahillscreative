<?php
/**
 * The task runner.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Options;
use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;
use Search_Filter_Pro\Task_Runner\Task;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The task runner class for queueing and running tasks.
 */
abstract class Task_Runner {

	/**
	 * Current tasks to run.
	 *
	 * @var array
	 */
	protected static $tasks = array();

	/**
	 * Task batch size.
	 *
	 * @var int
	 */
	private static $batch_size = -1;

	/**
	 * Start time for the task runner.
	 *
	 * @var int
	 */
	private static $process_start_time = 0;

	/**
	 * Has init tasks.
	 *
	 * @var bool
	 */
	private static $has_init_tasks = false;

	/**
	 * The task type to handle.
	 *
	 * @since    3.0.0
	 *
	 * @var      array    $handlers    The task handlers.
	 */
	protected static $type = '';

	/**
	 * Error count.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private static $option_error_name = 'task-runner-error_';

	/**
	 * Maximum number of errors before we show a message to the user.
	 *
	 * @var array
	 */
	protected static $error_count_limit = 2;

	/**
	 * Lock error timeout buffer.
	 *
	 * If current time has gone past the lock time plus the grace period
	 * timeout, then we'll assume that the process has died and we need to
	 * re-start it.
	 *
	 * @var int
	 */
	private static $lock_error_timeout = 10;

	/**
	 * The option name for the process.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private static $option_process_name = 'task-runner-process_';

	/**
	 * The options name for the process time.
	 *
	 * @var array
	 */
	private static $option_process_lock_time_name = 'task-runner-process-time_';

	/**
	 * The options name for the process time.
	 *
	 * @var array
	 */
	private static $option_status_name = 'task-runner-status_';

	/**
	 * The statuses that signify the task runner is stopped.
	 *
	 * @var array
	 */
	public static $stop_statuses = array( 'error', 'finished', 'paused' );

	/**
	 * Init the task runner.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		do_action( 'search-filter/task_runner/init' );
	}

	/**
	 * Add a task to the queue.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $task_data    The task to add.
	 */
	public static function add_task( $task_data ) {

		$defaults  = array(
			'type'      => static::$type,
			'action'    => '',
			'status'    => 'pending',
			'object_id' => 0,
		);
		$task_data = wp_parse_args( $task_data, $defaults );

		// Check the same task doesn't already exist.
		$task_query_data = array(
			'type'      => $task_data['type'],
			'action'    => $task_data['action'],
			'status'    => $task_data['status'],
			'object_id' => $task_data['object_id'],
		);
		if ( isset( $task_data['meta'] ) ) {
			foreach ( $task_data['meta'] as $key => $value ) {
				$task_query_data['meta_query'][] = array(
					'key'     => $key,
					'value'   => $value,
					'compare' => '=',
				);
			}
		}
		$query = new Tasks_Query( $task_query_data );
		if ( count( $query->items ) > 0 ) {
			return;
		}

		// Now build the task object.
		// If there is meta set, then we need to remove it from the task and apply it seperately.
		$meta = isset( $task_data['meta'] ) ? $task_data['meta'] : array();
		unset( $task_data['meta'] );

		$task_instance = new Task( $task_data );
		$task_instance->save();

		if ( ! empty( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$task_instance->add_meta( $key, $value, true );
			}
		}
	}

	/**
	 * Get the tasks.
	 *
	 * @param    string $status    The status of the tasks to get.
	 *
	 * @return   array    The tasks.
	 *
	 * @since    3.0.0
	 */
	private static function get_tasks( $status = 'pending' ) {
		if ( ! self::$has_init_tasks ) {
			self::$has_init_tasks = true;
			self::refresh_tasks( $status );
		}
		return static::$tasks;
	}

	/**
	 * Clear the tasks by meta query
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The args to filter by.
	 * @return bool True if the task exists.
	 */
	public static function has_task( $args ) {
		$query_args = array(
			'type' => static::$type,
		);
		$args       = wp_parse_args( $args, $query_args );
		$query      = new Tasks_Query( $args );
		if ( $query ) {
			if ( count( $query->items ) > 0 ) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Refresh the tasks.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $status    The status of the tasks to refresh.
	 */
	private static function refresh_tasks( $status = 'pending' ) {
		$query = new Tasks_Query(
			array(
				'type'    => static::$type,
				'status'  => $status,
				'number'  => 100,
				'order'   => 'ASC',
				'orderby' => 'date_modified',
			)
		);

		if ( $query ) {
			foreach ( $query->items as $task ) {
				$task_instance = new Task();
				$task_instance->load_record( $task );
				static::$tasks[] = $task_instance;
			}
		}
		return static::$tasks;
	}

	/**
	 * Check if there are any local tasks left.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if we have time to run tasks.
	 */
	public static function has_tasks() {
		if ( count( self::get_tasks() ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the next local task.
	 *
	 * @since    3.0.0
	 */
	protected static function get_next_task() {
		if ( self::has_tasks() ) {
			$task = &static::$tasks[0];
			return $task;
		}
		return false;
	}

	/**
	 * Remove a task from the front of the queue and delete it
	 * from the database.
	 *
	 * @since    3.0.0
	 */
	private static function complete_next_task() {
		if ( self::has_tasks() ) {
			array_shift( static::$tasks );
		}
	}

	/**
	 * Run a task.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to run.
	 */
	abstract protected static function run_task( &$task );
	/**
	 * Run the tasks.
	 *
	 * @since    3.0.0
	 *
	 * @param string $process_key    The process key to run the tasks for.
	 */
	public static function run_tasks( $process_key = '' ) {

		wp_using_ext_object_cache( false );

		// Don't run tasks without a valid process key.
		if ( ! self::is_valid_process_key( $process_key ) ) {
			return;
		}

		$status = self::get_status();

		if ( $status !== 'processing' ) {
			return;
		}
		$new_lock_time = self::get_new_process_lock_time();

		// Check if there are any issues with the lock time.
		if ( ! $new_lock_time ) {
			self::validate_process_lock_time();
			return;
		}

		// Reset the error count as we got through the process ok.
		self::reset_error_count();

		if ( ! self::has_tasks() ) {
			return;
		}

		// Start the timer.
		static::$process_start_time = time();

		$task_counter = 0;

		Util::raise_memory_limit();

		do {
			$task = self::get_next_task();

			// Process the task.
			static::run_task( $task );

			// If its marked as complete, then remove it from the queue.
			if ( $task->get_status() === 'complete' ) {
				self::complete_next_task();
			} elseif ( $task->get_status() === 'error' ) {
				// TODO - lets log the errors using our debugging tools.
				self::complete_next_task();
			}

			// If we've got to the end of the fetched tasks,
			// refresh to see if there are any more.
			if ( count( self::get_tasks() ) === 0 ) {
				// Refresh the tasks list from the DB.
				self::refresh_tasks();
			}
			$task_counter++;

		} while (
			self::is_valid_process_key( $process_key ) &&
			self::has_tasks() &&
			self::process_has_time_left() &&
			self::has_memory() &&
			self::has_batch_remaining( $task_counter )
		);

		self::clear_process_lock_time();

		// If we've completed all the tasks, then we can reset the process.
		if ( self::has_finished_tasks() ) {
			self::clear_process_key();
			self::clear_tasks( array( 'status' => 'complete' ) );
			self::set_status( 'finished' );
			Util::error_log( 'Task runner: finished tasks.', 'notice' );
			do_action( 'search-filter/task_runner/finished' );
		}

		Util::clear_caches();
	}

	/**
	 * Get the individual object indexed count.
	 *
	 * @since 3.0.0
	 *
	 * @param string $action The action to get the count for.
	 * @return int    The number of objects in the count result.
	 */
	public static function get_tasks_objects_count( $action ) {

		// Add the filter to do a custom select.
		add_filter( 'search_filter_tasks_query_clauses', array( __CLASS__, 'update_tasks_query_unique_count_clauses' ) );

		$query = new Tasks_Query(
			array(
				'action' => $action,
				// Need to set this to count so the query is handled a count
				// query in BerlinDB.
				'count'  => true,
				'number' => -1,
			)
		);

		// Remove the filter after the query is run.
		remove_filter( 'search_filter_tasks_query_clauses', array( __CLASS__, 'update_tasks_query_unique_count_clauses' ) );

		// Items contains the count of the objects.
		return $query->items;
	}

	/**
	 * To avoid big queries, better we update the SQL to select distinct columns.
	 *
	 * @since 3.0.0
	 *
	 * @param array $clauses The clauses to update.
	 * @return array    The updated clauses.
	 */
	public static function update_tasks_query_unique_count_clauses( $clauses ) {
		// Make sure we use distinct on the object ID to only count unique object_ids.
		$clauses['fields'] = 'COUNT( DISTINCT object_id ) as COUNT';
		return $clauses;
	}

	/**
	 * Check if we have finished tasks.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if we have finished tasks.
	 */
	public static function has_finished_tasks() {
		wp_using_ext_object_cache( false );

		$query = new Tasks_Query(
			array(
				'type'   => static::$type,
				'status' => 'pending',
				'number' => 1,
			)
		);

		if ( $query ) {
			return count( $query->items ) === 0;
		}
		return false;
	}

	/**
	 * Clear the tasks for the task type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $delete_where The where clause to delete the tasks.
	 */
	public static function clear_tasks_old( $delete_where = array() ) {
		$delete_defaults = array(
			'type' => static::$type,
		);
		$delete_where    = wp_parse_args( $delete_where, $delete_defaults );
		$query           = new Tasks_Query();
		$query->delete_items( $delete_where );

		static::$tasks = array();
		self::refresh_tasks();

	}
	public static function clear_tasks( $delete_where = array() ) {
		$delete_defaults = array(
			'type' => static::$type,
		);
		$delete_where    = wp_parse_args( $delete_where, $delete_defaults );
		$query           = new Tasks_Query();
		$query->delete_items_raw( $delete_where );

		static::$tasks = array();
		self::refresh_tasks();
	}

	/**
	 * Reset the tasks for the task type.
	 *
	 * It's similar to clear tasks but uses a more efficient delete query.
	 *
	 * @since 3.0.0
	 */
	public static function reset_tasks() {
		$query = new Tasks_Query();
		$query->delete_items_raw( array( 'type' => static::$type ) );

		static::$tasks = array();
		self::refresh_tasks();
	}

	/**
	 * Try to get the lock.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if the lock is set.
	 */
	public static function get_new_process_lock_time() {
		$option_name = self::$option_process_lock_time_name . static::$type;
		$lock        = Options::get_option_value( $option_name );
		$is_locked   = $lock !== false;

		// If already locked, then return false.
		if ( $is_locked === true ) {
			return false;
		}

		// Try to get a new lock.
		$new_lock = time() + self::get_time_limit();

		try {
			$result = Options::create_option_value( $option_name, $new_lock );

		} catch ( \Exception $e ) {
			// If there was an exception then we couldn't get a lock.
			return false;
		}

		// Result would be false if it failed.
		return $result !== false;
	}

	/**
	 * Check if the lock has expired.
	 *
	 * @since    3.0.0
	 */
	public static function validate_process_lock_time() {

		$option_name = self::$option_process_lock_time_name . static::$type;
		$lock        = Options::get_option_value( $option_name );

		if ( $lock === false ) {
			return;
		}

		// Check if there are any issues with the lock.
		$time_limit = intval( $lock ) + self::$lock_error_timeout;

		if ( time() >= $time_limit ) {

			$error_count = self::error_count();
			$error_count++;
			self::set_error_count( $error_count );
			Util::error_log( esc_html__( 'Task runner: lock expired.', 'search-filter' ) );

			if ( $error_count < self::$error_count_limit ) {
				// Reset the process key so we can try again.
				self::reset_process_locks();
				do_action( 'search-filter/task_runner/process_expired' );
			} else {
				// Reached the limit of errors, need to display a message to the user.
				Util::error_log( esc_html__( 'Task runner: error count limit reached.', 'search-filter' ) );
				do_action( 'search-filter/task_runner/process_stalled' );
			}
		}
	}

	/**
	 * Increment the error count.
	 *
	 * @since 3.0.0
	 */
	protected static function increment_error_count() {
		$error_count = self::error_count();
		$error_count++;
		self::set_error_count( $error_count );
	}

	/**
	 * Check if the lock is active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if the lock is active.
	 */
	public static function has_process_lock_time() {
		$option_name = self::$option_process_lock_time_name . static::$type;
		$lock        = Options::get_option_value( $option_name );
		if ( $lock !== false ) {
			$time_limit = intval( $lock ) + self::$lock_error_timeout;
			if ( time() < $time_limit ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Unlock the process.
	 *
	 * @since    3.0.0
	 */
	private static function clear_process_lock_time() {
		Options::delete_option( self::$option_process_lock_time_name . static::$type );
	}
	/**
	 * Check if there is time left to run tasks.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if we have time to run tasks.
	 */
	private static function process_has_time_left() {
		// Make sure we're comfortably within the time limit.
		$time_limit = static::$process_start_time + self::get_time_limit();
		return time() < $time_limit;
	}

	/**
	 * Get the time limit for the task runner.
	 *
	 * @since    3.0.0
	 *
	 * @return   int    The time limit for the task runner.
	 */
	private static function get_time_limit() {
		$time_limit = 30;

		// Try to get the max execution time from the ini settings.
		// Some hosts can have a really high max execution time (misreported?)
		// so lets limit it to 60 seconds.
		$max_execution_time = Util::get_max_execution_time();
		if ( $max_execution_time > 60 ) {
			$max_execution_time = 60;
		}
		if ( $max_execution_time !== 0 && $max_execution_time < $time_limit ) {
			$time_limit = $max_execution_time;
		}

		return $time_limit;
	}
	/**
	 * Check if there is memory left to run tasks.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if we have memory to run tasks.
	 */
	private static function has_memory() {
		// Make sure we're comfortably within the memory limit.
		$memory_limit   = Util::get_memory_limit() * 0.8;
		$current_memory = memory_get_usage( true );
		return $current_memory < $memory_limit;
	}

	/**
	 * Check if there are enough tasks left in the batch.
	 *
	 * By default there is no limit, used to override as a backup.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $task_counter    The number of tasks we've run.
	 *
	 * @return   bool    True if we have time to run tasks.
	 */
	private static function has_batch_remaining( $task_counter = 0 ) {
		// Allow the batch size to be overridden.
		$batch_size = apply_filters( 'search-filter-pro/task_runner/batch_size', static::$batch_size );
		// If we're not overriding the batch size, then we should run as many tasks as possible.
		if ( $batch_size === -1 ) {
			return true;
		}
		return $task_counter < $batch_size;
	}





	/**
	 * Get the error count for the current taks type.
	 */
	protected static function error_count() {
		$option_name = self::$option_error_name . static::$type;
		$error_count = Options::get_option_value( $option_name );
		if ( $error_count === false ) {
			$error_count = 0;
		}
		return absint( $error_count );
	}

	/**
	 * Increment the error count for the current taks type.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $error_count    The error count to set.
	 */
	private static function set_error_count( $error_count = 0 ) {
		$option_name = self::$option_error_name . static::$type;
		Options::update_option_value( $option_name, $error_count );
	}

	/**
	 * Reset the error count for the current taks type.
	 *
	 * @since    3.0.0
	 */
	protected static function reset_error_count() {
		$option_name = self::$option_error_name . static::$type;
		Options::delete_option( $option_name );
	}

	/**
	 * Check if we've reached the error limit.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if we've reached the error limit.
	 */
	protected static function has_reached_error_limit() {
		$error_count = self::error_count();
		return $error_count >= self::$error_count_limit;
	}

	/**
	 * Verify the process key.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $process_key    The process key to verify.
	 * @return   bool    True if the process key is valid.
	 */
	public static function is_valid_process_key( $process_key ) {

		if ( empty( $process_key ) ) {
			return false;
		}
		$process_key = sanitize_text_field( $process_key );
		if ( empty( $process_key ) ) {
			return false;
		}

		$option_name = self::$option_process_name . static::$type;

		$option_process_key = Options::get_option_value( $option_name );
		if ( $process_key !== $option_process_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the process key.
	 *
	 * @since 3.0.0
	 *
	 * @return string    The process key.
	 */
	private static function get_process_key() {
		$option_name = self::$option_process_name . static::$type;
		$process_key = Options::get_option_value( $option_name );
		return $process_key;
	}

	/**
	 * Create a new process key.
	 *
	 * @since 3.0.0
	 *
	 * @return   string    The process key.
	 */
	public static function create_process_key() {
		$option_name = self::$option_process_name . static::$type;

		// Use the same method as WP Application Passwords to generate a secure and unique process key.
		$process_key = wp_hash_password( wp_generate_password( 24, false ) );

		try {
			Options::create_option_value( $option_name, $process_key );
		} catch ( \Exception $e ) {
			$process_key = false;
		}
		return $process_key;
	}

	/**
	 * Has process key.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if the process key is set.
	 */
	public static function has_process_key() {
		$option_name = self::$option_process_name . static::$type;
		$process_key = Options::get_option_value( $option_name );
		return $process_key !== false;
	}

	/**
	 * Clear the process key.
	 *
	 * @since 3.0.0
	 */
	private static function clear_process_key() {
		$option_name = self::$option_process_name . static::$type;
		Options::delete_option( $option_name );
	}

	/**
	 * Reset the locks.
	 *
	 * @since 3.0.0
	 */
	public static function reset_process_locks() {
		self::clear_process_lock_time();
		self::clear_process_key();
	}

	/**
	 * Pause the indexer process.
	 *
	 * @since 3.0.0
	 */
	public static function pause_process() {
		self::clear_process_lock_time();
		self::clear_process_key();
		self::set_status( 'paused' );
	}

	/**
	 * Set the status of the task runner for the task type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $status    The status to set.
	 *
	 * @return bool    True if the status was updated.
	 */
	public static function set_status( $status ) {
		$option_name = self::$option_status_name . static::$type;
		$update      = Options::update_option_value( $option_name, $status );
		return $update !== false;
	}

	/**
	 * Get the status of the task runner for the task type.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The status of the indexer.
	 */
	public static function get_status() {
		$option_name = self::$option_status_name . static::$type;
		$status      = Options::get_option_value( $option_name );
		if ( $status === false ) {
			$status = 'pending';
		}
		return $status;
	}

	/**
	 * Clear the status.
	 *
	 * @since 3.0.0
	 */
	protected static function clear_status() {
		$option_name = self::$option_status_name . static::$type;
		Options::delete_option( $option_name );
	}

	/**
	 * Try to clear the status.
	 *
	 * Don't allow the status to be cleared if we're in progress or paused.
	 *
	 * @since 3.0.0
	 */
	public static function try_clear_status() {
		wp_using_ext_object_cache( false );

		// Don't clear the status if we're in progress or paused.
		if ( self::get_status() === 'processing' ) {
			return;
		}
		if ( self::get_status() === 'paused' ) {
			return;
		}
		self::clear_status();
	}
}
