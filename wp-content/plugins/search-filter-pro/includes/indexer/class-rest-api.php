<?php
/**
 * REST API endpoints for indexer operations.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Options;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use WP_REST_Response;
use WP_Error;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the indexer dashboard widget REST API endpoints.
 *
 * @since 3.0.0
 */
class Rest_API {

	/**
	 * Init the cron class.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
	}

	/**
	 * Add rest routes.
	 *
	 * @since    3.0.0
	 */
	public static function add_routes() {

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_status' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/rebuild',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rebuild' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/resume',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'resume' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/pause',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'pause' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/start-migration',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'start_migration' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter-pro/v1',
			'/indexer/process',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'process_tasks' ),
					'args'                => array(
						'process_key' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Get the indexer status.
	 *
	 * @since    3.0.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_status() {

		self::setup_rest_request();

		$status = Indexer_Task_Runner::get_status();

		// Check if we need to set errored state.
		Indexer_Task_Runner::check_for_errors();

		// Use the regular status checks to start a new indexer process if needed.
		if ( ! Indexer_Task_Runner::has_process_key() && ! Indexer_Task_Runner::is_stopped() ) {
			// Then try to spawn a new process.
			Util::error_log( 'REST API: get_status | running new process', 'notice' );
			Indexer_Task_Runner::run_processing();
		}

		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Get the indexer data for the indexer widget.
	 *
	 * Returns structured data with task statuses, index statistics,
	 * and migration state.
	 *
	 * @since 3.0.0
	 *
	 * @return array The indexer data.
	 */
	public static function get_indexer_data() {

		self::setup_rest_request();

		// Get overall runner status.
		$runner_status = Indexer_Task_Runner::get_status();

		$is_idle = ! Indexer_Task_Runner::has_task( array( 'status' => 'pending' ) );

		// Get all index stats from unified cache (single read).
		$all_stats = Stats::get( $is_idle );

		// Build structured response.
		$indexer_data = array(
			'runnerStatus'         => $runner_status,
			'tasks'                => array(
				'rebuild'       => Indexer_Task_Runner::get_task_status( 'rebuild' ),
				'migrate'       => Indexer_Task_Runner::get_task_status( 'migrate' ),
				'rebuild_query' => Indexer_Task_Runner::get_task_status( 'rebuild_query' ),
				'rebuild_field' => Indexer_Task_Runner::get_task_status( 'rebuild_field' ),
				'sync_post'     => Indexer_Task_Runner::get_task_status( 'sync_post' ),
			),
			'currentTask'          => Indexer_Task_Runner::get_current_task(),
			'indexStats'           => $all_stats,
			'migrationStatus'      => Indexer_Task_Runner::get_migration_status(),
			'canBackgroundProcess' => Indexer_Task_Runner::can_use_background_processing(),
			'time'                 => time(),
		);

		// Add error message if in error state.
		if ( $runner_status === 'error' ) {
			$indexer_data['message'] = __( 'There has been an issue with the indexing process. Check the error log for more information.', 'search-filter-pro' );
		}

		return $indexer_data;
	}

	/**
	 * Rebuild the indexer.
	 *
	 * @since 3.0.0
	 */
	public static function rebuild() {

		self::setup_rest_request();

		Indexer_Task_Runner::reset();

		// Add the rebuild task.
		Indexer_Task_Runner::add_task(
			array(
				'action' => 'rebuild',
			)
		);

		// Only if we're doing background processing should we launch the process.
		// otherwise, lets just return the updated indexer data and wait for the next tick
		// to start the processing.
		if ( Indexer_Task_Runner::get_processing_method() === 'background' ) {
			// Use async pattern to spawn after response sent (prevents race condition).
			Indexer::async_process_queue();
		} else {
			// Set the status to processing so the next request knows to run.
			Indexer_Task_Runner::set_status( 'processing' );
		}
		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Resume the indexer if it was paused or stalled.
	 *
	 * @since 3.0.0
	 */
	public static function resume() {

		self::setup_rest_request();

		$status = Indexer_Task_Runner::get_status();

		// If we're already paused, then resume.
		if ( $status === 'paused' ) {
			// Reset the process.
			Indexer_Task_Runner::reset_process_locks();
			// Reset the error count.
			Indexer_Task_Runner::reset_error_count();
			// Set status to processing for both modes so maybe_start_process() doesn't bail on 'paused' check.
			Indexer_Task_Runner::set_status( 'processing' );

			// Then try to resume.
			if ( Indexer_Task_Runner::get_processing_method() === 'background' ) {
				// Use async pattern to spawn after response sent (prevents race condition).
				Indexer::async_process_queue();
			}
		}

		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Pause the indexer.
	 *
	 * @since 3.0.0
	 */
	public static function pause() {

		self::setup_rest_request();

		$status = Indexer_Task_Runner::get_status();

		// If we're already paused, then return early.
		if ( $status === 'paused' ) {
			return rest_ensure_response( self::get_indexer_data() );
		}

		// Then pause the process.
		Indexer_Task_Runner::pause_process();
		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Start migration from legacy index to new system.
	 *
	 * Handles the edge case where migration is incomplete but no task exists.
	 *
	 * @since 3.2.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function start_migration() {

		self::setup_rest_request();

		// Check migration status.
		$migration_status = Indexer_Task_Runner::get_migration_status();

		// Only start if stuck.
		if ( $migration_status['state'] !== 'stuck' ) {
			return new \WP_Error(
				'migration_not_stuck',
				__( 'Migration is not in a stuck state.', 'search-filter-pro' ),
				array( 'status' => 400 )
			);
		}

		// Set migration flag to 'no' if needed.
		if ( Options::get( 'indexer-migration-completed' ) !== 'no' ) {
			Options::update( 'indexer-migration-completed', 'no' );
		}

		// Add migration task.
		Indexer_Task_Runner::add_task(
			array(
				'action' => 'migrate',
				'status' => 'pending',
			)
		);

		// Start processing if using background method.
		if ( Indexer_Task_Runner::get_processing_method() === 'background' ) {
			// Use async pattern to spawn after response sent (prevents race condition).
			Indexer::async_process_queue();
		} else {
			Indexer_Task_Runner::set_status( 'processing' );
		}

		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Process the indexer tasks.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function process_tasks( \WP_REST_Request $request ) {

		self::setup_rest_request();

		// Validate rest cookie.
		$process_key = $request->get_param( 'process_key' );
		Util::error_log( 'REST API: process_tasks | ' . $process_key, 'notice' );

		if ( ! Indexer_Task_Runner::is_valid_process_key( $process_key ) ) {
			Util::error_log( 'REST API: process_tasks | invalid process key | ' . $process_key, 'notice' );
			return rest_convert_error_to_response( new \WP_Error( 'indexer_process', __( 'Invalid indexer process key.' ), array( 'status' => 403 ) ) );
		}

		// Try to run the tasks.
		Util::error_log( 'REST API: process_tasks | run tasks | ' . $process_key, 'notice' );

		// Reset the lock time as we've just started a new process via the rest API.
		Indexer_Task_Runner::refresh_process_lock_time();
		Indexer_Task_Runner::run_tasks( $process_key );

		// Chaining async requests can cause mysql to crash.
		// Add a sleep to prevent this.
		sleep( 5 );

		// Check if there are any tasks left to run and we're not stopped (paused/error/finished).
		// IMPORTANT: Force fresh read from database in case status was changed to 'paused' during run_tasks().
		if ( ! Indexer_Task_Runner::has_finished_tasks() && ! Indexer_Task_Runner::is_stopped( true ) ) {
			Util::error_log( 'REST API: process_tasks | spawning new process | ' . $process_key, 'notice' );
			Indexer_Task_Runner::run_processing( $process_key );
		}

		// Mark clean exit so shutdown handler doesn't release locks.
		Indexer_Task_Runner::mark_clean_exit();
	}

	/**
	 * Check if the user has the permissions to access the settings.
	 *
	 * @since    3.0.0
	 *
	 * @return   bool    True if the user has the permissions.
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Sets up rest requests to bypass caching
	 *
	 * Aggressively prevents caching and flushes to work around various
	 * issues with hosting configurations that prevent our indexer
	 * from building.
	 *
	 * @since @3.2.1
	 *
	 * @return void
	 */
	private static function setup_rest_request() {
		wp_using_ext_object_cache( false );
		wp_suspend_cache_addition( true );
		wp_cache_flush();
		// Don't lock up other requests while running the indexer.
		session_write_close();
	}
}
