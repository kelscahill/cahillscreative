<?php

class Meow_WPMC_Rest
{
	private $core = null;
	private $admin = null;
	private $engine = null;
	private $namespace = 'media-cleaner/v1';
	private $shutdown_run_id = 0;
	private $shutdown_phase = null;
	private $shutdown_reserve = null;

	public function __construct( $core, $admin ) {
		$this->core = $core;
		$this->admin = $admin;
		$this->engine = $core->engine;
		$this->shutdown_reserve = str_repeat( 'x', 256 * 1024 );
		register_shutdown_function( array( $this, 'capture_fatal_shutdown' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		try {
			// SETTINGS
			register_rest_route( $this->namespace, '/update_options', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_update_options' )
			) );
			register_rest_route( $this->namespace, '/reset_options', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_reset_options' )
			) );
			register_rest_route( $this->namespace, '/all_settings', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_all_settings' ),
			) );

			// STATS & LISTING
			register_rest_route( $this->namespace, '/count', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_count' )
			) );
			register_rest_route( $this->namespace, '/all_ids', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_all_ids' ),
			) );
			register_rest_route( $this->namespace, '/stats', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_get_stats' ),
				'args' => array(
					'search' => array( 'required' => false ),
				)
			) );
			register_rest_route( $this->namespace, '/entries', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_entries' ),
				'args' => array(
					'limit' => array( 'required' => false, 'default' => 10 ),
					'skip' => array( 'required' => false, 'default' => 20 ),
					'filterBy' => array( 'required' => false, 'default' => 'all' ),
					'orderBy' => array( 'required' => false, 'default' => 'id' ),
					'order' => array( 'required' => false, 'default' => 'desc' ),
					'search' => array( 'required' => false ),
					'repairMode' => array( 'required' => false, 'default' => false ),
				)
			) );

			// ACTIONS
			register_rest_route( $this->namespace, '/set_ignore', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_set_ignore' )
			) );
			register_rest_route( $this->namespace, '/delete', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_delete' )
			) );
			register_rest_route( $this->namespace, '/force_trash_all', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_force_trash_all' )
			) );
			register_rest_route( $this->namespace, '/recover', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_recover' )
			) );
			register_rest_route( $this->namespace, '/reset_db', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_reset_db' )
			) );
			register_rest_route( $this->namespace, '/repair', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_repair' )
			) );

			// SCAN
			register_rest_route( $this->namespace, '/reset_issues', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_reset_issues' )
			) );
			register_rest_route( $this->namespace, '/reset_issues_and_references', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_reset_issues_and_references' )
			) );
			register_rest_route( $this->namespace, '/reset_references', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_reset_references' )
			) );
			register_rest_route( $this->namespace, '/extract_references', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_extract_references' )
			) );
			register_rest_route( $this->namespace, '/retrieve_medias', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_retrieve_medias' )
			) );
			register_rest_route( $this->namespace, '/retrieve_files', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_retrieve_files' )
			) );
			register_rest_route( $this->namespace, '/retrieve_hash_duplicates', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_retrieve_hash_duplicates' )
			) );
			register_rest_route( $this->namespace, '/check_targets', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_check_targets' )
			) );
			register_rest_route( $this->namespace, '/uploads_directory_hierarchy', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_uploads_directory_hierarchy' ),
				'args' => array(
					'force' => array( 'required' => false, 'default' => false ),
				)
			) );

			// PROGRESS
			register_rest_route( $this->namespace, '/get_progress', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_get_progress' )
			) );
				register_rest_route( $this->namespace, '/clear_progress', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_clear_progress' )
				) );
				register_rest_route( $this->namespace, '/preflight', array(
					'methods' => 'GET',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_preflight' )
				) );
				register_rest_route( $this->namespace, '/run/start', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_start' )
				) );
				register_rest_route( $this->namespace, '/run/status', array(
					'methods' => 'GET',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_status' )
				) );
				register_rest_route( $this->namespace, '/run/complete', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_complete' )
				) );
				register_rest_route( $this->namespace, '/run/fail', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_fail' )
				) );
				register_rest_route( $this->namespace, '/run/pause', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_pause' )
				) );
				register_rest_route( $this->namespace, '/run/cancel', array(
					'methods' => 'POST',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_run_cancel' )
				) );

				register_rest_route( $this->namespace, '/trash_preview', array(
					'methods' => 'GET',
					'permission_callback' => array( $this->core, 'can_access_features' ),
					'callback' => array( $this, 'rest_trash_preview' )
				) );

			// LOGS
			register_rest_route( $this->namespace, '/refresh_logs', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_refresh_logs' )
			) );
			register_rest_route( $this->namespace, '/clear_logs', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_clear_logs' )
			) );
			register_rest_route( $this->namespace, '/export', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_export' )
			) );
		} 
		catch ( Throwable $e ) {
			error_log( '[Media Cleaner] REST route registration failed: ' . $e->getMessage() );
		}
	}

	private function request_json( $request ) {
		$params = $request->get_json_params();
		return is_array( $params ) ? $params : array();
	}

	private function validate_regex_options( $options ) {
		foreach ( array( 'dirs_filter', 'files_filter' ) as $name ) {
			if ( !isset( $options[ $name ] ) || $options[ $name ] === '' ) continue;
			if ( !is_string( $options[ $name ] ) || @preg_match( $options[ $name ], '' ) === false ) {
				return new WP_Error( 'wpmc_invalid_regex', sprintf( __( 'The %s regular expression is invalid.', 'media-cleaner' ), $name ), array( 'status' => 400, 'option' => $name ) );
			}
		}
		return true;
	}

	private function request_run_id( $request ) {
		$params = $this->request_json( $request );
		$value = isset( $params['runId'] ) ? $params['runId'] : $request->get_param( 'runId' );
		return max( 0, (int) $value );
	}

	private function directory_snapshot( $relative_path ) {
		$directory = $this->core->resolve_upload_path( $relative_path, true );
		if ( is_wp_error( $directory ) ) return $directory;
		$stat = @lstat( $directory );
		if ( !$stat || !is_dir( $directory ) || is_link( $directory ) ) {
			return new WP_Error( 'wpmc_directory_snapshot_failed', __( 'A filesystem directory became unavailable or unsafe during the scan.', 'media-cleaner' ) );
		}
		return hash( 'sha256', wp_json_encode( array(
			'dev' => isset( $stat['dev'] ) ? (int) $stat['dev'] : 0,
			'ino' => isset( $stat['ino'] ) ? (int) $stat['ino'] : 0,
			'mtime' => isset( $stat['mtime'] ) ? (int) $stat['mtime'] : 0,
			'ctime' => isset( $stat['ctime'] ) ? (int) $stat['ctime'] : 0,
		) ) );
	}

	private function activate_request_run( $request ) {
		$run_id = $this->request_run_id( $request );
		if ( $run_id < 1 ) {
			return new WP_Error( 'wpmc_run_required', __( 'A valid scan run is required for this request.', 'media-cleaner' ), array( 'status' => 400 ) );
		}
		$run = $this->core->set_run_context( $run_id );
		if ( !is_wp_error( $run ) ) {
			$this->shutdown_run_id = (int) $run->id;
			$this->shutdown_phase = sanitize_key( basename( (string) $request->get_route() ) );
		}
		return $run;
	}

	public function capture_fatal_shutdown() {
		$this->shutdown_reserve = null;
		if ( $this->shutdown_run_id < 1 || !$this->core->runs ) return;
		$error = error_get_last();
		$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR );
		if ( !$error || !in_array( $error['type'], $fatal_types, true ) ) return;
		$run = $this->core->runs->get( $this->shutdown_run_id );
		if ( !$run || !in_array( $run->status, array( 'running', 'paused' ), true ) ) return;
		$message = isset( $error['message'] ) ? $error['message'] : __( 'The PHP worker stopped unexpectedly.', 'media-cleaner' );
		$details = array(
			'phase' => $this->shutdown_phase,
			'file' => isset( $error['file'] ) ? $error['file'] : null,
			'line' => isset( $error['line'] ) ? (int) $error['line'] : null,
			'memory' => memory_get_peak_usage( true ),
		);
		if ( preg_match( '/maximum execution time|allowed memory size|out of memory/i', $message ) ) {
			$this->core->runs->pause( $this->shutdown_run_id, 'wpmc_resource_exhausted', $message, $details );
		}
		else {
			$this->core->runs->fail( $this->shutdown_run_id, 'wpmc_fatal_error', $message, $details );
		}
	}

	private function transient_error_details( $error ) {
		if ( $error instanceof Meow_WPMC_Transient_Exception ) {
			return array( 'retryable' => true, 'retry_after_ms' => $error->get_retry_after_ms() );
		}
		if ( $error instanceof WP_Error ) {
			$data = $error->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
			if ( is_array( $data ) && !empty( $data['retryable'] ) ) {
				return array( 'retryable' => true, 'retry_after_ms' => isset( $data['retry_after_ms'] ) ? (int) $data['retry_after_ms'] : 2000 );
			}
			if ( in_array( $status, array( 408, 429, 502, 503, 504 ), true ) ) {
				return array( 'retryable' => true, 'retry_after_ms' => isset( $data['retry_after_ms'] ) ? (int) $data['retry_after_ms'] : 2000 );
			}
		}
		$message = $error instanceof WP_Error
			? $error->get_error_message()
			: ( $error instanceof Throwable ? $error->getMessage() : (string) $error );
		if ( preg_match( '/too many connections|server has gone away|lost connection|connection (?:refused|reset)|deadlock|lock wait timeout|resource temporarily unavailable|temporarily unavailable/i', $message ) ) {
			return array( 'retryable' => true, 'retry_after_ms' => 5000 );
		}
		return array( 'retryable' => false, 'retry_after_ms' => 0 );
	}

	private function error_response( $error, $run_id = 0, $phase = null, $commit_state = 'not_committed' ) {
		if ( !$error instanceof WP_Error ) {
			$error = new WP_Error( 'wpmc_unknown_error', $error instanceof Throwable ? $error->getMessage() : (string) $error );
		}
		$data = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;
		$code = $error->get_error_code();
		$message = $error->get_error_message();
		$transient = $this->transient_error_details( $error );
		$retry_after_ms = is_array( $data ) && isset( $data['retry_after_ms'] )
			? max( 250, min( 60000, (int) $data['retry_after_ms'] ) )
			: max( 0, (int) $transient['retry_after_ms'] );
		$retryable_commit = in_array( $commit_state, array( 'not_committed', 'safe_to_retry' ), true );
		$retryable_status = in_array( $status, array( 408, 429, 502, 503, 504 ), true );
		$response = new WP_REST_Response( array(
			'success' => false,
			'error' => array(
				'request_id' => wp_generate_uuid4(),
				'run_id' => (int) $run_id,
				'phase' => $phase,
				'code' => $code,
				'retryable' => $retryable_commit && ( $retryable_status || !empty( $transient['retryable'] ) ),
				'retry_after_ms' => $retry_after_ms,
				'commit_state' => $commit_state,
				'message' => $message,
				'details' => is_array( $data ) ? $data : array(),
			),
			'message' => $message,
		), $status );
		if ( $retry_after_ms > 0 ) {
			$response->header( 'Retry-After', (string) max( 1, (int) ceil( $retry_after_ms / 1000 ) ) );
		}
		return $response;
	}

	private function fail_run_response( $throwable, $run_id, $phase ) {
		$code = $throwable instanceof WP_Error ? $throwable->get_error_code() : 'wpmc_' . sanitize_key( $phase ) . '_failed';
		$message = $throwable instanceof WP_Error ? $throwable->get_error_message() : $throwable->getMessage();
		$transient = $this->transient_error_details( $throwable );
		if ( !empty( $transient['retryable'] ) ) {
			if ( $run_id > 0 && $this->core->runs ) {
				$paused = $this->core->runs->pause( $run_id, $code, $message, array( 'phase' => $phase, 'retry_after_ms' => $transient['retry_after_ms'] ) );
				if ( is_wp_error( $paused ) ) {
					return $this->error_response( $paused, $run_id, $phase, 'unknown' );
				}
				if ( !$paused ) {
					return $this->error_response( new WP_Error(
						'wpmc_run_state_changed',
						__( 'The scan state changed while Media Cleaner was handling a temporary server error.', 'media-cleaner' ),
						array( 'status' => 409 )
					), $run_id, $phase, 'unknown' );
				}
			}
			$error = new WP_Error( $code, $message, array(
				'status' => 503,
				'retryable' => true,
				'retry_after_ms' => $transient['retry_after_ms'],
			) );
			return $this->error_response( $error, $run_id, $phase, 'safe_to_retry' );
		}
		if ( $run_id > 0 && $this->core->runs ) {
			$failed = $this->core->runs->fail( $run_id, $code, $message, array( 'phase' => $phase ) );
			if ( is_wp_error( $failed ) ) {
				return $this->error_response( $failed, $run_id, $phase, 'unknown' );
			}
			if ( !$failed ) {
				return $this->error_response( new WP_Error(
					'wpmc_run_state_changed',
					__( 'The scan state changed while Media Cleaner was recording an error.', 'media-cleaner' ),
					array( 'status' => 409 )
				), $run_id, $phase, 'unknown' );
			}
		}
		$error = $throwable instanceof WP_Error ? $throwable : new WP_Error( $code, $message, array( 'status' => 500 ) );
		return $this->error_response( $error, $run_id, $phase, 'staged_run_failed' );
	}

	function rest_run_start( $request ) {
		$params = $this->request_json( $request );
		$method = isset( $params['method'] ) ? sanitize_key( $params['method'] ) : $this->core->get_option( 'method' );
		$config = isset( $params['config'] ) && is_array( $params['config'] ) ? $params['config'] : array();
		$valid_regex = $this->validate_regex_options( array_merge( $this->core->get_all_options(), $config ) );
		if ( is_wp_error( $valid_regex ) ) return $this->error_response( $valid_regex );
		$config = $this->core->sanitize_scan_config( $config );
		$uploads = wp_upload_dir();
		$basedir = empty( $uploads['error'] ) && !empty( $uploads['basedir'] ) ? $uploads['basedir'] : null;
		if ( !$basedir || !is_dir( $basedir ) || !is_readable( $basedir ) || !is_writable( $basedir ) ) {
			return $this->error_response( new WP_Error(
				'wpmc_storage_unavailable',
				__( 'Uploads storage must be readable and writable before a safe scan can start.', 'media-cleaner' ),
				array( 'status' => 412, 'storage_error' => isset( $uploads['error'] ) ? $uploads['error'] : null )
			) );
		}
		$private_storage = $this->core->prepare_private_storage();
		if ( is_wp_error( $private_storage ) ) {
			$private_storage->add_data( array( 'status' => 412 ) );
			return $this->error_response( $private_storage );
		}
		$roundtrip = $this->core->test_quarantine_roundtrip();
		if ( is_wp_error( $roundtrip ) ) {
			$roundtrip->add_data( array( 'status' => 412 ) );
			return $this->error_response( $roundtrip );
		}
		$needs_dom = ( $method === 'media' && !empty( $config['content'] ) ) || ( $method === 'files' && !empty( $config['filesystem_content'] ) );
		if ( $needs_dom && !class_exists( 'DOMDocument' ) ) {
			return $this->error_response( new WP_Error(
				'wpmc_dom_unavailable',
				__( 'The PHP DOM extension is required for the selected content analysis.', 'media-cleaner' ),
				array( 'status' => 412 )
			) );
		}
		$request_key = isset( $params['requestKey'] ) ? sanitize_text_field( $params['requestKey'] ) : '';
		$run = $this->core->runs->start( $method, $config, $request_key );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		$this->core->set_run_context( $run->id );
		return new WP_REST_Response( array(
			'success' => true,
			'data' => array(
				'run' => $this->core->runs->to_array( $run ),
				'cleanup_allowed' => false,
			),
		), 201 );
	}

	function rest_run_status( $request ) {
		$run_id = $this->request_run_id( $request );
		$run = $run_id > 0 ? $this->core->runs->get( $run_id ) : $this->core->runs->get_resumable();
		if ( !$run && $run_id > 0 ) {
			return $this->error_response( new WP_Error( 'wpmc_run_not_found', __( 'This scan run was not found.', 'media-cleaner' ), array( 'status' => 404 ) ), $run_id );
		}
		return new WP_REST_Response( array(
			'success' => true,
			'data' => array(
				'run' => $this->core->runs->to_array( $run ),
				'active_run_id' => $this->core->runs->get_active_id(),
				'cleanup_allowed' => $this->core->runs->cleanup_allowed(),
			),
		), 200 );
	}

	function rest_run_complete( $request ) {
		$run_id = $this->request_run_id( $request );
		$run = $this->core->runs->complete( $run_id );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run, $run_id, 'complete' );
		}
		$this->core->clear_run_context();
		// Asked rather than assumed: a scan started by an older version can still be
		// resumed and published here, and its results are not the current one's.
		return new WP_REST_Response( array( 'success' => true, 'data' => array(
			'run' => $this->core->runs->to_array( $run ),
			'cleanup_allowed' => $this->core->runs->cleanup_allowed(),
		) ), 200 );
	}

	function rest_run_fail( $request ) {
		$params = $this->request_json( $request );
		$run_id = $this->request_run_id( $request );
		$code = isset( $params['code'] ) ? sanitize_key( $params['code'] ) : 'client_failure';
		$message = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : __( 'The scan stopped after an error.', 'media-cleaner' );
		$failed = $this->core->runs->fail( $run_id, $code, $message );
		if ( is_wp_error( $failed ) ) {
			return $this->error_response( $failed, $run_id, 'fail', 'unknown' );
		}
		if ( !$failed ) {
			return $this->error_response( new WP_Error(
				'wpmc_run_not_failed',
				__( 'The scan could not be marked as failed because its state changed.', 'media-cleaner' ),
				array( 'status' => 409 )
			), $run_id, 'fail' );
		}
		return new WP_REST_Response( array( 'success' => true, 'data' => array( 'run' => $this->core->runs->to_array( $this->core->runs->get( $run_id ) ) ) ), 200 );
	}

	function rest_run_pause( $request ) {
		$params = $this->request_json( $request );
		$run_id = $this->request_run_id( $request );
		$code = isset( $params['code'] ) ? sanitize_key( $params['code'] ) : 'client_pause';
		$message = isset( $params['message'] )
			? sanitize_text_field( $params['message'] )
			: __( 'The scan was paused by the user.', 'media-cleaner' );
		$paused = $this->core->runs->pause( $run_id, $code, $message, array( 'source' => 'client' ) );
		if ( is_wp_error( $paused ) ) {
			return $this->error_response( $paused, $run_id, 'pause', 'unknown' );
		}
		if ( !$paused ) {
			return $this->error_response( new WP_Error(
				'wpmc_run_not_paused',
				__( 'The scan could not be paused because it is no longer running.', 'media-cleaner' ),
				array( 'status' => 409 )
			), $run_id, 'pause' );
		}
		return new WP_REST_Response( array(
			'success' => true,
			'data' => array( 'run' => $this->core->runs->to_array( $this->core->runs->get( $run_id ) ) ),
		), 200 );
	}

	function rest_run_cancel( $request ) {
		$params = $this->request_json( $request );
		$run_id = $this->request_run_id( $request );
		$discard = isset( $params['discard'] ) && rest_sanitize_boolean( $params['discard'] );
		$result = $discard ? $this->core->runs->discard( $run_id ) : $this->core->runs->cancel( $run_id );
		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result, $run_id, $discard ? 'discard' : 'cancel' );
		}
		if ( !$result ) {
			return $this->error_response( new WP_Error( 'wpmc_run_not_cancelled', __( 'The scan could not be cancelled because it is no longer running.', 'media-cleaner' ), array( 'status' => 409 ) ), $run_id );
		}
		return new WP_REST_Response( array( 'success' => true, 'data' => array( 'run' => $this->core->runs->to_array( $this->core->runs->get( $run_id ) ) ) ), 200 );
	}

	function rest_preflight() {
		$checks = array();
		$blocked = false;
		$constrained = false;

		$add_check = function( $id, $status, $message, $details = array() ) use ( &$checks, &$blocked, &$constrained ) {
			$checks[] = array( 'id' => $id, 'status' => $status, 'message' => $message, 'details' => $details );
			$blocked = $blocked || $status === 'blocked';
			$constrained = $constrained || $status === 'constrained';
		};

		$schema_ok = $this->core->runs && $this->core->runs->maybe_upgrade();
		$add_check( 'database', $schema_ok ? 'ready' : 'blocked', $schema_ok ? __( 'Database tables are ready.', 'media-cleaner' ) : __( 'Database tables could not be created or upgraded.', 'media-cleaner' ) );
		if ( $schema_ok ) {
			$gc_ok = $this->core->runs->garbage_collect( 500 );
			$add_check( 'database_retention', $gc_ok ? 'ready' : 'constrained', $gc_ok ? __( 'Old scan data is within the bounded retention process.', 'media-cleaner' ) : __( 'Old scan data could not be pruned during this request.', 'media-cleaner' ) );
			$resumable = $this->core->runs->get_resumable();
			$add_check( 'scan_lock', $resumable ? 'blocked' : 'ready', $resumable ? __( 'A staged scan already exists. Resume or stop it before starting another scan.', 'media-cleaner' ) : __( 'No competing scan is running.', 'media-cleaner' ), $resumable ? array( 'run_id' => (int) $resumable->id ) : array() );
		}

		$uploads = wp_upload_dir();
		$upload_error = isset( $uploads['error'] ) ? $uploads['error'] : null;
		$basedir = isset( $uploads['basedir'] ) ? wp_normalize_path( $uploads['basedir'] ) : '';
		$upload_ready = !$upload_error && $basedir && is_dir( $basedir ) && is_readable( $basedir ) && is_writable( $basedir );
		$add_check( 'storage', $upload_ready ? 'ready' : 'blocked', $upload_ready ? __( 'Uploads storage is readable and writable.', 'media-cleaner' ) : __( 'Uploads storage is unavailable or not writable.', 'media-cleaner' ), array( 'error' => $upload_error ) );

		if ( $upload_ready ) {
			$source = tempnam( $basedir, '.wpmc-' );
			$destination = $source ? $source . '.moved' : null;
			$storage_ops = $source && file_put_contents( $source, 'wpmc' ) === 4 && @rename( $source, $destination ) && @unlink( $destination );
			if ( $source && file_exists( $source ) ) {
				@unlink( $source );
			}
			if ( $destination && file_exists( $destination ) ) {
				@unlink( $destination );
			}
			$add_check( 'storage_operations', $storage_ops ? 'ready' : 'blocked', $storage_ops ? __( 'Storage move and delete operations work.', 'media-cleaner' ) : __( 'Storage cannot reliably move and delete files.', 'media-cleaner' ) );
		}

		$private_storage = $this->core->prepare_private_storage();
		$private_ready = !is_wp_error( $private_storage );
		$add_check(
			'private_storage',
			$private_ready ? 'ready' : 'blocked',
			$private_ready ? __( 'Private quarantine storage is ready.', 'media-cleaner' ) : $private_storage->get_error_message(),
			$private_ready ? array() : array( 'code' => $private_storage->get_error_code() )
		);
		if ( $private_ready ) {
			$roundtrip = $this->core->test_quarantine_roundtrip();
			$roundtrip_ready = !is_wp_error( $roundtrip );
			$add_check( 'quarantine_roundtrip', $roundtrip_ready ? 'ready' : 'blocked', $roundtrip_ready ? __( 'Quarantine move and recovery operations work.', 'media-cleaner' ) : $roundtrip->get_error_message() );
		}

		$memory_bytes = $this->core->parse_ini_bytes( ini_get( 'memory_limit' ) );
		$memory_status = $memory_bytes < 0 || $memory_bytes >= 128 * 1024 * 1024 ? 'ready' : 'constrained';
		$add_check( 'memory', $memory_status, sprintf( __( 'PHP memory limit: %s.', 'media-cleaner' ), ini_get( 'memory_limit' ) ), array( 'bytes' => $memory_bytes ) );

		$execution_time = (int) ini_get( 'max_execution_time' );
		$request_budget = $this->core->get_request_time_budget();
		$time_status = $request_budget >= 10 ? 'ready' : 'constrained';
		$add_check( 'execution_time', $time_status, sprintf( __( 'PHP execution limit: %d seconds; Media Cleaner work budget: %.1f seconds.', 'media-cleaner' ), $execution_time, $request_budget ), array( 'seconds' => $execution_time, 'work_budget_seconds' => $request_budget ) );

		global $wpdb;
		$db_started = microtime( true );
		$db_probe = $wpdb->get_var( 'SELECT 1' );
		$db_latency_ms = (int) round( ( microtime( true ) - $db_started ) * 1000 );
		$db_ready = (int) $db_probe === 1 && empty( $wpdb->last_error );
		$db_status = !$db_ready ? 'blocked' : ( $db_latency_ms > 250 ? 'constrained' : 'ready' );
		$add_check(
			'database_connection',
			$db_status,
			$db_ready ? sprintf( __( 'Database round trip: %d ms.', 'media-cleaner' ), $db_latency_ms ) : __( 'The database connection did not answer a health check.', 'media-cleaner' ),
			array( 'latency_ms' => $db_latency_ms, 'error' => $wpdb->last_error )
		);

		$dom_ready = class_exists( 'DOMDocument' );
		$method = $this->core->get_option( 'method' );
		$needs_dom = ( $method === 'media' && $this->core->get_option( 'content' ) ) || ( $method === 'files' && $this->core->get_option( 'filesystem_content' ) );
		$dom_status = $dom_ready ? 'ready' : ( $needs_dom ? 'blocked' : 'constrained' );
		$add_check( 'dom', $dom_status, $dom_ready ? __( 'The PHP DOM extension is available.', 'media-cleaner' ) : __( 'The PHP DOM extension is unavailable; content analysis cannot run.', 'media-cleaner' ) );

		$disk_free = $basedir && function_exists( 'disk_free_space' ) ? @disk_free_space( $basedir ) : false;
		$disk_status = $disk_free === false || $disk_free >= 256 * 1024 * 1024 ? 'ready' : 'constrained';
		$add_check( 'disk', $disk_status, $disk_free === false ? __( 'Free disk space could not be measured.', 'media-cleaner' ) : sprintf( __( 'Free upload storage: %s.', 'media-cleaner' ), size_format( $disk_free ) ), array( 'bytes' => $disk_free ) );

		$status = $blocked ? 'blocked' : ( $constrained ? 'constrained' : 'ready' );
		$severely_constrained = $request_budget < 10 || ( $memory_bytes > 0 && $memory_bytes < 96 * 1024 * 1024 ) || $db_latency_ms > 750;
		$profile_constrained = $status === 'constrained';
		$base_delay_ms = $severely_constrained ? 1000 : ( $profile_constrained || $db_latency_ms > 250 ? 500 : 150 );
		return new WP_REST_Response( array(
			'success' => true,
			'data' => array(
				'status' => $status,
				'checks' => $checks,
				'profile' => array(
					'posts_buffer' => $severely_constrained ? 1 : ( $profile_constrained ? 2 : 5 ),
					'medias_buffer' => $severely_constrained ? 10 : ( $profile_constrained ? 25 : 100 ),
					'analysis_buffer' => $severely_constrained ? 10 : ( $profile_constrained ? 25 : 100 ),
					'file_buffer' => $severely_constrained ? 25 : ( $profile_constrained ? 100 : 500 ),
					'cleanup_buffer' => $severely_constrained ? 5 : ( $profile_constrained ? 10 : 20 ),
					'base_delay_ms' => $base_delay_ms,
					'max_retries' => 4,
					'request_timeout_ms' => max( 30000, min( 90000, (int) ceil( ( $request_budget + 15 ) * 1000 ) ) ),
				),
				'cleanup_allowed' => $this->core->runs->cleanup_allowed(),
			),
		), 200 );
	}

	/**
	 * Validates certain option values
	 * @param string $option Option name
	 * @param mixed $value Option value
	 * @return mixed|WP_Error Validated value if no problem
	 */
	function validate_option( $option, $value ) {
		switch ( $option ) {
		case 'wpmc_dirs_filter':
		case 'wpmc_files_filter':
		if ( $value && @preg_match( $value, '' ) === false ) return new WP_Error( 'invalid_option', __( "Invalid Regular-Expression", 'media-cleaner' ) );
		break;
		}
		return $value;
	}

	function rest_reset_issues( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) return $this->error_response( $run );
		try {
			$this->core->reset_issues();
			$this->core->save_progress( 'resetIssues' );
			return new WP_REST_Response( [ 'success' => true, 'message' => __( 'Issues were reset.', 'media-cleaner' ) ], 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'resetIssues' );
		}
	}

	function rest_reset_issues_and_references( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) return $this->error_response( $run );
		try {
			$this->core->reset_issues();
			$this->core->reset_references();
			$this->core->save_progress( 'resetIssuesAndReferences' );
			return new WP_REST_Response( [ 'success' => true, 'message' => __( 'Issues and References were reset.', 'media-cleaner' ) ], 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'resetIssuesAndReferences' );
		}
	}

	function rest_reset_references( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) return $this->error_response( $run );
		try {
			$this->core->reset_references();
			$this->core->save_progress( 'resetReferences' );
			return new WP_REST_Response( [ 'success' => true, 'message' => __( 'References were reset.', 'media-cleaner' ) ], 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'resetReferences' );
		}
	}

	function rest_count( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		$params = $request->get_json_params();
		$src = isset( $params['source'] ) ? $params['source'] : null;
		$num = 0;
		if ( $src === 'posts' ) {
			$num = $this->engine->count_posts_to_check();
		}
		else if ( $src === 'medias' ) {
			$num = $this->engine->count_media_entries( $this->core->get_option( 'attach_is_use' ) );
		}
		else {
			return $this->fail_run_response( new WP_Error(
				'wpmc_count_source_invalid',
				__( 'No valid source was provided for the scan count.', 'media-cleaner' ),
				array( 'status' => 400 )
			), $run->id, 'count' );
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $num ], 200 );
	}

	function rest_all_ids( $request ) {
		$params = $this->request_json( $request );
		$src = isset( $params['source'] ) ? $params['source'] : null;
		$search = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : null;
		$repair_mode = isset( $params['repairMode'] ) ? rest_sanitize_boolean( $params['repairMode'] ) : false;
		$cursor = isset( $params['cursor'] ) ? absint( $params['cursor'] ) : 0;
		$limit = isset( $params['limit'] ) ? absint( $params['limit'] ) : 100;
		$limit = max( 1, min( 100, $limit ) );
		$ids = [];
		if ( $src === 'issues' ) {
			$ids = $repair_mode ? $this->core->get_repair_ids( $search, $cursor, $limit ) : $this->get_issues_ids( $search, $cursor, $limit );
		}
		else if ( $src === 'ignored' ) {
			$ids = $this->get_ignored_ids( $search, $cursor, $limit );
		}
		else if ( $src === 'trash' ) {
			$ids = $this->get_trash_ids( $search, $cursor, $limit );
		}
		else {
			return $this->error_response( new WP_Error(
				'wpmc_id_source_invalid',
				__( 'No valid source was provided for the requested IDs.', 'media-cleaner' ),
				array( 'status' => 400 )
			) );
		}
		$next_cursor = empty( $ids ) ? $cursor : (int) end( $ids );
		return new WP_REST_Response( array(
			'success' => true,
			'data' => array_map( 'intval', $ids ),
			'pagination' => array(
				'cursor' => $next_cursor,
				'finished' => count( $ids ) < $limit,
			),
		), 200 );
	}

	function verify_token() {
		 // Check if token needs refresh
		$current_nonce = $this->core->get_nonce( true );
		$request_nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : null;
		
		$should_refresh = false;
		if ( $request_nonce ) {
			$verify = wp_verify_nonce( $request_nonce, 'wp_rest' );
			if ( $verify === 2 ) {
				// Nonce is valid but was generated 12-24 hours ago
				$should_refresh = true;
			}
		}
		
		if ( $should_refresh || ( $request_nonce && $current_nonce !== $request_nonce ) ) {
			return $current_nonce;
		}

		return false;
	}

	function rest_extract_references( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		try {

		//DEBUG: Simulate a service unavailable error
		// $error_chance = rand( 0, 4 ) === 0; // 25% chance to simulate an error
		// if ( $error_chance ) {
	    // 	return new WP_REST_Response( [ 'success' => false, 'message' => 'Test Service Unavailable!' ], 503 );
		// }

		$params = $request->get_json_params();
		$limit = isset( $params['limit'] ) ? max( 0, (int) $params['limit'] ) : 0;
		$source = isset( $params['source'] ) ? $params['source'] : null;
		$post_id = isset( $params['postId'] ) ? $params['postId'] : null;
		$limitsize = $this->core->get_option( 'posts_buffer' );
		$finished = false;
		$processed = 0;
		$message = ""; // will be filled by extractRefsFrom...

		// Randomly throw an exception timeout
		// if ( rand( 0, 1 ) !== 1 ) {
		// 	//throw a 408 error
		// 	$this->core->deepsleep(10); header("HTTP/1.0 408 Request Timeout"); exit;
		// }

		if ( $post_id !== null && ( !is_numeric( $post_id ) || !is_int( (int) $post_id ) ) ) {
			return $this->fail_run_response( new WP_Error(
				'wpmc_post_id_invalid',
				__( 'The postId parameter must be null or an integer.', 'media-cleaner' ),
				array( 'status' => 400 )
			), $run->id, 'extractReferences' );
		}

		if ( $source === 'content' ) {
			$finished = $this->engine->extractRefsFromContent( $limit, $limitsize, $message, $post_id, $processed );
		}
		else if ( $source === 'media' ) {
			$finished = $this->engine->extractRefsFromLibrary( $limit, $limitsize, $message, $post_id, $processed );
		}else if ( $source === 'duplicates' ) {
			$finished = $this->engine->extractRefsFromDuplicates( $limit, $limitsize, $message, $post_id, $processed );
		} else if( $source === 'thumbnails' ) {
			$finished = $this->engine->extractRefsFromThumbnails( $limit, $limitsize, $message, $post_id, $processed );
		}
		else {
			return $this->fail_run_response( new WP_Error(
				'wpmc_reference_source_invalid',
				__( 'No valid source was provided for reference extraction.', 'media-cleaner' ),
				array( 'status' => 400 )
			), $run->id, 'extractReferences' );
		}

		$this->core->clean_ob();

		$response = [ 
			'success' => true, 
			'message' => $message,
			'data' => [
				'limit' => $limit + $processed,
				'finished' => $finished,
				'checked' => $processed,
				'yielded' => !$finished && $processed < $limitsize,
			]
		];

		$new_token = $this->verify_token();
		if( $new_token ) {
			$response['new_token'] = $new_token;
		}

			return new WP_REST_Response( $response, 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'extractReferences' );
		}
	}

	function rest_retrieve_hash_duplicates( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		try {

		$params = $this->request_json( $request );
		$offset = isset( $params['offset'] ) ? max( 0, (int) $params['offset'] ) : 0;
		$limit = min( 500, max( 1, (int) $this->core->get_option( 'analysis_buffer' ) ) );
		$hashes = $this->engine->get_hash_duplicates( $offset, $limit );
		$finished = count( $hashes ) < $limit;
		$processed = 0;
		$yielded = false;
		$this->core->timeout_check_start( count( $hashes ) );
		foreach ( $hashes as $hash ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();
			$this->engine->check_duplicates( $hash );
			$this->core->timeout_check_additem();
			$processed++;
		}
		$finished = !$yielded && $processed === count( $hashes ) && $finished;

		$response = [ 
			'success' => true, 
			'message' => sprintf( __( "Retrieved %d hash duplicates.", 'media-cleaner' ), $processed ),
			'data' => [
				'results' => array(),
				'checked' => $processed,
				'offset' => $offset + $processed,
				'finished' => $finished,
				'yielded' => $yielded,
			],
		];

		$this->core->save_progress( $finished ? 'retrieveDuplicates_finished' : 'retrieveDuplicates', array(
			'type' => 'duplicates',
			'groups' => $processed,
			'offset' => $offset,
			'next' => $offset + $processed,
		) );
		$new_token = $this->verify_token();
		if ( $new_token ) {
			$response['new_token'] = $new_token;
		}

			return new WP_REST_Response( $response, 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'retrieveDuplicates' );
		}
	}

	function rest_save_progress( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		$params = $request->get_json_params();

		$save = isset( $params['data'] ) ? $params['data'] : null;
		$step = isset( $params['step'] ) ? $params['step'] : null;

		if( !is_array( $save ) || !$step ) {
			return $this->fail_run_response( new WP_Error(
				'wpmc_progress_invalid',
				__( 'Invalid parameters were provided for saving scan progress.', 'media-cleaner' ),
				array( 'status' => 400 )
			), $run->id, 'saveProgress' );
		}

		$this->core->save_progress( $step, $save );

		$response = [ 
			'success' => true, 
			'message' => __( 'Progress saved successfully.', 'media-cleaner' ),
		];

		$new_token = $this->verify_token();
		if( $new_token ) {
			$response['new_token'] = $new_token;
		}

		return new WP_REST_Response( $response, 200 );
	}

	function rest_retrieve_files( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		$work = null;
		try {
			$params = $this->request_json( $request );
			if ( !empty( $params['initialize'] ) ) {
				$root = isset( $params['root'] ) ? $this->core->normalize_upload_relative_path( $params['root'] ) : '';
				if ( is_wp_error( $root ) ) return $this->fail_run_response( $root, $run->id, 'retrieveFiles' );
				$resolved_root = $this->core->resolve_upload_path( $root, true );
				if ( is_wp_error( $resolved_root ) || !is_dir( $resolved_root ) ) {
					$error = is_wp_error( $resolved_root ) ? $resolved_root : new WP_Error( 'wpmc_not_directory', __( 'The selected uploads path is not a directory.', 'media-cleaner' ), array( 'status' => 400 ) );
					return $this->fail_run_response( $error, $run->id, 'retrieveFiles' );
				}
				if ( !$this->core->runs->enqueue_work( $run->id, 'retrieveFiles', 'directory', $root ) ) {
					throw new RuntimeException( __( 'Media Cleaner could not queue the uploads directory.', 'media-cleaner' ) );
				}
			}

			$work = $this->core->runs->next_work( $run->id, 'retrieveFiles' );
			if ( !$work ) {
				$this->core->save_progress( 'retrieveFiles_finished', array( 'pending_directories' => 0 ) );
				return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Filesystem discovery completed.', 'media-cleaner' ), 'data' => array( 'results' => array(), 'finished' => true, 'pending_directories' => 0 ) ), 200 );
			}

			$snapshot = $this->directory_snapshot( $work->target_key );
			if ( is_wp_error( $snapshot ) ) throw new RuntimeException( $snapshot->get_error_message() );
			if ( !empty( $work->snapshot_token ) && !hash_equals( (string) $work->snapshot_token, $snapshot ) ) {
				throw new RuntimeException( __( 'An uploads directory changed while it was being paged. Start a new scan so no files are skipped.', 'media-cleaner' ) );
			}
			if ( empty( $work->snapshot_token ) && !$this->core->runs->set_work_snapshot( $work->id, $snapshot ) ) {
				throw new RuntimeException( __( 'Media Cleaner could not checkpoint the uploads directory.', 'media-cleaner' ) );
			}
			if ( !$this->core->runs->update_work( $work->id, 'running', $work->cursor_value ) ) {
				throw new RuntimeException( __( 'Media Cleaner could not lease the uploads directory batch.', 'media-cleaner' ) );
			}
			$limitsize = $this->core->get_option( 'uploads_file_buffer' );
			$entries = $this->engine->get_files( $work->target_key, (int) $work->cursor_value, $limitsize );
			$page_info = $this->engine->get_file_page_info( count( $entries ), $limitsize );
			$scanned_count = isset( $page_info['scanned'] ) ? (int) $page_info['scanned'] : count( $entries );
			$directory_finished = !empty( $page_info['finished'] );
			$has_files = !empty( array_filter( $entries, function( $entry ) { return isset( $entry['type'] ) && $entry['type'] === 'file'; } ) );
			if ( $has_files ) {
				$this->core->safe_do_action( 'wpmc_check_file_init' );
			}
			$this->core->timeout_check_start( count( $entries ) );
			$processed = 0;
			$checked = 0;
			$yielded = false;
			$next_cursor = (int) $work->cursor_value;
			foreach ( $entries as $entry ) {
				if ( $this->core->timeout_should_yield() ) {
					$yielded = true;
					break;
				}
				$this->core->timeout_check();
				if ( $entry['type'] === 'dir' ) {
					if ( !$this->core->runs->enqueue_work( $run->id, 'retrieveFiles', 'directory', $entry['path'] ) ) {
						throw new RuntimeException( __( 'Media Cleaner could not queue an uploads subdirectory.', 'media-cleaner' ) );
					}
				}
				else if ( $entry['type'] === 'file' ) {
					$this->engine->check_file( $entry['path'] );
					$checked++;
				}
				$processed++;
				$next_cursor = isset( $entry['cursor'] ) ? max( $next_cursor, (int) $entry['cursor'] ) : $next_cursor + 1;
				$this->core->timeout_check_additem();
				if ( $processed % 10 === 0 && !$this->core->runs->update_work( $work->id, 'running', $next_cursor ) ) {
					throw new RuntimeException( __( 'Media Cleaner could not checkpoint a filesystem sub-batch.', 'media-cleaner' ) );
				}
			}
			$snapshot_after = $this->directory_snapshot( $work->target_key );
			if ( is_wp_error( $snapshot_after ) || !hash_equals( $snapshot, (string) $snapshot_after ) ) {
				throw new RuntimeException( __( 'An uploads directory changed during analysis. Start a new scan so no files are skipped.', 'media-cleaner' ) );
			}
			if ( !$yielded && $processed === count( $entries ) ) {
				$next_cursor = isset( $page_info['next_cursor'] )
					? max( $next_cursor, (int) $page_info['next_cursor'] )
					: max( $next_cursor, (int) $work->cursor_value + $scanned_count );
			}
			$work_status = !$yielded && $processed === count( $entries ) && $directory_finished ? 'complete' : 'pending';
			if ( !$this->core->runs->update_work( $work->id, $work_status, $next_cursor ) ) {
				throw new RuntimeException( __( 'Media Cleaner could not checkpoint the uploads directory batch.', 'media-cleaner' ) );
			}
			$pending = $this->core->runs->pending_work_count( $run->id, 'retrieveFiles' );
			$finished = $pending === 0;
			$this->core->save_progress( $finished ? 'retrieveFiles_finished' : 'retrieveFiles', array(
				'work_id' => (int) $work->id,
				'path' => $work->target_key,
				'cursor' => $next_cursor,
				'pending_directories' => $pending,
				'skipped' => isset( $page_info['skipped'] ) ? $page_info['skipped'] : array(),
			) );
			$response = array(
				'success' => true,
				'message' => sprintf( __( 'Retrieved %d filesystem targets.', 'media-cleaner' ), $checked ),
				'data' => array(
					'results' => array(),
					'checked' => $checked,
					'finished' => $finished,
					'yielded' => $yielded,
					'work_id' => (int) $work->id,
					'cursor' => $next_cursor,
					'pending_directories' => $pending,
					'scanned' => $scanned_count,
					'skipped' => isset( $page_info['skipped'] ) ? $page_info['skipped'] : array(),
				),
			);
			$new_token = $this->verify_token();
			if ( $new_token ) $response['new_token'] = $new_token;
			return new WP_REST_Response( $response, 200 );
		}
		catch ( Throwable $e ) {
			if ( $work ) {
				$transient = $this->transient_error_details( $e );
				if ( !empty( $transient['retryable'] ) ) $this->core->runs->retry_work( $work->id, $e );
				else $this->core->runs->update_work( $work->id, 'failed', $work->cursor_value, $e );
			}
			return $this->fail_run_response( $e, $run->id, 'retrieveFiles' );
		}
	}

	function rest_retrieve_medias( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		try {

		//DEBUG: Simulate a service unavailable error
		// $error_chance = rand( 0, 4 ) === 0; // 25% chance to simulate an error
		// if ( $error_chance ) {
	    // 	return new WP_REST_Response( [ 'success' => false, 'message' => 'Test Service Unavailable!' ], 503 );
		// }

		$params = $request->get_json_params();
		$limit = isset( $params['limit'] ) ? max( 0, (int) $params['limit'] ) : 0;
		$limitsize = $this->core->get_option( 'medias_buffer' );
		$unattachedOnly = $this->core->get_option( 'attach_is_use' );
		
		// Save step progress at the beginning of media retrieval
		if ( $limit === 0 ) {
			$this->core->save_progress( 'retrieveMedia' );
		}
		
		$results = $this->engine->get_media_entries( $limit, $limitsize, $unattachedOnly );
		$finished = count( $results ) < $limitsize;
		$processed = 0;
		$yielded = false;
		$this->core->timeout_check_start( count( $results ) );
		foreach ( $results as $media_id ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();
			$this->engine->check_media( $media_id );
			$this->core->timeout_check_additem();
			$processed++;
		}
		$finished = !$yielded && $processed === count( $results ) && $finished;
		$next_offset = $limit + $processed;
		$message = sprintf( __( "Retrieved %d targets.", 'media-cleaner' ), $processed );

		$this->core->save_progress( $finished ? 'retrieveMedia_finished' : 'retrieveMedia', array( 'limit' => $limit, 'limitSize' => $limitsize, 'next' => $next_offset, 'processed' => $processed ) );

		$this->core->clean_ob();

		$response = [ 
			'success' => true, 
			'message' => $message,
			'data' => [
				'limit' => $next_offset,
				'finished' => $finished,
				'results' => array(),
				'checked' => $processed,
				'yielded' => $yielded,
			]	
		];

		$new_token = $this->verify_token();
		if( $new_token ) {
			$response['new_token'] = $new_token;
		}

			return new WP_REST_Response( $response, 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'retrieveMedia' );
		}
	}

	function rest_check_targets( $request ) {
		$run = $this->activate_request_run( $request );
		if ( is_wp_error( $run ) ) {
			return $this->error_response( $run );
		}
		try {
		//DEBUG: Simulate a service unavailable error
		// $error_chance = rand( 0, 4 ) === 0; // 25% chance to simulate an error
		// if ( $error_chance ) {
	    // 	return new WP_REST_Response( [ 'success' => false, 'message' => 'Test Service Unavailable!' ], 503 );
		// }

		$params = $request->get_json_params();
		// DEBUG: Simulate a timeout
		//$this->core->deepsleep(10); header("HTTP/1.0 408 Request Timeout by Nyao"); exit;

		//ob_start();
		$data = isset( $params['targets'] ) && is_array( $params['targets'] ) ? array_values( $params['targets'] ) : array();
		$method = $this->core->current_method;

		if ( empty( $data ) || count( $data ) > 500 ) {
			return $this->fail_run_response( new WP_Error(
				'wpmc_invalid_targets',
				__( 'The scan target batch must contain between 1 and 500 items.', 'media-cleaner' ),
				array( 'status' => 400 )
			), $run->id, 'checkTargets' );
		}
		if ( $method === 'media' ) {
			$data = array_values( array_filter( array_map( 'absint', $data ) ) );
		}
		else if ( $method === 'files' || $method === 'optimize_thumbnails' ) {
			$normalized = array();
			foreach ( $data as $path ) {
				$path = $this->core->normalize_upload_relative_path( $path );
				if ( is_wp_error( $path ) ) return $this->fail_run_response( $path, $run->id, 'checkTargets' );
				$normalized[] = $path;
			}
			$data = $normalized;
		}
		else if ( $method === 'duplicates' ) {
			$data = array_values( array_filter( array_map( 'sanitize_text_field', $data ), function( $hash ) {
				return preg_match( '/^HASH:[a-f0-9]{64}$/', $hash ) === 1;
			} ) );
		}
		if ( empty( $data ) ) {
			return $this->fail_run_response( new WP_Error( 'wpmc_invalid_targets', __( 'The scan target batch is invalid.', 'media-cleaner' ), array( 'status' => 400 ) ), $run->id, 'checkTargets' );
		}

		$this->core->timeout_check_start( count( $data ) );
		$success = 0;
		if ( $method == 'files' ) {
			$this->core->safe_do_action( 'wpmc_check_file_init' ); // Build_CroppedFile_Cache() in pro core.php
		}
		foreach ( $data as $piece ) {
			$this->core->timeout_check();
			if ( $method == 'files' ) {
				$this->core->log( "🔎 Checking File: {$piece}..." );
				$result = ( $this->engine->check_file( $piece ) ? 1 : 0 );
				if ( $result ) {
					$success += $result;
				}
				// else {
				// 	$this->core->log( "👻 Nothing found." );
				// }
			}
			else if ( $method == 'media' ) {
				$this->core->log( "🔎 Checking Media #{$piece}..." );
				$result = ( $this->engine->check_media( $piece ) ? 1 : 0 );
				if ( $result ) {
					$success += $result;
				}
				// else {
				// 	$this->core->log( "👻 Nothing found." );
				// }
			} else if( $method == 'duplicates' ) {
				$this->core->log( "🔎 Checking Duplicate #{$piece}..." );
				$result = ( $this->engine->check_duplicates( $piece ) ? 1 : 0 );
				if ( $result ) {
					$success += $result;
				}
			}
			else if ( $method == 'optimize_thumbnails' ) {
				$this->core->log( "🔎 Checking Thumbnail File: {$piece}..." );
				$result = ( $this->engine->check_file( $piece ) ? 1 : 0 );
				if ( $result ) {
					$success += $result;
				}
			}
			//$this->core->log();
			$this->core->timeout_check_additem();
		}
		//ob_end_clean();
		$elapsed = $this->core->timeout_get_elapsed();
		$issues_found = count( $data ) - $success;
		$message = sprintf(
			// translators: %1$d is a number of targets, %2$d is a number of issues, %3$s is elapsed time in milliseconds
			__( 'Checked %1$d targets and found %2$d issues in %3$s.', 'media-cleaner' ),
			count( $data ), $issues_found, $elapsed
		);

		$response = [ 
			'success' => true, 
			'message' => $message,
			'data' => [
				'results' => $success
			]
		];

		$this->core->save_progress( 'checkTargets', array( 'last_batch_size' => count( $data ) ) );


		$new_token = $this->verify_token();
		if( $new_token ) {
			$response['new_token'] = $new_token;
		}

			return new WP_REST_Response( $response, 200 );
		}
		catch ( Throwable $e ) {
			return $this->fail_run_response( $e, $run->id, 'checkTargets' );
		}
	}

	/**
	 * Streams the preview of a trashed item. The trash is private storage with no
	 * public URL, so the file is served here, and only if it really is an image
	 * sitting inside the trash.
	 */
	function rest_trash_preview( $request ) {
		$id = (int) $request->get_param( 'id' );
		$issue = $this->core->get_issue( $id );
		if ( !$issue || (int) $issue->deleted !== 1 ) {
			return new WP_Error( 'wpmc_preview_not_found', __( 'This trashed item does not exist.', 'media-cleaner' ), array( 'status' => 404 ) );
		}

		$path = $this->trash_preview_path( $issue );
		if ( !$path ) {
			return new WP_Error( 'wpmc_preview_unavailable', __( 'This item cannot be previewed.', 'media-cleaner' ), array( 'status' => 404 ) );
		}
		$resolved = $this->core->resolve_trash_path( $path, true );
		if ( is_wp_error( $resolved ) || !is_file( $resolved ) || is_link( $resolved ) ) {
			return new WP_Error( 'wpmc_preview_unavailable', __( 'This item cannot be previewed.', 'media-cleaner' ), array( 'status' => 404 ) );
		}

		// Raster formats only, and never anything derived from the extension alone.
		// SVG is an image that can carry scripts: served inline from here, opening one
		// directly would run it under the site's own origin.
		$filetype = wp_check_filetype( basename( $resolved ) );
		$mime = isset( $filetype['type'] ) ? $filetype['type'] : '';
		$previewable = apply_filters( 'wpmc_previewable_trash_mimes', array(
			'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp',
		) );
		if ( !in_array( (string) $mime, (array) $previewable, true ) ) {
			return new WP_Error( 'wpmc_preview_not_an_image', __( 'Only images can be previewed.', 'media-cleaner' ), array( 'status' => 415 ) );
		}
		$size = (int) @filesize( $resolved );
		$limit = (int) apply_filters( 'wpmc_max_trash_preview_bytes', 8 * 1024 * 1024 );
		if ( $size < 1 || $size > $limit ) {
			return new WP_Error( 'wpmc_preview_too_large', __( 'This image is too large to be previewed.', 'media-cleaner' ), array( 'status' => 413 ) );
		}

		$this->core->clean_ob();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . $size );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: inline; filename="' . basename( $resolved ) . '"' );
		header( 'Cache-Control: private, max-age=300' );
		readfile( $resolved );
		exit;
	}

	/**
	 * The smallest file of a trashed item, so previewing never streams a huge
	 * original when a thumbnail was trashed along with it.
	 */
	private function trash_preview_path( $issue ) {
		if ( (int) $issue->type === 1 && $issue->postId ) {
			$meta = wp_get_attachment_metadata( $issue->postId );
			$main = $this->core->clean_uploaded_filename( get_attached_file( $issue->postId ) );
			if ( !empty( $meta['sizes']['thumbnail']['file'] ) && $main !== '' ) {
				$directory = dirname( $main );
				$directory = $directory === '.' ? '' : trailingslashit( $directory );
				return $directory . $meta['sizes']['thumbnail']['file'];
			}
			return $main !== '' ? $main : null;
		}
		// Filesystem items keep the display suffix, e.g. "file.jpg (+ 3 files)".
		$path = preg_replace( '/\s\(\+.*$/', '', (string) $issue->path );
		return $path !== '' ? $path : null;
	}

	function rest_refresh_logs() {
		return new WP_REST_Response( [ 'success' => true, 'data' => $this->core->get_logs() ], 200 );
	}

	function rest_clear_logs() {
		$this->core->clear_logs();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	private function settings_options_payload( $options = null, $include_progress = true ) {
		$options = is_array( $options ) ? $options : $this->core->get_all_options();
		$payload = array_merge( $options, array(
			'incompatible_plugins' => Meow_WPMC_Support::get_issues(),
			'native_plugins' => Meow_WPMC_Support::get_natives(),
		) );
		if ( $include_progress ) {
			$payload['scan_progress'] = $this->core->get_progress();
		}
		return $payload;
	}

	function rest_all_settings() {
		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->settings_options_payload( null, false ),
		], 200 );
	}

	function rest_update_options( $request ) {
		try {
			$params = $this->request_json( $request );
			if ( !isset( $params['options'] ) || !is_array( $params['options'] ) ) {
				return $this->error_response( new WP_Error( 'wpmc_invalid_options', __( 'A valid options object is required.', 'media-cleaner' ), array( 'status' => 400 ) ) );
			}
			unset( $params['options']['logs_path'] );
			$valid_regex = $this->validate_regex_options( array_merge( $this->core->get_all_options(), $params['options'] ) );
			if ( is_wp_error( $valid_regex ) ) return $this->error_response( $valid_regex );

			if ( count( $params['options'] ) === 1 ) {
				$this->core->log( 'Updating the Media Cleaner option: ' . sanitize_key( key( $params['options'] ) ) );

				$options = $this->core->get_all_options();
				$options[ key( $params['options'] ) ] = $params['options'][ key( $params['options'] ) ];
				$params['options'] = $options;
			}

			$value = $params['options'];

			$options = $this->core->update_options( $value );
			return new WP_REST_Response([ 'success' => true, 'message' => 'OK', 'options' => $this->settings_options_payload( $options ) ], 200 );
		} 
		catch ( Throwable $e ) {
			return $this->error_response( $e );
		}
	}

	function rest_reset_options() {
		$this->core->reset_options();
		return new WP_REST_Response( [ 'success' => true, 'options' => $this->settings_options_payload() ], 200 );
	}

	function rest_reset_db() {
		if ( !current_user_can( 'manage_options' ) ) {
			return $this->error_response( new WP_Error( 'wpmc_reset_forbidden', __( 'Only an administrator can reset the Media Cleaner database.', 'media-cleaner' ), array( 'status' => 403 ) ) );
		}
		if ( $this->core->runs->get_resumable() ) {
			return $this->error_response( new WP_Error( 'wpmc_reset_scan_running', __( 'The database cannot be reset while a scan is resumable.', 'media-cleaner' ), array( 'status' => 409 ) ) );
		}
		global $wpdb;
		$table_scan = $wpdb->prefix . 'mclean_scan';
		$active_run_id = $this->core->runs->get_active_id();
		$trashed = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_scan WHERE run_id = %d AND deleted = 1",
			$active_run_id
		) );
		if ( $trashed > 0 ) {
			return $this->error_response( new WP_Error( 'wpmc_reset_trash_not_empty', __( 'Recover or permanently delete every quarantined item before resetting the database.', 'media-cleaner' ), array( 'status' => 409, 'trash_count' => $trashed ) ) );
		}
		wpmc_reset();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	function rest_reference_entries( $request ) {
		global $wpdb;
		$limit = max( 1, min( 100, (int) $request->get_param('limit') ) );
		$skip = max( 0, (int) $request->get_param('skip') );
		$orderBy = sanitize_text_field( $request->get_param('orderBy') );
		$order = sanitize_text_field( $request->get_param('order') );
		$search = sanitize_text_field( $request->get_param('search') );
		$referenceFilter = sanitize_text_field( $request->get_param('referenceFilter') );
		$table_ref = $wpdb->prefix . "mclean_refs";
		$run_id = $this->core->get_run_id();
	
		$total = $this->count_references($search, $referenceFilter);
	
		// Every column is qualified with the r alias. Searching joins the posts table,
		// and an unqualified id exists on both sides, which makes the query ambiguous
		// and returns no reference at all.
		$where_sql = $wpdb->prepare( 'AND r.run_id = %d', $run_id );
		if ($referenceFilter === 'mediaIds') {
			$where_sql .= ' AND r.mediaId IS NOT NULL';
		} else if ($referenceFilter === 'mediaUrls') {
			$where_sql .= ' AND r.mediaUrl IS NOT NULL';
		}

		$order_sql = 'ORDER BY r.id DESC';
		if ( $orderBy === 'id' ) {
			$order_sql = 'ORDER BY r.id IS NULL, r.id ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		} elseif ( $orderBy === 'mediaId' ) {
			$order_sql = 'ORDER BY r.mediaId IS NULL, r.mediaId ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		} elseif ( $orderBy === 'mediaUrl' ) {
			$order_sql = 'ORDER BY r.mediaUrl IS NULL, r.mediaUrl ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		} elseif ( $orderBy === 'originType' ) {
			$order_sql = 'ORDER BY r.originType ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}

		if ( empty( $search ) ) {
			$entries = $wpdb->get_results(
				$wpdb->prepare( "SELECT r.*
					FROM $table_ref r
					WHERE 1=1
					$where_sql
					$order_sql
					LIMIT %d, %d", $skip, $limit
				)
			);
		} else {
			$posts_table = $wpdb->posts;
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$entries = $wpdb->get_results(
				$wpdb->prepare( "SELECT r.*
					FROM $table_ref r
					LEFT JOIN $posts_table p ON r.origin = p.ID
					WHERE (r.mediaId LIKE %s
					OR r.mediaUrl LIKE %s
					OR r.originType LIKE %s
					OR r.origin LIKE %s
					OR p.post_title LIKE %s)
					$where_sql
					$order_sql
					LIMIT %d, %d", $search_like, $search_like, $search_like, $search_like, $search_like, $skip, $limit
				)
			);
		}
	
		// Prepare arrays to store IDs and data
		$post_ids = [];
		$media_ids = [];
		$media_urls = [];
	
		// Extract post IDs and media IDs/URLs
		foreach ( $entries as $entry ) {

			if( $entry->origin && is_numeric( $entry->origin ) ) {
				$post_ids[] = (int) $entry->origin;
			}

			// Collect media IDs and URLs
			if ( $entry->mediaId ) {
				$media_ids[] = $entry->mediaId;
			}
	
			if ( $entry->mediaUrl ) {
				$media_urls[] = $entry->mediaUrl;
			}
		}
	
		// Remove duplicates
		$post_ids = array_unique( $post_ids );
		$media_ids = array_unique( $media_ids );
		$media_urls = array_unique( $media_urls );
	
		// Get post titles. get_posts() would silently drop the post types excluded
		// from search (many builders and galleries use such types) and anything not
		// published, so the posts are read directly instead.
		$post_titles = [];
		if ( !empty( $post_ids ) ) {
			_prime_post_caches( $post_ids, false, false );
			foreach ( $post_ids as $post_id ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$post_titles[ $post->ID ] = $post->post_title;
				}
			}
		}
	
		// Get thumbnails and titles for media IDs
		$media_thumbnails = [];
		$media_titles = [];
		if ( !empty( $media_ids ) ) {
			_prime_post_caches( $media_ids, false, true );
		}
		foreach ( $media_ids as $media_id ) {
			$media = wp_get_attachment_image_src( $media_id, 'thumbnail' );
			if ( $media ) {
				$media_thumbnails[ $media_id ] = $media[0];
			}
			$media_post = get_post( $media_id );
			if ( $media_post ) {
				$media_titles[ $media_id ] = $media_post->post_title;
			}
		}
	
		// Get the uploads directory URL
		$upload_dir = wp_upload_dir();
		$upload_baseurl = $upload_dir['baseurl'];

		// Map media URLs to attachment IDs and get thumbnails. The references store a
		// path relative to the uploads folder, so it has to be made absolute first,
		// otherwise nothing is ever resolved and the full size image ends up being
		// used as a thumbnail. A resolution suffix is dropped to find the original.
		$media_url_to_id = [];
		foreach ( $media_urls as $media_url ) {
			$absolute = strpos( $media_url, 'http' ) === 0
				? $media_url
				: trailingslashit( $upload_baseurl ) . ltrim( $media_url, '/' );
			$attachment_id = attachment_url_to_postid( $absolute );
			if ( !$attachment_id ) {
				$original = preg_replace( '/-\d+x\d+(\.[A-Za-z0-9]+)$/', '$1', $absolute );
				if ( $original !== $absolute ) {
					$attachment_id = attachment_url_to_postid( $original );
				}
			}
			if ( $attachment_id ) {
				$media_url_to_id[ $media_url ] = $attachment_id;
				$media = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
				if ( $media ) {
					$media_thumbnails[ $attachment_id ] = $media[0];
				}
			}
		}
	
		// Assign post titles and thumbnails to entries
		foreach ( $entries as $entry ) {
			// Assign post title
			if ( isset( $entry->origin ) && isset( $post_titles[ $entry->origin ] ) ) {
				$entry->post_title = $post_titles[ $entry->origin ];
			} else {
				$entry->post_title = '';
			}
		
	
			// Assign the media title, so a reference by ID is readable without
			// having to open the media.
			$entry->media_title = isset( $entry->mediaId ) && isset( $media_titles[ $entry->mediaId ] )
				? $media_titles[ $entry->mediaId ]
				: '';
			$entry->media_exists = !$entry->mediaId || isset( $media_titles[ $entry->mediaId ] );

			// Assign thumbnail
			$entry->thumbnail = '';
	
			if ( $entry->mediaId && isset( $media_thumbnails[ $entry->mediaId ] ) ) {
				$entry->thumbnail = $media_thumbnails[ $entry->mediaId ];
			} elseif ( $entry->mediaUrl && isset( $media_url_to_id[ $entry->mediaUrl ] ) ) {
				$attachment_id = $media_url_to_id[ $entry->mediaUrl ];
				if ( isset( $media_thumbnails[ $attachment_id ] ) ) {
					$entry->thumbnail = $media_thumbnails[ $attachment_id ];
				}
			}
	
			// If thumbnail is still empty, use mediaUrl as thumbnail
			if ( empty( $entry->thumbnail ) && $entry->mediaUrl ) {
				// Ensure mediaUrl is absolute
				if ( strpos( $entry->mediaUrl, 'http' ) !== 0 ) {
					$entry->thumbnail = $upload_baseurl . '/' . ltrim( $entry->mediaUrl, '/' );
				} else {
					$entry->thumbnail = $entry->mediaUrl;
				}
			}
	
			// Ensure thumbnail is absolute URL ( for sizes of medias )
			if ( !empty( $entry->thumbnail ) && strpos( $entry->thumbnail, 'http' ) !== 0 ) {
				$entry->thumbnail = $upload_baseurl . '/' . ltrim( $entry->thumbnail, '/' );
			}
		}
	
		return new WP_REST_Response( [ 'success' => true, 'data' => $entries, 'total' => $total ], 200 );
	}

	function rest_entries( $request ) {
		global $wpdb;
		$limit = max( 1, min( 100, (int) $request->get_param('limit') ) );
		$skip = max( 0, (int) $request->get_param('skip') );
		$filterBy = sanitize_text_field( $request->get_param('filterBy') );
		$orderBy = sanitize_text_field( $request->get_param('orderBy') );
		$order = sanitize_text_field( $request->get_param('order') );
		$search = sanitize_text_field( $request->get_param('search') );
		$repair_mode = rest_sanitize_boolean( $request->get_param('repairMode') );
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		$total = 0;

		if ( $filterBy === 'references' ) {
			return $this->rest_reference_entries( $request );
		}

		$entries = [];
		if ( $repair_mode ) {
			$entries = $this->core->get_issues_to_repair( $orderBy, $order, $search, $skip, $limit );
			$total = $this->core->get_count_of_issues_to_repair( $search );
		}
		else {
			$filters = array(
				'issues' => 'ignored = 0 AND deleted = 0',
				'ignored' => 'ignored = 1',
				'trash' => 'deleted = 1',
				'all' => 'deleted = 0',
			);
			$filter_sql = isset( $filters[ $filterBy ] ) ? $filters[ $filterBy ] : $filters['all'];
			$total = $filterBy === 'issues' ? $this->count_issues( $search ) : ( $filterBy === 'ignored' ? $this->count_ignored( $search ) : ( $filterBy === 'trash' ? $this->count_trash( $search ) : 0 ) );

			$allowed_order = array( 'id', 'type', 'postId', 'time', 'path', 'size' );
			$order_column = in_array( $orderBy, $allowed_order, true ) ? $orderBy : 'id';
			$order_direction = $order === 'asc' ? 'ASC' : 'DESC';
			$search_sql = empty( $search ) ? '' : $wpdb->prepare( 'AND path LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
			$entries = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, type, postId, path, size, ignored, deleted, issue, time
				FROM $table_scan
				WHERE run_id = %d AND $filter_sql $search_sql
				ORDER BY $order_column $order_direction
				LIMIT %d, %d",
				$run_id,
				$skip,
				$limit
			) );
		}

		$is_trash = $filterBy === 'trash';
		$base = $this->core->upload_url;
		foreach ( $entries as $entry ) {
			if ( $is_trash ) {
				// The trash lives outside the uploads folder, so it has no public URL.
				// The preview is streamed by the plugin instead of being linked.
				$preview = add_query_arg( array(
					'id' => (int) $entry->id,
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				), rest_url( $this->namespace . '/trash_preview' ) );
				$entry->thumbnail_url = $preview;
				$entry->image_url = $preview;
				if ( $entry->type != 0 ) {
					$entry->title = html_entity_decode( get_the_title( $entry->postId ) );
				}
				continue;
			}

			// FILESYSTEM
			if ( $entry->type == 0 ) {
				$entry->thumbnail_url = htmlspecialchars( trailingslashit( $base ) . $entry->path, ENT_QUOTES );
				$entry->image_url = $entry->thumbnail_url;

				// If the extension is not an image, we set the thumbnail to null
				$ext = pathinfo( $entry->path, PATHINFO_EXTENSION );
				if ( !$this->core->is_image_extension( $ext ) ) {
					$entry->thumbnail_url = null;
				}

				

			}
			// MEDIA
			else {
				$attachment_src = wp_get_attachment_image_src( $entry->postId, 'thumbnail' );
				$attachment_src_large = wp_get_attachment_image_src( $entry->postId, 'large' );
				$thumbnail = empty( $attachment_src ) ? null : $attachment_src[0];
				$image = empty( $attachment_src_large ) ? null : $attachment_src_large[0];
				// This was working when the Post Type" was attachment"
				$entry->thumbnail_url = $thumbnail;
				$entry->image_url = $image;
				$entry->title = html_entity_decode( get_the_title( $entry->postId ) );
			}
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $entries, 'total' => $total ], 200 );
	}

	// Nothing here waits for a scan. Only trashing something new does, and delete()
	// refuses that on its own. Recovering, emptying the trash and ignoring are the
	// user acting on decisions they already made.
	private function perform_item_operations( $request, $operation, $callback ) {
		$params = $this->request_json( $request );
		$ids = isset( $params['entryIds'] ) ? (array) $params['entryIds'] : array();
		if ( isset( $params['entryId'] ) ) {
			$ids[] = $params['entryId'];
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		if ( empty( $ids ) || count( $ids ) > 100 ) {
			return $this->error_response( new WP_Error( 'wpmc_invalid_operation_items', __( 'Cleanup requests must contain between 1 and 100 valid items.', 'media-cleaner' ), array( 'status' => 400 ) ) );
		}
		$request_key = isset( $params['requestKey'] ) ? sanitize_text_field( $params['requestKey'] ) : wp_generate_uuid4();
		$results = array();
		$succeeded = 0;
		$failed = 0;
		$attempted = 0;
		$yielded = false;
		$this->core->timeout_check_start( count( $ids ) );

		foreach ( $ids as $index => $id ) {
			if ( $index > 0 && $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$attempted = $index + 1;
			$issue = null;
			$journal = $this->core->runs->begin_operation( $id, $operation, $request_key );
			if ( is_wp_error( $journal ) ) {
				$results[] = array( 'id' => $id, 'success' => false, 'code' => $journal->get_error_code(), 'message' => $journal->get_error_message() );
				$failed++;
				continue;
			}
			if ( $journal->state === 'complete' ) {
				$results[] = array( 'id' => $id, 'success' => true, 'idempotent' => true );
				$succeeded++;
				continue;
			}
			$manifest = json_decode( (string) $journal->manifest, true );
			$manifest = is_array( $manifest ) ? $manifest : array();
			if ( $journal->state === 'pending' ) {
				$issue = $this->core->get_issue( $id );
				if ( !$issue ) {
					$error = new WP_Error( 'wpmc_issue_missing', __( 'The selected Media Cleaner result no longer exists.', 'media-cleaner' ) );
					$this->core->runs->update_operation( $journal->id, 'failed', $manifest, $error );
					$results[] = array( 'id' => $id, 'success' => false, 'code' => $error->get_error_code(), 'message' => $error->get_error_message() );
					$failed++;
					continue;
				}
				$manifest = array(
					'initial_deleted' => (bool) $issue->deleted,
					'initial_ignored' => (bool) $issue->ignored,
					'initial_type' => (int) $issue->type,
				);
			}
			$requires_identity = in_array( $operation, array( 'delete', 'recover', 'repair' ), true );
			if ( $requires_identity && empty( $manifest['identity_validated'] ) ) {
				$issue = isset( $issue ) && $issue ? $issue : $this->core->get_issue( $id );
				$identity = $issue ? $this->core->validate_issue_manifest( $issue ) : new WP_Error( 'wpmc_issue_missing', __( 'The selected Media Cleaner result no longer exists.', 'media-cleaner' ) );
				if ( is_wp_error( $identity ) ) {
					$this->core->runs->update_operation( $journal->id, 'failed', $manifest, $identity );
					$results[] = array( 'id' => $id, 'success' => false, 'code' => $identity->get_error_code(), 'message' => $identity->get_error_message() );
					$failed++;
					continue;
				}
				$manifest['identity_validated'] = true;
			}
			if ( !$this->core->runs->update_operation( $journal->id, 'running', $manifest ) ) {
				$results[] = array( 'id' => $id, 'success' => false, 'code' => 'wpmc_operation_journal_failed', 'message' => __( 'The cleanup journal could not be updated.', 'media-cleaner' ) );
				$failed++;
				continue;
			}
			try {
				$result = in_array( $operation, array( 'delete', 'recover' ), true ) ? call_user_func( $callback, $id, $manifest ) : call_user_func( $callback, $id );
				if ( is_wp_error( $result ) || $result !== true ) {
					$error = is_wp_error( $result ) ? $result : new WP_Error( 'wpmc_operation_failed', __( 'The item could not be updated.', 'media-cleaner' ) );
					$this->core->runs->update_operation( $journal->id, 'failed', null, $error );
					$results[] = array( 'id' => $id, 'success' => false, 'code' => $error->get_error_code(), 'message' => $error->get_error_message() );
					$failed++;
				}
				else {
					if ( !$this->core->runs->update_operation( $journal->id, 'complete', $manifest ) ) {
						$results[] = array( 'id' => $id, 'success' => false, 'code' => 'wpmc_operation_journal_failed', 'message' => __( 'The item was updated, but the cleanup journal could not be finalized.', 'media-cleaner' ) );
						$failed++;
					}
					else {
						$results[] = array( 'id' => $id, 'success' => true );
						$succeeded++;
					}
				}
			}
			catch ( Throwable $e ) {
				$error = new WP_Error( 'wpmc_operation_exception', $e->getMessage() );
				$this->core->runs->update_operation( $journal->id, 'failed', null, $error );
				$results[] = array( 'id' => $id, 'success' => false, 'code' => $error->get_error_code(), 'message' => $error->get_error_message() );
				$failed++;
			}
		}

		$response = array(
			'success' => $failed === 0,
			'data' => array(
				'results' => $results,
				'succeeded' => $succeeded,
				'failed' => $failed,
				'request_key' => $request_key,
				'finished' => !$yielded,
				'remaining' => max( 0, count( $ids ) - $attempted ),
			),
			'message' => $failed === 0 ? __( 'All requested items were updated.', 'media-cleaner' ) : sprintf( __( '%1$d item(s) succeeded and %2$d failed.', 'media-cleaner' ), $succeeded, $failed ),
		);
		$new_token = $this->verify_token();
		if ( $new_token ) {
			$response['new_token'] = $new_token;
		}
		return new WP_REST_Response( $response, $failed === 0 ? 200 : 207 );
	}

	function rest_set_ignore( $request ) {
		$params = $this->request_json( $request );
		$ignore = isset( $params['ignore'] ) ? rest_sanitize_boolean( $params['ignore'] ) : true;
		return $this->perform_item_operations( $request, $ignore ? 'ignore' : 'unignore', function( $id ) use ( $ignore ) {
			return $this->core->ignore( $id, $ignore );
		} );
	}

	function rest_delete( $request ) {
		return $this->perform_item_operations( $request, 'delete', array( $this->core, 'delete' ) );
	}

	// Emptying the trash needs no scan: those files were set aside on purpose.
	function rest_force_trash_all( $request ) {
		$params = $this->request_json( $request );
		$initialize = isset( $params['initialize'] ) ? rest_sanitize_boolean( $params['initialize'] ) : true;
		$res = $this->core->force_trash( $initialize, 100 );
		if ( is_wp_error( $res ) ) return $this->error_response( $res );
		return new WP_REST_Response( array(
			'success' => true,
			'data' => $res,
			'message' => !empty( $res['finished'] ) ? __( 'The Media Cleaner trash has been emptied.', 'media-cleaner' ) : __( 'Media Cleaner emptied another bounded trash batch.', 'media-cleaner' ),
		), 200 );
	}

	function rest_recover( $request ) {
		return $this->perform_item_operations( $request, 'recover', array( $this->core, 'recover' ) );
	}

	function rest_repair( $request ) {
		return $this->perform_item_operations( $request, 'repair', array( $this->core, 'repair' ) );
	}

	function get_issues_ids( $search, $cursor = 0, $limit = 100 ) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare( "AND path LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $table_scan WHERE run_id = %d AND ID > %d AND ignored = 0 AND deleted = 0 $whereSql ORDER BY ID ASC LIMIT %d", $run_id, $cursor, $limit ) );
	}

	function get_ignored_ids( $search, $cursor = 0, $limit = 100 ) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare( "AND path LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $table_scan WHERE run_id = %d AND ID > %d AND ignored = 1 $whereSql ORDER BY ID ASC LIMIT %d", $run_id, $cursor, $limit ) );
	}

	function get_trash_ids( $search, $cursor = 0, $limit = 100 ) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare( "AND path LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $table_scan WHERE run_id = %d AND ID > %d AND deleted = 1 $whereSql ORDER BY ID ASC LIMIT %d", $run_id, $cursor, $limit ) );
	}

	function count_issues($search) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare("AND path LIKE %s", ( '%' . $search . '%' ));
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_scan WHERE run_id = %d AND ignored = 0 AND deleted = 0 $whereSql", $run_id ) );
	}

	function count_ignored($search) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare("AND path LIKE %s", ( '%' . $search . '%' ));
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_scan WHERE run_id = %d AND ignored = 1 $whereSql", $run_id ) );
	}

	function count_trash($search) {
		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare("AND path LIKE %s", ( '%' . $search . '%' ));
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		return (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_scan WHERE run_id = %d AND deleted = 1 $whereSql", $run_id ) );
	}

	function count_references($search, $referenceFilter) {
		global $wpdb;
		$table_ref = $wpdb->prefix . "mclean_refs";
		$run_id = $this->core->get_run_id();
		$posts_table = $wpdb->posts;
		$filter_sql = '';
		if ($referenceFilter === 'mediaIds') {
			$filter_sql = ' AND r.mediaId IS NOT NULL';
		} else if ($referenceFilter === 'mediaUrls') {
			$filter_sql = ' AND r.mediaUrl IS NOT NULL';
		}
		// The whole statement is prepared once: feeding an already prepared clause
		// back into prepare() would treat the escaped search value as placeholders.
		if ( empty( $search ) ) {
			return (int)$wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(r.id) FROM $table_ref r WHERE r.run_id = %d $filter_sql",
				$run_id
			) );
		}
		$search_like = '%' . $wpdb->esc_like( $search ) . '%';
		return (int)$wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(r.id) FROM $table_ref r
			LEFT JOIN $posts_table p ON r.origin = p.ID
			WHERE r.run_id = %d $filter_sql
			AND (r.mediaId LIKE %s OR r.mediaUrl LIKE %s OR r.originType LIKE %s
			OR r.origin LIKE %s OR p.post_title LIKE %s)",
			$run_id, $search_like, $search_like, $search_like, $search_like, $search_like
		) );
	}

	function rest_get_stats( $request ) {
		$search = sanitize_text_field( $request->get_param('search') );
		$reference_filter = sanitize_text_field( $request->get_param('referenceFilter') );
		$repair_mode = rest_sanitize_boolean( $request->get_param('repairMode') );

		global $wpdb;
		$whereSql = empty($search) ? '' : $wpdb->prepare("AND path LIKE %s", ( '%' . $search . '%' ));
		$table_scan = $wpdb->prefix . "mclean_scan";
		$run_id = $this->core->get_run_id();
		$issues = $repair_mode
			? $this->core->get_stats_of_issues_to_repair( $search )
			: $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) as entries, SUM(size) as size
				FROM $table_scan WHERE run_id = %d AND ignored = 0 AND deleted = 0 $whereSql", $run_id ) );
		$ignored = (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*)
			FROM $table_scan WHERE run_id = %d AND ignored = 1 $whereSql", $run_id ) );
		$trash = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) as entries, SUM(size) as size
			FROM $table_scan WHERE run_id = %d AND deleted = 1 $whereSql", $run_id ) );
		$references = $this->count_references($search, $reference_filter);

		return new WP_REST_Response( [ 'success' => true, 'data' => array(
			'issues' => $issues->entries,
			'issues_size' => $issues->size,
			'ignored' => $ignored,
			'trash' => $trash->entries,
			'trash_size' => $trash->size,
			'references' => $references,
		) ], 200 );
	}

	function rest_uploads_directory_hierarchy( $request ) {
		if ( !$this->admin->is_pro_user() ) {
			return $this->error_response( new WP_Error(
				'wpmc_pro_required',
				__( 'This feature is available to Pro users.', 'media-cleaner' ),
				array( 'status' => 403 )
			) );
		}

		$force = rest_sanitize_boolean( $request->get_param('force') );
		$transientKey = 'wpmc_uploads_directory_hierarchy_' . get_current_blog_id();
		if ( $force ) {
			delete_transient( $transientKey );
		}

		$data = get_transient( $transientKey );
		if ( !$data ) {
			$data = $this->core->get_uploads_directory_hierarchy();
			set_transient( $transientKey, $data, HOUR_IN_SECONDS );
		}

		$uploads_dir = wp_upload_dir();
		$root = wp_normalize_path( '/' . wp_basename( $uploads_dir['basedir'] ) );

		return new WP_REST_Response( [ 'success' => true, 'data' => [
			'root' => $root,
			'hierarchy' => $data,
		] ] , 200 );
	}

	function rest_get_progress() {
		$progress = $this->core->get_progress();
		return new WP_REST_Response( [ 'success' => true, 'data' => $progress ], 200 );
	}

	function rest_clear_progress() {
		$this->core->clear_step_progress();
		return new WP_REST_Response( [ 'success' => true, 'message' => __( 'Progress cleared.', 'media-cleaner' ) ], 200 );
	}

	function rest_export( $request ) {
		global $wpdb;
		$table_scan = $wpdb->prefix . "mclean_scan";
		$table_ref = $wpdb->prefix . "mclean_refs";
		$run_id = $this->core->get_run_id();
		$section = sanitize_key( (string) $request->get_param( 'section' ) );
		$section = $section ?: 'issues';
		$cursor = max( 0, (int) $request->get_param( 'cursor' ) );
		$limit_param = (int) $request->get_param( 'limit' );
		$limit = $limit_param > 0 ? max( 1, min( 500, $limit_param ) ) : 500;
		$sections = array( 'issues', 'ignored', 'trash', 'references' );
		if ( !in_array( $section, $sections, true ) ) $section = 'issues';

		$rows = array();
		if ( $section === 'references' ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, mediaId, mediaUrl, originType, origin FROM $table_ref WHERE run_id = %d AND id > %d ORDER BY id ASC LIMIT %d", $run_id, $cursor, $limit ) );
		}
		else {
			$condition = $section === 'issues' ? 'ignored = 0 AND deleted = 0' : ( $section === 'ignored' ? 'ignored = 1' : 'deleted = 1' );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, path, size, issue, time, postId FROM $table_scan WHERE run_id = %d AND id > %d AND $condition ORDER BY id ASC LIMIT %d", $run_id, $cursor, $limit ) );
		}

		$csv = $section === 'issues' && $cursor === 0 ? "Tab,ID,Path/Url,Size,Issue/Origin,Time,PostId,MediaId\n" : '';
		foreach ( $rows as $row ) {
			if ( $section === 'references' ) {
				$post_id = preg_match( '/\[(\d+)\]/', $row->originType, $matches ) ? $matches[1] : '';
				$csv .= implode( ',', array_map( array( $this, 'csv_cell' ), array( 'Found In Use Medias', $row->id, $row->mediaUrl, '', $row->originType, '', $post_id, $row->mediaId ) ) ) . "\n";
			}
			else {
				$label = $section === 'issues' ? 'Issues' : ( $section === 'ignored' ? 'Ignored' : 'Trash' );
				$csv .= implode( ',', array_map( array( $this, 'csv_cell' ), array( $label, $row->id, $row->path, $row->size, $row->issue, $row->time, $row->postId, '' ) ) ) . "\n";
			}
		}

		$next_cursor = !empty( $rows ) ? (int) end( $rows )->id : $cursor;
		$section_finished = count( $rows ) < $limit;
		$section_index = array_search( $section, $sections, true );
		$finished = $section_finished && $section_index === count( $sections ) - 1;
		$next_section = $section_finished && !$finished ? $sections[ $section_index + 1 ] : $section;
		if ( $section_finished && !$finished ) $next_cursor = 0;

		return new WP_REST_Response( array( 'success' => true, 'data' => array(
			'chunk' => $csv,
			'section' => $next_section,
			'cursor' => $next_cursor,
			'finished' => $finished,
		) ), 200 );
	}

	public function csv_cell( $value ) {
		$value = (string) $value;
		if ( preg_match( '/^[=+\-@]/', $value ) ) $value = "'" . $value;
		return '"' . str_replace( '"', '""', $value ) . '"';
	}
}
