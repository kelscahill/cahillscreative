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

use Search_Filter\Database\Transaction;
use Search_Filter\Options;
use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Database\Queries\Options_Direct;
use Search_Filter_Pro\Task_Runner\Cron;
use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;
use Search_Filter_Pro\Task_Runner\Database\Task_Query_Direct;
use Search_Filter_Pro\Task_Runner\Rest_API;
use Search_Filter_Pro\Task_Runner\Task;
use Search_Filter_Pro\Task_Runner\Task_Signal;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The task runner class for queueing and running tasks.
 */
abstract class Task_Runner {

	/**
	 * Table keys for task runner tables.
	 *
	 * @var array
	 */
	const TABLE_KEYS = array(
		'tasks',
		'tasks_meta',
	);

	/**
	 * Map of short names to full table keys.
	 *
	 * @var array<string, string>
	 */
	const TABLE_KEY_MAP = array(
		'tasks' => 'tasks',
		'meta'  => 'tasks_meta',
	);

	/**
	 * Get a task runner table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'tasks' or 'meta'. Default 'tasks'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter_Pro\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $type = 'tasks', $should_use = true ) {
		$key = self::TABLE_KEY_MAP[ $type ] ?? 'tasks';
		return Table_Manager::get( $key, $should_use );
	}

	/**
	 * Get a task runner table name.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'tasks' or 'meta'. Default 'tasks'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $type = 'tasks', $should_use = true ) {
		$table = self::get_table( $type, $should_use );
		return $table ? $table->get_table_name() : '';
	}

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
	protected static $batch_size = -1;

	/**
	 * Start time for the task runner.
	 *
	 * @var int
	 */
	protected static $process_start_time = 0;

	/**
	 * The process key for the current run.
	 *
	 * Stored so that sub-methods (e.g. batch sync) can check
	 * whether this process is still the active one.
	 *
	 * @var string
	 */
	protected static $current_process_key = '';

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
	 * @var string
	 */
	private static $option_error_name = 'task-runner-error_';

	/**
	 * Maximum number of errors before we show a message to the user.
	 *
	 * @var int
	 */
	protected static $error_count_limit = 10;

	/**
	 * Lock error timeout buffer.
	 *
	 * If current time has gone past the lock time plus the grace period
	 * timeout, then we'll assume that the process has died and we need to
	 * re-start it.
	 *
	 * @var int
	 */
	private static $lock_error_timeout = 20;

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
	 * @var string
	 */
	private static $option_process_lock_time_name = 'task-runner-process-time_';

	/**
	 * The options name for the process time.
	 *
	 * @var string
	 */
	private static $option_status_name = 'task-runner-status_';

	/**
	 * The option name for the generation counter.
	 *
	 * Generation tracking prevents race conditions when a rebuild is triggered
	 * while another is in progress. Each rebuild increments the generation,
	 * allowing stale processes to detect they should exit without modifying status.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private static $option_generation_name = 'task-runner-generation_';

	/**
	 * The option name for the process generation.
	 *
	 * Stores which generation the current process belongs to.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private static $option_process_generation_name = 'task-runner-process-generation_';

	/**
	 * The statuses that signify the task runner is stopped.
	 *
	 * @var array
	 */
	public static $stop_statuses = array( 'error', 'finished', 'paused' );

	/**
	 * Whether the process exited cleanly.
	 *
	 * Used by the shutdown handler to detect abnormal termination.
	 * If still false when the handler runs, locks are released for
	 * immediate recovery instead of waiting for lock expiry.
	 *
	 * @since 3.2.3
	 *
	 * @var bool
	 */
	private static $clean_exit = false;

	/**
	 * Whether the task runner is actively processing tasks.
	 *
	 * True only while inside the run_tasks() processing loop.
	 * The shutdown handler checks this to avoid firing for
	 * unrelated fatals that happen after a batch completes.
	 *
	 * @since 3.3.3
	 *
	 * @var bool
	 */
	private static $is_processing = false;

