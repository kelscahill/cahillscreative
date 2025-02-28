<?php
namespace Search_Filter_Pro\Indexer;

use Search_Filter\Options;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Indexer;
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
		wp_using_ext_object_cache( false );
		session_write_close();

		$status = Indexer::get_status();

		// Check if we need to set errored state.
		Indexer::check_for_errors();

		// Use the regular status checks to start a new indexer process if needed.
		if ( ! Indexer::has_process_key() && ! in_array( $status, Indexer::$stop_statuses, true ) ) {
			// Then try to spawn a new process.
			Indexer::run_processing();
		}

		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Get the indexer data for the indexer widget.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The indexer data.
	 */
	public static function get_indexer_data() {

		// Get task type.
		$task_type = Indexer::get_task_type();
		// Get the status.
		$status = Indexer::get_status();
		// Get the progress.
		$progress = Indexer::get_progress_data();

		$indexer_data = array(
			'status'                  => $status,
			'type'                    => $task_type,
			'message'                 => '',
			'progress'                => $progress,
			'postTypes'               => array(),
			'objectsCount'            => 0,
			'rowsCount'               => 0,
			'time'                    => time(),
			'canBackgroundProcess'    => Indexer::can_use_background_processing(),
		);

		if ( $status === 'error' ) {
			$indexer_data['message'] = __( 'There has been an issue with the indexing process.  Check the error log for more information.', 'search-filter-pro' );
			return $indexer_data;
		}

		if ( $status === 'finished' ) {
			// Only try to get this once the indexer has finished, otherwise it's not
			// displayed anyway.
			$indexer_data['objectsCount'] = Indexer::get_indexed_objects_count();
			$indexer_data['rowsCount']    = Indexer::get_indexed_rows_count();
		}

		$post_type_names = Indexer::get_indexed_post_types();
		$post_types      = array();
		foreach ( $post_type_names as $post_type_name ) {
			$post_type = get_post_type_object( $post_type_name );
			if ( $post_type ) {
				$post_types[] = $post_type->label;
			}
		}

		$indexer_data['postTypes'] = $post_types;
		$indexer_data['time']      = time();

		return $indexer_data;
	}
	/**
	 * Rebuild the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function rebuild( \WP_REST_Request $request ) {
		wp_using_ext_object_cache( false );
		session_write_close();

		Indexer::reset();
		// Add the rebuild task.
		Indexer::add_task(
			array(
				'action' => 'rebuild',
			)
		);

		// Only if we're doing background processing should we launch the process.
		// otherwise, lets just return the updated indexer data and wait for the next tick
		// to start the processing.
		if ( Indexer::get_processing_method() === 'background' ) {
			// Then run the process.
			Indexer::run_processing();
		} else {
			// Set the status to processing so the next request knows to run.
			Indexer::set_status( 'processing' );
		}
		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Resume the indexer if it was paused or stalled.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function resume( \WP_REST_Request $request ) {
		wp_using_ext_object_cache( false );
		session_write_close();

		$status = Indexer::get_status();
		// If we're already paused, then return early.
		if ( $status === 'paused' ) {
			// Reset the process.
			Indexer::reset_process_locks();
			// Then try to resume.
			Indexer::run_processing();
		}

		return rest_ensure_response( self::get_indexer_data() );
	}

	/**
	 * Pause the indexer
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function pause( \WP_REST_Request $request ) {
		wp_using_ext_object_cache( false );
		session_write_close();

		$status = Indexer::get_status();
		// If we're already paused, then return early.
		if ( $status === 'paused' ) {
			return rest_ensure_response(
				array(
					'status'   => $status,
					'progress' => Indexer::get_progress_data(),
				)
			);
		}

		// Then pause the process.
		Indexer::pause_process();
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
		wp_using_ext_object_cache( false );
		// Don't lock up other requests while running the indexer.
		session_write_close();
		Util::error_log( 'REST API: process_tasks', 'notice' );

		// Validate rest cookie.
		$process_key = $request->get_param( 'process_key' );
		if ( ! Indexer::is_valid_process_key( $process_key ) ) {
			Util::error_log( 'REST API: process_tasks | invalid process key.', 'error' );
			return rest_convert_error_to_response( new \WP_Error( 'indexer_process', __( 'Invalid indexer process key.' ), array( 'status' => 403 ) ) );
		}

		// Try to run the tasks.
		Util::error_log( 'REST API: process_tasks | run tasks', 'notice' );

		// Reset the lock time as we've just started a new process via the rest API.
		Indexer::refresh_process_lock_time();
		Indexer::run_tasks( $process_key );

		// Chaining async requests can cause mysql to crash.
		// Add a sleep to prevent this.
		$sleep_seconds = 5;
		if ( $sleep_seconds ) {
			sleep( $sleep_seconds );
		}

		// Check if there are any tasks left to run.
		if ( ! Indexer::has_finished_tasks() ) {
			Util::error_log( 'REST API: process_tasks | spawning new process', 'notice' );
			Indexer::run_processing( $process_key );
		}
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

}
