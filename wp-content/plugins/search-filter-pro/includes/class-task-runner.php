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
use Search_Filter_Pro\Task_Runner\Cron;
use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;
use Search_Filter_Pro\Task_Runner\Rest_API;
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
	protected static $batch_size = -1;

	/**
	 * Start time for the task runner.
	 *
	 * @var int
	 */
	protected static $process_start_time = 0;

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
	 * Test background processing option name.
	 *
	 * @since 3.1.2
	 *
	 * @var int
	 */
	public static $can_background_process_option_name = 'task-runner-can-background-process';
	
	/**
	 * Init the task runner.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		Rest_API::init();
		Cron::init();
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

		// Reset the error count as we got through the process ok.
		self::reset_error_count();

		// Start the timer.
		static::$process_start_time = time();

		$task_counter = 0;

		Util::raise_memory_limit();

		do {
			$task = self::get_next_task();

			// This should technically never happen, but as a safeguard lets
			// ensure we have valid task, in case something ran `run_tasks`
			// but there were no tasks in the queue.
			if ( ! $task ) {
				break;
			}

			// Process the task.
			static::run_task( $task );

			// If its marked as complete, then remove it from the queue.
			if ( $task->get_status() === 'complete' ) {
				self::complete_next_task();
			} elseif ( $task->get_status() === 'error' ) {
				Util::error_log( 'Task runner: error running task: ' . $task->get_action(), 'error' );
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

		// If we've completed all the tasks, then we can reset the process.
		if ( self::has_finished_tasks() ) {
			self::reset_process_locks();
			self::clear_tasks( array( 'status' => 'complete' ) );
			self::set_status( 'finished' );
			Util::error_log( 'Task runner: finished tasks.', 'notice' );
			do_action( 'search-filter/task_runner/finished' );
		}

		Util::clear_caches();
	}

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
	public static function create_process_lock_time() {
		$option_name = self::$option_process_lock_time_name . static::$type;
		// Try to get a new lock.
		$new_lock = time() + self::get_time_limit();
		try {
			$result = Options::create_option_value( $option_name, $new_lock );
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
		$lock        = Options::get_option_value( $option_name );

		// This should not happen as we set and clear process time in sync with the process
		// key.  But this did happen in versions <= 3.1.4
		if ( $lock === false ) {
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

			$error_count = self::increment_error_count();
			Util::error_log( __( 'Task runner: lock expired.', 'search-filter-pro' ), 'error' );

			if ( $error_count < self::$error_count_limit ) {
				// Reset the process key so we can try again.
				self::reset_process_locks();
			} else {
				// Reached the limit of errors, need to display a message to the user.
				Util::error_log( __( 'Task runner: error count limit reached.', 'search-filter-pro' ), 'error' );
			}
		}
	}

	/**
	 * Increment the error count.
	 *
	 * @since 3.0.0
	 */
	protected static function increment_error_count() {
		$error_count = self::get_error_count();
		$error_count++;
		self::set_error_count( $error_count );

		if ( $error_count >= self::$error_count_limit ) {
			self::set_status( 'error' );
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
	 * Reset the locks.
	 *
	 * @since 3.0.0
	 */
	public static function refresh_process_lock_time() {
		self::clear_process_lock_time();
		self::create_process_lock_time();
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
	protected static function get_error_count() {
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

	public static function create_process() {
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
		self::set_status( 'paused' );
		self::reset_process_locks();
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


	/**
	 * Check if we can use background processing.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if we can use background processing.
	 */
	public static function can_use_background_processing() {
		$can_background_process = Options::get_option_value( self::$can_background_process_option_name );
		if ( $can_background_process === false ) {
			// Then the test has not init yet, so lets assume yes.
			return true;
		}
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
	 * @return bool    True if we can use background processing.
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
		// Check the result for valid status code
		$response_code = 0;
		if ( ! is_wp_error( $result ) ) {
			$response_code = wp_remote_retrieve_response_code( $result );
			if ( $response_code >= 200 && $response_code < 300 ) {
				$can_use_background_processing = 'yes';
			}
		}

		Options::update_option_value( Task_Runner::$can_background_process_option_name, $can_use_background_processing );

		return $can_use_background_processing;
	}
}