	/**
	 * Whether the shutdown handler has been registered.
	 *
	 * @since 3.2.3
	 *
	 * @var bool
	 */
	private static $shutdown_registered = false;

	/**
	 * Test background processing option name.
	 *
	 * @since 3.1.2
	 *
	 * @var string
	 */
	public static $can_background_process_option_name = 'task-runner-can-background-process';

	/**
	 * Init the task runner.
	 *
	 * @since    3.0.0
	 */
	public static function init() {

		// Register table with Table_Manager.
		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );

		Rest_API::init();
		Cron::init();
	}

	/**
	 * Register task runner tables.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 * This is more robust than a static flag because it correctly handles
	 * Table_Manager::reset() scenarios (e.g., in tests).
	 *
	 * @since    3.2.0
	 */
	public static function register_tables() {
		// Guard: Skip if tables are already registered.
		if ( Table_Manager::has( 'tasks' ) ) {
			return;
		}

		Table_Manager::register( 'tasks', \Search_Filter_Pro\Task_Runner\Database\Tasks_Table::class, true );
		Table_Manager::register( 'tasks_meta', \Search_Filter_Pro\Task_Runner\Database\Tasks_Meta_Table::class, true );
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
			'parent_id' => 0,
		);
		$task_data = wp_parse_args( $task_data, $defaults );

		// Check the same task doesn't already exist.
		$task_query_data = array(
			'type'      => $task_data['type'],
			'action'    => $task_data['action'],
			'status'    => $task_data['status'],
			'object_id' => $task_data['object_id'],
			'parent_id' => $task_data['parent_id'],
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

		self::get_table( 'tasks', true );
		self::get_table( 'meta', true );

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
	 * Batch add tasks to the queue.
	 *
	 * This method uses direct SQL insertion for optimal performance when adding
	 * multiple tasks at once. Does not check for duplicates. Uses transactions
	 * to ensure atomicity when adding metadata.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $tasks_data    Array of task data arrays to add.
	 * @param    array $meta          Optional metadata to apply to ALL tasks in the batch.
	 * @return   array|false          Array with 'count' and 'ids' of inserted tasks, or false on error.
	 * @throws   \Exception           If task insertion or metadata insertion fails.
	 */
	public static function batch_add_tasks( $tasks_data, $meta = array() ) {
		if ( empty( $tasks_data ) || ! is_array( $tasks_data ) ) {
			return false;
		}

		// Start transaction to ensure atomicity.
		Transaction::start();

		try {
			// Apply defaults to each task.
			$tasks_for_insert = array();

			foreach ( $tasks_data as $task_data ) {
				$defaults  = array(
					'type'      => static::$type,
					'action'    => '',
					'status'    => 'pending',
					'object_id' => 0,
					'parent_id' => 0,
				);
				$task_data = wp_parse_args( $task_data, $defaults );

				$tasks_for_insert[] = $task_data;
			}

			// Use direct query for batch insert.
			$result = Task_Query_Direct::batch_insert( $tasks_for_insert );

			if ( ! $result || ! isset( $result['ids'] ) ) {
				throw new \Exception( 'Task insertion failed' );
			}

			// Insert metadata if provided (applies to ALL tasks).
			if ( ! empty( $meta ) && is_array( $meta ) ) {
				$meta_data = array();

				// Apply same metadata to all inserted tasks.
				foreach ( $result['ids'] as $task_id ) {
					foreach ( $meta as $meta_key => $meta_value ) {
						$meta_data[] = array(
							'task_id'    => $task_id,
							'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						);
					}
				}

				// Batch insert all metadata.
				if ( ! empty( $meta_data ) ) {
					$meta_result = Task_Query_Direct::batch_insert_meta( $meta_data );

					if ( false === $meta_result ) {
						throw new \Exception( 'Metadata insertion failed' );
					}
				}
			}

			// Commit transaction.
			Transaction::commit();

			return $result;

		} catch ( \Exception $e ) {
			// Rollback on error.
			Transaction::rollback();
			Util::error_log( 'Batch add tasks error: ' . $e->getMessage(), 'error' );
			return false;
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
		if ( count( $query->items ) > 0 ) {
			return true;
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

		foreach ( $query->items as $task ) {
			$task_instance = new Task();
			$task_instance->load_record( $task );
			static::$tasks[] = $task_instance;
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
	 * Remove a task from the front of the queue.
	 *
	 * Also useful after a parent task transitions to 'running' status
	 * (preprocessing complete, waiting for children). Removing it from the
	 * local queue allows refresh_tasks() to be triggered, fetching the new
	 * child tasks immediately rather than looping on the parent until timeout.
	 *
	 * @since    3.0.0
	 */
	protected static function complete_next_task() {
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
	 * @return   int  A Task_Signal constant indicating execution outcome.
	 */
	abstract protected static function run_task( &$task ): int;
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

		// Store process key so sub-methods can check validity.
		static::$current_process_key = $process_key;

		// Register shutdown handler to release locks on abnormal termination.
		self::$clean_exit = false;
		self::register_shutdown_handler();

		$status = self::get_status();

		if ( $status !== 'processing' ) {
			return;
		}

		// Record the generation at the START of processing.
		// This allows us to detect if a new rebuild was triggered mid-processing.
		$process_generation = self::get_process_generation();

		// Reset the error count as we got through the process ok.
		self::reset_error_count();

		// Start the timer.
		static::$process_start_time = time();

		$task_counter = 0;

		Util::raise_memory_limit();

		self::$is_processing = true;

		do {
			// Check if generation changed mid-processing.
			if ( ! self::is_current_generation( $process_generation ) ) {
				Util::error_log( 'Task runner: generation changed during processing - exiting gracefully.', 'notice' );
				break;
			}

			$task = self::get_next_task();

			// This should technically never happen, but as a safeguard lets
			// ensure we have valid task, in case something ran `run_tasks`
			// but there were no tasks in the queue.
			if ( ! $task ) {
				break;
			}

			// Process the task.
			$signal = static::run_task( $task );

			// If its marked as complete, then remove it from the queue.
			if ( $task->get_status() === 'complete' ) {
				self::complete_next_task();
			} elseif ( $task->get_status() === 'error' ) {
				Util::error_log( 'Task runner: error running task: ' . $task->get_action(), 'error' );
				self::complete_next_task();
			}

			// Task reported it couldn't run — stop processing, let next request pick up.
			if ( $signal === Task_Signal::TIME_LIMITED ) {
				break;
			}

			// If we've got to the end of the fetched tasks,
			// refresh to see if there are any more.
			if ( count( self::get_tasks() ) === 0 ) {
				// Refresh the tasks list from the DB.
				self::refresh_tasks();
			}
			++$task_counter;

		} while (
			self::is_valid_process_key( $process_key ) && // @phpstan-ignore booleanAnd.leftAlwaysTrue (Value can change between iterations)
			self::is_current_generation( $process_generation ) && // @phpstan-ignore booleanAnd.rightAlwaysTrue (Value can change between iterations)
			self::has_tasks() &&
			self::process_has_time_left() &&
			self::has_memory() &&
			self::has_batch_remaining( $task_counter )
		);

		// Only set 'finished' if we're still the active process AND current generation.
		// Prevents a stale process from overriding a new rebuild's status or
		// nuking a new process's key after lock expiry.
		if ( self::has_finished_tasks() && self::is_current_process() && self::is_current_generation( $process_generation ) ) {
			self::reset_process_locks();
			self::clear_tasks( array( 'status' => 'complete' ) );
			self::set_status( 'finished' );
			Util::error_log( 'Task runner: finished tasks.', 'notice' );
			do_action( 'search-filter-pro/task_runner/finished' );
		} elseif ( ! self::is_current_generation( $process_generation ) ) {
			// Stale process - clean up our locks but don't modify status.
			self::reset_process_locks();
			Util::error_log( 'Task runner: stale process exiting without status change - new rebuild pending.', 'notice' );
		}

		self::$is_processing = false;

		Util::clear_object_caches();
	}


	/**
	 * Finish tasks.
	 *
	 * @since 3.0.0
	 */
	public static function finish_tasks() {
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
				'status' => array( 'pending', 'running' ),
				'number' => 1,
			)
		);

		return count( $query->items ) === 0;
	}

	/**
	 * Clear tasks matching specific criteria.
	 *
	 * Uses high-performance bulk delete that properly cleans up metadata.
	 *
	 * @since 3.0.0
	 *
	 * @param array $delete_where The where clause to delete the tasks.
	 */
	public static function clear_tasks( $delete_where = array() ) {
		$delete_defaults = array(
			'type' => static::$type,
		);
		$delete_where    = wp_parse_args( $delete_where, $delete_defaults );

		// Use high-performance bulk delete that handles metadata properly.
		Task_Query_Direct::batch_delete( $delete_where );

		self::flush();
	}

	/**
	 * Reset all tasks for the task type.
	 *
	 * Uses TRUNCATE for maximum performance when clearing all tasks.
	 * This clears ALL tasks and metadata regardless of type.
	 *
	 * @since 3.0.0
	 */
	public static function reset_tasks() {
		// Use TRUNCATE for fastest full reset.
		Task_Query_Direct::truncate_all();

		self::flush();
	}

	/**
	 * Flush in-memory state only (no DB changes).
	 *
	 * Clears the in-memory task queue, task init flag, current process key,
	 * and process timer. Call this when DB state has changed externally
	 * (e.g. after adding tasks) and the in-memory cache is stale.
	 *
	 * @since 3.3.3
	 */
	public static function flush() {
		static::$tasks               = array();
		self::$has_init_tasks        = false;
		static::$current_process_key = '';
		static::$process_start_time  = 0;
	}

	/**
	 * Try to get the lock.
	 *
	 * @since    3.0.0
	 *
	 * @return   int|false    The lock time if successful, false otherwise.
	 */
	public static function create_process_lock_time() {
		$option_name = self::$option_process_lock_time_name . static::$type;
		// Try to get a new lock.
		$new_lock = time() + self::get_time_limit();
		try {
			$result = Options::create( $option_name, $new_lock );
		} catch ( \Exception $e ) {
			// If there was an exception then we couldn't get a lock.
			return false;
		}
		// Result would be false if it failed.
		return $new_lock;
	}

	/**
	 * Check if the lock has expired, ignore if no lock is set.
	 *
	 * @since    3.0.0
	 */
	protected static function validate_process() {

		if ( ! self::has_process_key() ) {
			return;
		}

		$option_name = self::$option_process_lock_time_name . static::$type;
		$lock        = Options::get( $option_name );

		// This should not happen as we set and clear process time in sync with the process
		// key.  But this did happen in versions <= 3.1.4.
		if ( $lock === null ) {
			$error_count = self::increment_error_count();
			if ( $error_count < self::$error_count_limit ) {
				Util::error_log( __( 'Task runner: lock time not found.', 'search-filter-pro' ), 'error' );
				// Reset the process locks so we can try again.
				self::reset_process_locks();
			}
			return;
		}

		// Check if there are any issues with the lock.
		$time_limit = intval( $lock ) + self::$lock_error_timeout;

		if ( time() >= $time_limit ) {
			self::reset_process_locks();
			Util::error_log( __( 'Task runner: lock expired.', 'search-filter-pro' ), 'error' );
			$error_count = self::get_error_count();
			if ( $error_count < self::$error_count_limit ) {
				// Reset the process key so we can try again.
				self::increment_error_count();
			}
		}
	}

	/**
	 * Increment the error count.
	 *
	 * @since 3.0.0
	 */
	protected static function increment_error_count() {
		$option_name = self::$option_error_name . static::$type;
		$error_count = Options_Direct::increment( $option_name );

		if ( $error_count >= self::$error_count_limit ) {
			// Reached the limit of errors, need to display a message to the user.
			self::set_status( 'error' );
			Util::error_log( __( 'Task runner: error count limit reached.', 'search-filter-pro' ), 'error' );
		}

		return $error_count;
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
		$lock        = Options::get( $option_name );
		if ( $lock !== null ) {
			$time_limit = intval( $lock ) + self::$lock_error_timeout;
			if ( time() < $time_limit ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Reset the locks.
	 *
	 * @since 3.0.0
	 */
	public static function refresh_process_lock_time() {
		$option_name = self::$option_process_lock_time_name . static::$type;
		$new_lock    = time() + self::get_time_limit();
		Options_Direct::upsert( $option_name, $new_lock );
	}
	/**
	 * Unlock the process.
	 *
	 * @since    3.0.0
	 */
	private static function clear_process_lock_time() {
		Options::delete( self::$option_process_lock_time_name . static::$type );
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
	protected static function get_time_limit() {
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
	protected static function get_error_count() {
		$option_name = self::$option_error_name . static::$type;
		$error_count = Options::get( $option_name );
		if ( $error_count === null ) {
			$error_count = 0;
		}
		return absint( $error_count );
	}

	/**
	 * Reset the error count for the current taks type.
	 *
	 * @since    3.0.0
	 */
	public static function reset_error_count() {
		$option_name = self::$option_error_name . static::$type;
		Options::delete( $option_name );
	}

	/**
	 * Check if we've reached the error limit.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if we've reached the error limit.
	 */
	public static function has_reached_error_limit() {
		$error_count = self::get_error_count();
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

		$option_name = self::$option_process_name . static::$type;

		$option_process_key = Options::get( $option_name );
		if ( $process_key !== $option_process_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if this is still the active process.
	 *
	 * Uses the stored process key from run_tasks() to verify
	 * that this process hasn't been superseded by a new one
	 * (e.g. after lock expiry).
	 *
	 * @since 3.3.3
	 *
	 * @return bool True if this process is still active.
	 */
	public static function is_current_process() {
		return self::is_valid_process_key( static::$current_process_key );
	}

	/**
	 * Get the process key.
	 *
	 * @since 3.0.0
	 *
	 * @return string    The process key.
	 */
	public static function get_process_key() {
		$option_name = self::$option_process_name . static::$type;
		$process_key = Options::get( $option_name );
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
		$process_key = wp_hash_password( wp_generate_password( 24, true, false ) );

		// Atomic claim — if another process already created a key, this no-ops and returns false.
		$claimed = Options_Direct::claim( $option_name, $process_key );
		return $claimed ? $process_key : false;
	}

	/**
	 * Create a process with generation tracking.
	 *
	 * @since 3.0.0
	 *
	 * @return string|false The process key or false on failure.
	 */
	public static function create_process() {
		// Record which generation this process belongs to.
		$generation = self::get_current_generation();
		self::set_process_generation( $generation );

		$process_key = self::create_process_key();
		self::create_process_lock_time();
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
		$process_key = Options::get( $option_name );
		return $process_key !== null;
	}

	/**
	 * Clear the process key.
	 *
	 * @since 3.0.0
	 */
	private static function clear_process_key() {
		$option_name = self::$option_process_name . static::$type;
		Options::delete( $option_name );
	}

	/**
	 * Reset the locks.
	 *
	 * @since 3.0.0
	 */
	public static function reset_process_locks() {
		self::clear_process_lock_time();
		self::clear_process_key();
		self::clear_process_generation();
	}

	/**
	 * Mark the process as having exited cleanly.
	 *
	 * Call this at the end of a processing entry point (REST endpoint, manual run)
	 * so the shutdown handler knows not to release locks.
	 *
	 * @since 3.2.3
	 */
	public static function mark_clean_exit() {
		self::$clean_exit = true;
	}

	/**
	 * Register a shutdown handler to release locks on abnormal termination.
	 *
	 * When PHP is killed externally (LiteSpeed worker recycling, max_execution_time,
	 * OOM), the shutdown handler releases process locks immediately instead of
	 * waiting for lock expiry (~50s). This allows the next poll or cron to spawn
	 * a new process without delay.
	 *
	 * Uses WordPress `shutdown` action at priority 0 so it runs BEFORE other
	 * plugins (LiteSpeed, Jetpack, etc.) attempt queries on the broken MySQL
	 * connection. This is critical because other plugins' failed queries pile
	 * "Commands out of sync" errors onto the connection, making it unrecoverable.
	 * At priority 0, only the original interrupted result set needs draining.
	 *
	 * Safe because:
	 * - If killed during value collection: task is still pending, no data committed
	 * - If killed during flush: MySQL rolls back the in-progress transaction
	 * - If SIGKILL (hard kill): handler doesn't run, falls back to lock expiry
	 *
	 * @since 3.2.3
	 */
	private static function register_shutdown_handler() {
		if ( self::$shutdown_registered ) {
			return;
		}
		self::$shutdown_registered = true;

		// Capture option names now — static::$type won't resolve in the closure.
		$option_lock_name = self::$option_process_lock_time_name . static::$type;
		$option_key_name  = self::$option_process_name . static::$type;
		$option_gen_name  = self::$option_process_generation_name . static::$type;

		// Priority 0 = run before all other shutdown hooks.
		add_action(
			'shutdown',
			function () use ( $option_lock_name, $option_key_name, $option_gen_name ) {
				if ( Task_Runner::did_exit_cleanly() || ! Task_Runner::is_processing() ) {
					return;
				}

				// Detect what caused the abnormal exit.
				$error = error_get_last();
				$cause = 'unknown';
				if ( $error ) {
					$cause = $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
				}

				Util::error_log( 'Shutdown handler: abnormal exit detected — releasing locks. Cause: ' . $cause, 'error' );

				// Release locks for immediate recovery.
				// Uses resilient_bulk_delete() which tries $wpdb first, then falls
				// back to a fresh mysqli connection if $wpdb is broken.
				// Single connection + single DELETE ... WHERE name IN (...) query.
				$deleted = Options_Direct::resilient_bulk_delete(
					array(
						$option_lock_name,
						$option_key_name,
						$option_gen_name,
					)
				);

				$deleted_result = false === $deleted ? 'failed' : absint( $deleted );
				Util::error_log( 'Shutdown handler: deleted=' . $deleted_result, 'error' );
			},
			0
		);
	}

	/**
	 * Check if the process exited cleanly.
	 *
	 * Public so the shutdown handler closure can access it.
	 *
	 * @since 3.2.3
	 *
	 * @return bool
	 */
	public static function did_exit_cleanly() {
		return self::$clean_exit;
	}

	/**
	 * Check if the task runner is actively processing.
	 *
	 * Public so the shutdown handler closure can access it.
	 * Returns false after run_tasks() completes its batch,
	 * preventing the handler from firing for unrelated fatals.
	 *
	 * @since 3.3.3
	 *
	 * @return bool
	 */
	public static function is_processing() {
		return self::$is_processing;
	}

	/**
	 * Get the current generation number.
	 *
	 * Generation is used to detect stale processes. Each rebuild increments
	 * the generation, and processes check their generation matches before
	 * making state changes like setting status to 'finished'.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $force_fresh Force fresh database read bypassing cache.
	 * @return int Current generation number (defaults to 0 if not set).
	 */
	public static function get_current_generation( $force_fresh = false ) {
		$option_name = self::$option_generation_name . static::$type;

		if ( $force_fresh ) {
			wp_using_ext_object_cache( false );
			$value = Options::get_direct( $option_name );
			return absint( $value ?? 0 );
		}

		$generation = Options::get( $option_name );
		return absint( $generation ?? 0 );
	}

	/**
	 * Increment the generation counter.
	 *
	 * Called when a new rebuild is triggered to invalidate all previous
	 * generation processes. Any running process with an older generation
	 * will detect the mismatch and exit without modifying status.
	 *
	 * @since 3.2.0
	 *
	 * @return int The new generation number.
	 */
	public static function increment_generation() {
		$current        = self::get_current_generation( true );
		$new_generation = $current + 1;
		$option_name    = self::$option_generation_name . static::$type;
		Options::update( $option_name, $new_generation );
		return $new_generation;
	}

	/**
	 * Check if the given generation matches the current generation.
	 *
	 * Used by running processes to detect if they've been superseded
	 * by a new rebuild.
	 *
	 * @since 3.2.0
	 *
	 * @param int $generation The generation to check.
	 * @return bool True if generation is current.
	 */
	public static function is_current_generation( $generation ) {
		// Force fresh read from database to detect changes.
		return self::get_current_generation( true ) === absint( $generation );
	}

	/**
	 * Set the generation for the current process.
	 *
	 * Called when a process is created to record which generation it belongs to.
	 *
	 * @since 3.2.0
	 *
	 * @param int $generation The generation number.
	 */
	private static function set_process_generation( $generation ) {
		$option_name = self::$option_process_generation_name . static::$type;
		Options::update( $option_name, $generation );
	}

	/**
	 * Get the generation for the current process.
	 *
	 * @since 3.2.0
	 *
	 * @return int The process generation number.
	 */
	public static function get_process_generation() {
		$option_name = self::$option_process_generation_name . static::$type;
		$generation  = Options::get( $option_name );
		// If not set, default to current generation for backwards compatibility.
		return absint( $generation ?? self::get_current_generation() );
	}

	/**
	 * Clear the process generation option.
	 *
	 * @since 3.2.0
	 */
	private static function clear_process_generation() {
		$option_name = self::$option_process_generation_name . static::$type;
		Options::delete( $option_name );
	}

	/**
	 * Pause the indexer process.
	 *
	 * @since 3.0.0
	 */
	public static function pause_process() {
		self::set_status( 'paused' );
		self::reset_process_locks();
	}

	/**
	 * Set the status of the task runner for the task type.
	 *
	 * @since 3.0.0
	 *
	 * @param string $status    The status to set.
	 *
	 * @return bool    True if the status was updated.
	 */
	public static function set_status( $status ) {
		$option_name = self::$option_status_name . static::$type;
		$update      = Options::update( $option_name, $status );
		return $update !== false;
	}

	/**
	 * Get the status of the task runner for the task type.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $force_fresh Whether to force a fresh read from database by clearing Data_Store cache.
	 *
	 * @return array    The status of the indexer.
	 */
	public static function get_status( $force_fresh = false ) {
		$option_name = self::$option_status_name . static::$type;

		// Force fresh database read using direct query to bypass all caching layers.
		if ( $force_fresh ) {
			$status = Options::get_direct( $option_name );
		} else {
			// Use normal cached read.
			$status = Options::get( $option_name );
		}

		if ( $status === null ) {
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
		Options::delete( $option_name );
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


	/**
	 * Check if we can use background processing.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if we can use background processing.
	 */
	public static function can_use_background_processing() {
		$can_background_process = Options::get( self::$can_background_process_option_name, 'yes' );

		if ( $can_background_process === 'yes' ) {
			return true;
		}
		return false;
	}

	/**
	 * Test if we can use background processing.
	 *
	 * @since 3.0.0
	 *
	 * @return string    'yes' if we can use background processing, 'no' otherwise.
	 */
	public static function test_background_process() {
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
			'timeout'   => 10,
			'blocking'  => true,
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$rest_url = get_rest_url( null, 'search-filter-pro/v1/task-runner/endpoint' );
		$result   = wp_remote_post( $rest_url, $options );

		$can_use_background_processing = 'no';
		// Check the result for valid status code.
		$response_code = 0;
		if ( ! is_wp_error( $result ) ) {
			$response_code = wp_remote_retrieve_response_code( $result );
			if ( $response_code >= 200 && $response_code < 300 ) {
				$can_use_background_processing = 'yes';
			}
		}

		Options::update( self::$can_background_process_option_name, $can_use_background_processing );

		return $can_use_background_processing;
	}
}
