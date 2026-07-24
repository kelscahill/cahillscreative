<?php

class Meow_WPMC_Runs {

	// 6 and 7 were used during development and never released, so no site in the wild
	// carries them. A development site still stamped 6 or 7 is left alone, since it is
	// only ahead of itself: the next real migration must be 8, or those sites would
	// skip it.
	const SCHEMA_VERSION = 5;
	const LOCK_OPTION = 'wpmc_scan_lock';
	const ACTIVE_RUN_OPTION = 'wpmc_active_run_id';
	const SCHEMA_OPTION = 'wpmc_schema_version';
	const SCHEMA_LOCK_OPTION = 'wpmc_schema_upgrade_lock';
	const LOCK_TTL = 1800;

	private $core;
	private $schema_ready = null;

	public function __construct( $core ) {
		$this->core = $core;
	}

	public function maybe_upgrade() {
		$version = (int) get_option( self::SCHEMA_OPTION, 0 );
		if ( $version >= self::SCHEMA_VERSION && $this->tables_exist() ) {
			return true;
		}
		$lock = get_option( self::SCHEMA_LOCK_OPTION, null );
		if ( is_array( $lock ) && !empty( $lock['expires_at'] ) && (int) $lock['expires_at'] > time() ) return false;
		if ( $lock !== null ) delete_option( self::SCHEMA_LOCK_OPTION );
		$token = wp_generate_uuid4();
		if ( !add_option( self::SCHEMA_LOCK_OPTION, array( 'token' => $token, 'expires_at' => time() + 600 ), '', false ) ) return false;

		try {
			wpmc_create_database();
			if ( !$this->migrate_reference_indexes() ) return false;
			if ( !$this->tables_exist( true ) ) return false;
			if ( $version < 2 ) {
				delete_transient( 'wpmc_progress' );
			}
			// Schema 2 to 4 force-disabled shortcode analysis on upgrade, which silently
			// removed references produced by rendered shortcodes (Foo Gallery and others)
			// and caused false positives. Undo that overwrite once; users who prefer it
			// disabled can turn it off again in the settings.
			if ( $version >= 2 && $version < 5 ) {
				$options = get_option( 'wpmc_options', array() );
				if ( is_array( $options ) && !empty( $options['shortcodes_disabled'] ) ) {
					$options['shortcodes_disabled'] = false;
					update_option( 'wpmc_options', $options, false );
				}
			}
			update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
			return true;
		}
		finally {
			$stored = get_option( self::SCHEMA_LOCK_OPTION, null );
			if ( is_array( $stored ) && isset( $stored['token'] ) && hash_equals( $token, (string) $stored['token'] ) ) delete_option( self::SCHEMA_LOCK_OPTION );
		}
	}

	public function table( $name ) {
		global $wpdb;
		$tables = array(
			'scan' => $wpdb->prefix . 'mclean_scan',
			'refs' => $wpdb->prefix . 'mclean_refs',
			'runs' => $wpdb->prefix . 'mclean_runs',
			'work' => $wpdb->prefix . 'mclean_work',
			'operations' => $wpdb->prefix . 'mclean_operations',
			'duplicates' => $wpdb->prefix . 'mclean_duplicates',
		);
		return isset( $tables[ $name ] ) ? $tables[ $name ] : null;
	}

	public function tables_exist( $refresh = false ) {
		global $wpdb;
		if ( !$refresh && $this->schema_ready !== null ) return $this->schema_ready;
		$this->schema_ready = false;
		$requirements = array(
			'scan' => array( 'run_id', 'path_hash', 'manifest', 'deleted', 'ignored' ),
			'refs' => array( 'run_id', 'mediaUrl_hash', 'ref_hash' ),
			'runs' => array( 'status', 'phase', 'checkpoint', 'heartbeat_at' ),
			'work' => array( 'run_id', 'target_hash', 'snapshot_token', 'cursor_value', 'status' ),
			'operations' => array( 'run_id', 'request_key', 'state', 'manifest' ),
			'duplicates' => array( 'run_id', 'media_id', 'content_hash' ),
		);
		foreach ( $requirements as $name => $columns ) {
			$table = $this->table( $name );
			if ( !$table || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
				return false;
			}
			foreach ( $columns as $column ) {
				$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $column ) );
				if ( $found !== $column ) return false;
			}
		}
		$critical_indexes = array(
			array( $this->table( 'scan' ), 'run_state_index' ),
			array( $this->table( 'scan' ), 'run_path_index' ),
			array( $this->table( 'refs' ), 'run_ref_hash_unique' ),
			array( $this->table( 'work' ), 'run_target_unique' ),
			array( $this->table( 'operations' ), 'request_issue_unique' ),
		);
		foreach ( $critical_indexes as $index ) {
			if ( !$wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM `{$index[0]}` WHERE Key_name = %s", $index[1] ) ) ) return false;
		}
		$refs_table = $this->table( 'refs' );
		if ( $wpdb->get_var( "SHOW INDEX FROM `$refs_table` WHERE Key_name = 'ref_hash_unique'" ) ) return false;
		$this->schema_ready = true;
		return true;
	}

	public function get_active_id() {
		return max( 0, (int) get_option( self::ACTIVE_RUN_OPTION, 0 ) );
	}

	public function get( $run_id ) {
		global $wpdb;
		$run_id = (int) $run_id;
		if ( $run_id < 1 || !$this->tables_exist() ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . $this->table( 'runs' ) . ' WHERE id = %d',
			$run_id
		) );
	}

	public function get_resumable() {
		global $wpdb;
		if ( !$this->tables_exist() ) {
			return null;
		}
		return $wpdb->get_row(
			"SELECT * FROM {$this->table( 'runs' )}
			WHERE status IN ('running', 'paused')
			ORDER BY id DESC LIMIT 1"
		);
	}

	public function start( $method, $config = array(), $request_key = '' ) {
		global $wpdb;
		if ( !$this->maybe_upgrade() ) {
			return new WP_Error( 'wpmc_schema_unavailable', __( 'Media Cleaner could not prepare its database tables.', 'media-cleaner' ) );
		}

		$method = sanitize_key( $method );
		if ( !in_array( $method, array( 'media', 'files', 'duplicates', 'optimize_thumbnails' ), true ) ) {
			return new WP_Error( 'wpmc_invalid_method', __( 'The selected scan method is invalid.', 'media-cleaner' ) );
		}

		$request_key = substr( sanitize_text_field( $request_key ), 0, 64 );
		if ( $request_key === '' ) {
			$request_key = wp_generate_uuid4();
		}
		$token = wp_generate_uuid4();
		$lock = get_option( self::LOCK_OPTION, null );
		if ( is_array( $lock ) && isset( $lock['request_key'] ) && hash_equals( (string) $lock['request_key'], $request_key ) ) {
			$locked_run = !empty( $lock['run_id'] ) ? $this->get( (int) $lock['run_id'] ) : null;
			if ( $locked_run && in_array( $locked_run->status, array( 'running', 'paused' ), true ) ) {
				$locked_config = $this->decode_json( $locked_run->config );
				if ( $locked_run->method !== $method || $locked_config !== $config ) {
					return new WP_Error( 'wpmc_start_request_conflict', __( 'This repeated start request no longer matches the staged scan configuration.', 'media-cleaner' ), array( 'status' => 409, 'run_id' => (int) $locked_run->id ) );
				}
				if ( $locked_run->phase === 'starting' ) {
					$initialized = $this->initialize_started_run( (int) $locked_run->id );
					if ( is_wp_error( $initialized ) ) return $initialized;
				}
				$this->refresh_lock( (int) $locked_run->id );
				return $this->get( (int) $locked_run->id );
			}
			// A matching temporary lock means the first attempt stopped before a run
			// became visible. Repeating the same start request is safe.
			delete_option( self::LOCK_OPTION );
			$lock = null;
		}
		if ( is_array( $lock ) && !empty( $lock['run_id'] ) ) {
			$locked_run = $this->get( (int) $lock['run_id'] );
			$is_fresh = !empty( $lock['expires_at'] ) && (int) $lock['expires_at'] > time();
			if ( $locked_run && in_array( $locked_run->status, array( 'running', 'paused' ), true ) ) {
				if ( !$is_fresh ) {
					$lock['expires_at'] = time() + self::LOCK_TTL;
					update_option( self::LOCK_OPTION, $lock, false );
				}
				return new WP_Error(
					'wpmc_scan_locked',
					__( 'Another Media Cleaner scan is already running. Resume or stop that scan first.', 'media-cleaner' ),
					array( 'status' => 409, 'run_id' => (int) $locked_run->id )
				);
			}
			$this->mark_stale( (int) $lock['run_id'] );
			delete_option( self::LOCK_OPTION );
		}
		else if ( is_array( $lock ) ) {
			$is_fresh = !empty( $lock['expires_at'] ) && (int) $lock['expires_at'] > time();
			if ( $is_fresh ) {
				return new WP_Error( 'wpmc_scan_initializing', __( 'Another Media Cleaner request is still initializing a scan. Retry shortly.', 'media-cleaner' ), array( 'status' => 409 ) );
			}
			delete_option( self::LOCK_OPTION );
		}
		else if ( $lock !== null && $lock !== false ) {
			delete_option( self::LOCK_OPTION );
		}

		// A missing or damaged option lock must never permit a second staged run.
		// The database row is the durable source of truth.
		$resumable = $this->get_resumable();
		if ( $resumable ) {
			update_option( self::LOCK_OPTION, array(
				'token' => wp_generate_uuid4(),
				'request_key' => '',
				'run_id' => (int) $resumable->id,
				'expires_at' => time() + self::LOCK_TTL,
			), false );
			return new WP_Error(
				'wpmc_scan_locked',
				__( 'A staged Media Cleaner scan already exists. Resume or stop that scan first.', 'media-cleaner' ),
				array( 'status' => 409, 'run_id' => (int) $resumable->id )
			);
		}

		$temporary_lock = array(
			'token' => $token,
			'request_key' => $request_key,
			'run_id' => 0,
			'expires_at' => time() + self::LOCK_TTL,
		);
		if ( !add_option( self::LOCK_OPTION, $temporary_lock, '', false ) ) {
			return new WP_Error( 'wpmc_scan_locked', __( 'Another scan started at the same time. Please resume that scan.', 'media-cleaner' ), array( 'status' => 409 ) );
		}

		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			$this->table( 'runs' ),
			array(
				'owner_id' => get_current_user_id(),
				'method' => $method,
				'status' => 'running',
				'phase' => 'starting',
				'config' => wp_json_encode( $config ),
				'checkpoint' => wp_json_encode( array() ),
				// The version that produced these results. checkpoint() merges into this
				// and complete() leaves it alone, so it is what the published run carries.
				'counters' => wp_json_encode( array( 'version' => WPMC_VERSION ) ),
				'errors' => wp_json_encode( array() ),
				'error_count' => 0,
				'created_at' => $now,
				'updated_at' => $now,
				'heartbeat_at' => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( !$inserted ) {
			delete_option( self::LOCK_OPTION );
			return new WP_Error( 'wpmc_run_create_failed', __( 'Media Cleaner could not create a scan run.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}

		$run_id = (int) $wpdb->insert_id;
		update_option( self::LOCK_OPTION, array(
			'token' => $token,
			'request_key' => $request_key,
			'run_id' => $run_id,
			'expires_at' => time() + self::LOCK_TTL,
		), false );
		$stored_lock = get_option( self::LOCK_OPTION, null );
		if ( !is_array( $stored_lock ) || !isset( $stored_lock['run_id'], $stored_lock['request_key'] ) || (int) $stored_lock['run_id'] !== $run_id || !hash_equals( $request_key, (string) $stored_lock['request_key'] ) ) {
			$wpdb->update( $this->table( 'runs' ), array( 'status' => 'failed', 'updated_at' => $now, 'finished_at' => $now ), array( 'id' => $run_id ), array( '%s', '%s', '%s' ), array( '%d' ) );
			delete_option( self::LOCK_OPTION );
			return new WP_Error( 'wpmc_scan_lock_failed', __( 'Media Cleaner could not persist the scan lock.', 'media-cleaner' ) );
		}
		$initialized = $this->initialize_started_run( $run_id );
		if ( is_wp_error( $initialized ) ) {
			$this->fail( $run_id, $initialized->get_error_code(), $initialized->get_error_message() );
			return $initialized;
		}

		return $this->get( $run_id );
	}

	public function assert_writable( $run_id ) {
		$run = $this->get( $run_id );
		if ( !$run ) {
			return new WP_Error( 'wpmc_run_not_found', __( 'This Media Cleaner scan no longer exists.', 'media-cleaner' ), array( 'status' => 404 ) );
		}
		if ( !in_array( $run->status, array( 'running', 'paused' ), true ) ) {
			return new WP_Error( 'wpmc_run_not_writable', __( 'This Media Cleaner scan is no longer writable.', 'media-cleaner' ), array( 'status' => 409, 'run_status' => $run->status ) );
		}
		return $run;
	}

	public function checkpoint( $run_id, $phase, $checkpoint = array(), $counters = null ) {
		global $wpdb;
		$run = $this->assert_writable( $run_id );
		if ( is_wp_error( $run ) ) {
			return $run;
		}

		$stored_counters = $this->decode_json( $run->counters );
		$coverage = isset( $stored_counters['coverage'] ) && is_array( $stored_counters['coverage'] ) ? $stored_counters['coverage'] : array();
		$coverage_steps = array(
			'resetIssuesAndReferences' => 'reset',
			'extractReferencesFromContent_finished' => 'content',
			'extractReferencesFromLibrary_finished' => 'library',
			'extractReferencesFromMedia_finished' => 'library',
			'extractReferencesFromDuplicates_finished' => 'duplicates',
			'extractReferencesFromThumbnails_finished' => 'thumbnails',
			'retrieveMedia_finished' => 'targets',
			'retrieveFiles_finished' => 'targets',
			'retrieveDuplicates_finished' => 'targets',
		);
		if ( isset( $coverage_steps[ $phase ] ) ) {
			$coverage[ $coverage_steps[ $phase ] ] = true;
		}
		$stored_counters['coverage'] = $coverage;
		if ( is_array( $counters ) ) {
			$stored_counters = array_merge( $stored_counters, $counters );
			$stored_counters['coverage'] = $coverage;
		}

		$phase_name = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $phase );
		$data = array(
			'phase' => $phase_name,
			'counters' => wp_json_encode( $stored_counters ),
			'updated_at' => current_time( 'mysql', true ),
			'heartbeat_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s', '%s', '%s' );
		// A null checkpoint means "leave it alone". The engines own that column and
		// write their own resume cursor into it during a step, so a caller that only
		// has counters to record must not blank it on the way past.
		if ( $checkpoint !== null ) {
			$data['checkpoint'] = wp_json_encode( $checkpoint );
			$formats[] = '%s';
		}

		$updated = $wpdb->update(
			$this->table( 'runs' ),
			$data,
			array( 'id' => (int) $run_id, 'status' => $run->status ),
			$formats,
			array( '%d', '%s' )
		);
		if ( $updated === false ) return false;
		if ( $updated === 0 ) {
			$current = $this->get( $run_id );
			if ( !$current || $current->status !== $run->status ) return false;
		}
		$this->refresh_lock( $run_id );
		return true;
	}

	public function fail( $run_id, $code, $message, $details = array() ) {
		global $wpdb;
		$run = $this->get( $run_id );
		if ( $run && $run->status === 'failed' ) return true;
		if ( !$run || !in_array( $run->status, array( 'running', 'paused' ), true ) ) {
			return false;
		}
		$errors = json_decode( $run->errors, true );
		$errors = is_array( $errors ) ? $errors : array();
		$errors[] = array(
			'code' => sanitize_key( $code ),
			'message' => sanitize_text_field( $message ),
			'details' => $details,
			'time' => time(),
		);
		$updated = $wpdb->update(
			$this->table( 'runs' ),
			array(
				'status' => 'failed',
				'errors' => wp_json_encode( $errors ),
				'error_count' => count( $errors ),
				'updated_at' => current_time( 'mysql', true ),
				'finished_at' => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $run_id, 'status' => $run->status ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $updated === false ) {
			return new WP_Error( 'wpmc_run_fail_failed', __( 'Media Cleaner could not persist the failed scan state.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		if ( $updated !== 1 ) return false;
		$this->release_lock( $run_id );
		return true;
	}

	public function pause( $run_id, $code, $message, $details = array() ) {
		global $wpdb;
		$run = $this->get( $run_id );
		if ( !$run || !in_array( $run->status, array( 'running', 'paused' ), true ) ) return false;
		$errors = $this->decode_json( $run->errors );
		$errors[] = array(
			'code' => sanitize_key( $code ),
			'message' => sanitize_text_field( $message ),
			'details' => array_merge( is_array( $details ) ? $details : array(), array( 'transient' => true ) ),
			'time' => time(),
		);
		$errors = array_slice( $errors, -20 );
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$this->table( 'runs' ),
			array( 'status' => 'paused', 'errors' => wp_json_encode( $errors ), 'updated_at' => $now, 'heartbeat_at' => $now ),
			array( 'id' => (int) $run_id, 'status' => $run->status ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $updated === 0 ) {
			$current = $this->get( $run_id );
			if ( !$current || $current->status !== 'paused' ) return false;
		}
		else if ( $updated === false ) {
			return new WP_Error( 'wpmc_run_pause_failed', __( 'Media Cleaner could not persist the paused scan state.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		$this->refresh_lock( $run_id );
		return true;
	}

	public function resume( $run_id ) {
		global $wpdb;
		$run = $this->assert_writable( $run_id );
		if ( is_wp_error( $run ) ) return $run;
		if ( $run->status === 'running' ) {
			$this->refresh_lock( $run_id );
			return $run;
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$this->table( 'runs' ),
			array( 'status' => 'running', 'updated_at' => $now, 'heartbeat_at' => $now ),
			array( 'id' => (int) $run_id, 'status' => 'paused' ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $updated !== 1 ) {
			return new WP_Error(
				'wpmc_run_resume_failed',
				__( 'Media Cleaner could not resume the staged scan.', 'media-cleaner' ),
				array( 'status' => 409, 'database_error' => $wpdb->last_error )
			);
		}
		$this->refresh_lock( $run_id );
		return $this->get( $run_id );
	}

	public function complete( $run_id ) {
		global $wpdb;
		$existing = $this->get( $run_id );
		if ( $existing && $existing->status === 'completed' ) {
			$active_id = $this->get_active_id();
			if ( $active_id === (int) $run_id ) return $existing;
			if ( $active_id > (int) $run_id || $this->get_resumable() ) {
				return new WP_Error( 'wpmc_run_publish_conflict', __( 'A newer scan prevents this completed scan from being activated.', 'media-cleaner' ), array( 'status' => 409 ) );
			}
			update_option( self::ACTIVE_RUN_OPTION, (int) $run_id, false );
			if ( $this->get_active_id() !== (int) $run_id ) {
				return new WP_Error( 'wpmc_active_run_publish_failed', __( 'The completed scan exists, but Media Cleaner could not activate it. Previous results remain active.', 'media-cleaner' ) );
			}
			$this->release_lock( $run_id );
			return $existing;
		}
		$run = $this->assert_writable( $run_id );
		if ( is_wp_error( $run ) ) {
			return $run;
		}
		if ( (int) $run->error_count > 0 ) {
			return new WP_Error( 'wpmc_run_has_errors', __( 'This scan has errors and cannot replace the last successful results.', 'media-cleaner' ), array( 'status' => 409 ) );
		}
		$required_final_phases = array(
			'media' => 'retrieveMedia_finished',
			'files' => 'retrieveFiles_finished',
			'duplicates' => 'retrieveDuplicates_finished',
			'optimize_thumbnails' => 'retrieveFiles_finished',
		);
		$required_phase = isset( $required_final_phases[ $run->method ] ) ? $required_final_phases[ $run->method ] : null;
		if ( !$required_phase || $run->phase !== $required_phase ) {
			return new WP_Error(
				'wpmc_run_incomplete',
				__( 'This scan has not completed every required phase and cannot be published.', 'media-cleaner' ),
				array( 'status' => 409, 'phase' => $run->phase, 'required_phase' => $required_phase )
			);
		}
		$config = $this->decode_json( $run->config );
		$counters = $this->decode_json( $run->counters );
		$coverage = isset( $counters['coverage'] ) && is_array( $counters['coverage'] ) ? $counters['coverage'] : array();
		$required_coverage = array( 'reset', 'targets' );
		if ( $run->method === 'media' && !empty( $config['content'] ) ) $required_coverage[] = 'content';
		if ( $run->method === 'files' && !empty( $config['filesystem_content'] ) ) $required_coverage[] = 'content';
		if ( $run->method === 'files' && !empty( $config['media_library'] ) ) $required_coverage[] = 'library';
		if ( $run->method === 'duplicates' ) {
			$required_coverage[] = 'duplicates';
			if ( !empty( $config['content'] ) ) $required_coverage[] = 'content';
		}
		if ( $run->method === 'optimize_thumbnails' ) $required_coverage[] = 'thumbnails';
		$missing_coverage = array_values( array_filter( $required_coverage, function( $name ) use ( $coverage ) {
			return empty( $coverage[ $name ] );
		} ) );
		if ( !empty( $missing_coverage ) ) {
			return new WP_Error(
				'wpmc_run_coverage_incomplete',
				__( 'This scan is missing required analysis coverage and cannot be published.', 'media-cleaner' ),
				array( 'status' => 409, 'missing' => $missing_coverage )
			);
		}

		$now = current_time( 'mysql', true );
		$table = $this->table( 'runs' );
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET status = 'completed', phase = 'completed', updated_at = %s,
			heartbeat_at = %s, finished_at = %s, published_at = %s
			WHERE id = %d AND status IN ('running', 'paused')",
			$now,
			$now,
			$now,
			$now,
			(int) $run_id
		) );
		if ( $updated !== 1 ) {
			return new WP_Error( 'wpmc_run_publish_failed', __( 'Media Cleaner could not publish the completed scan.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}

		update_option( self::ACTIVE_RUN_OPTION, (int) $run_id, false );
		if ( (int) get_option( self::ACTIVE_RUN_OPTION, 0 ) !== (int) $run_id ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE $table SET status = 'paused', phase = %s, updated_at = %s,
				finished_at = NULL, published_at = NULL WHERE id = %d AND status = 'completed'",
				$run->phase,
				current_time( 'mysql', true ),
				(int) $run_id
			) );
			$this->refresh_lock( $run_id );
			return new WP_Error( 'wpmc_active_run_publish_failed', __( 'Media Cleaner could not activate the completed scan, so it remains staged and resumable. Previous results remain active.', 'media-cleaner' ) );
		}
		$this->release_lock( $run_id );
		return $this->get( $run_id );
	}

	public function cancel( $run_id ) {
		global $wpdb;
		$run = $this->get( $run_id );
		if ( $run && $run->status === 'cancelled' ) return true;
		if ( !$run || !in_array( $run->status, array( 'running', 'paused' ), true ) ) {
			return false;
		}
		$updated = $wpdb->update(
			$this->table( 'runs' ),
			array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql', true ), 'finished_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $run_id, 'status' => $run->status ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $updated === false ) {
			return new WP_Error( 'wpmc_run_cancel_failed', __( 'Media Cleaner could not cancel the staged scan.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		if ( $updated !== 1 ) {
			$current = $this->get( $run_id );
			if ( !$current || $current->status !== 'cancelled' ) return false;
		}
		$this->release_lock( $run_id );
		return true;
	}

	public function discard( $run_id ) {
		global $wpdb;
		$run = $this->get( $run_id );
		if ( !$run || !in_array( $run->status, array( 'running', 'paused', 'failed', 'cancelled' ), true ) ) {
			return false;
		}
		if ( in_array( $run->status, array( 'running', 'paused' ), true ) ) {
			$cancelled = $this->cancel( $run_id );
			if ( is_wp_error( $cancelled ) ) return $cancelled;
			if ( !$cancelled ) return false;
		}
		$tables = array(
			$this->table( 'scan' ),
			$this->table( 'refs' ),
			$this->table( 'duplicates' ),
			$this->table( 'work' ),
			$this->table( 'operations' ),
		);
		foreach ( $tables as $table ) {
			if ( $wpdb->delete( $table, array( 'run_id' => (int) $run_id ), array( '%d' ) ) === false ) {
				return new WP_Error(
					'wpmc_run_discard_failed',
					__( 'The staged scan was cancelled, but some of its temporary data could not be removed.', 'media-cleaner' ),
					array( 'database_error' => $wpdb->last_error )
				);
			}
		}
		return true;
	}

	/**
	 * Whether the published results can be deleted from. They have to come from a
	 * scan that finished, with no other scan staged over it, and from this version of
	 * the plugin: each version analyses the site differently, so results made by
	 * another one are asked to be scanned again rather than trusted.
	 *
	 * Results that fail this stay on screen, and their trash stays usable. Only new
	 * deletions are refused, in delete().
	 */
	public function cleanup_allowed() {
		$run = $this->get( $this->get_active_id() );
		if ( !$run || $run->status !== 'completed' || $this->get_resumable() ) {
			return false;
		}
		$counters = json_decode( (string) $run->counters, true );
		$version = is_array( $counters ) && isset( $counters['version'] ) ? (string) $counters['version'] : '';
		return $version === WPMC_VERSION;
	}

	/**
	 * The same answer as cleanup_allowed(), with the run behind it, so the screen can
	 * say which scan it is waiting on instead of only that it is waiting.
	 */
	public function cleanup_status() {
		$summary = function( $r ) {
			if ( !$r ) return null;
			return array(
				'id' => (int) $r->id,
				'method' => $r->method,
				'status' => $r->status,
				'created_at' => $r->created_at,
				'finished_at' => $r->finished_at,
				'published_at' => $r->published_at,
			);
		};
		return array(
			// Asked, never re-implemented: two copies of this rule would drift apart.
			'allowed' => $this->cleanup_allowed(),
			'run' => $summary( $this->get( $this->get_active_id() ) ),
			'resumable' => $summary( $this->get_resumable() ),
		);
	}

	public function garbage_collect( $limit = 500 ) {
		global $wpdb;
		$active_id = $this->get_active_id();
		$candidates = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$this->table( 'runs' )}
			WHERE status IN ('completed', 'failed', 'cancelled') AND id != %d
			ORDER BY id DESC LIMIT 20",
			$active_id
		) );
		if ( count( $candidates ) <= 3 ) return true;
		$run_id = (int) $candidates[3];
		$limit = max( 10, min( 2000, (int) $limit ) );
		$tables = array(
			$this->table( 'scan' ),
			$this->table( 'refs' ),
			$this->table( 'duplicates' ),
			$this->table( 'work' ),
			$this->table( 'operations' ),
		);
		foreach ( $tables as $table ) {
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM `$table` WHERE run_id = %d LIMIT %d", $run_id, $limit ) );
			if ( $deleted === false ) return false;
			$remaining = (int) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM `$table` WHERE run_id = %d LIMIT 1", $run_id ) );
			if ( $remaining ) return true;
		}
		return $wpdb->delete( $this->table( 'runs' ), array( 'id' => $run_id ), array( '%d' ) ) !== false;
	}

	public function begin_operation( $issue_id, $operation, $request_key ) {
		global $wpdb;
		$run_id = $this->get_active_id();
		$operation = sanitize_key( $operation );
		$request_key = sanitize_text_field( $request_key );
		$table = $this->table( 'operations' );
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE request_key = %s AND issue_id = %d AND operation = %s",
			$request_key,
			(int) $issue_id,
			$operation
		) );
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert( $table, array(
			'run_id' => $run_id,
			'issue_id' => (int) $issue_id,
			'operation' => $operation,
			'state' => 'pending',
			'request_key' => $request_key,
			'manifest' => wp_json_encode( array() ),
			'created_at' => $now,
			'updated_at' => $now,
		), array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		if ( !$inserted ) {
			return new WP_Error( 'wpmc_operation_create_failed', __( 'The cleanup operation could not be recorded.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $wpdb->insert_id ) );
	}

	public function update_operation( $operation_id, $state, $manifest = null, $error = null ) {
		global $wpdb;
		$data = array(
			'state' => sanitize_key( $state ),
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s' );
		if ( in_array( $state, array( 'running', 'complete' ), true ) ) {
			$data['error_code'] = null;
			$data['error_message'] = null;
			$formats[] = '%s';
			$formats[] = '%s';
		}
		if ( is_array( $manifest ) ) {
			$data['manifest'] = wp_json_encode( $manifest );
			$formats[] = '%s';
		}
		if ( $error ) {
			$data['error_code'] = $error instanceof WP_Error ? $error->get_error_code() : 'operation_failed';
			$data['error_message'] = $error instanceof WP_Error ? $error->get_error_message() : (string) $error;
			$formats[] = '%s';
			$formats[] = '%s';
		}
		return $wpdb->update( $this->table( 'operations' ), $data, array( 'id' => (int) $operation_id ), $formats, array( '%d' ) ) !== false;
	}

	public function enqueue_work( $run_id, $phase, $target_type, $target_key, $cursor = 0 ) {
		global $wpdb;
		$target_key = (string) $target_key;
		$inserted = $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$this->table( 'work' )}
			(run_id, phase, target_type, target_key, target_hash, cursor_value, status, attempts, updated_at)
			VALUES (%d, %s, %s, %s, %s, %d, 'pending', 0, %s)",
			(int) $run_id,
			sanitize_key( $phase ),
			sanitize_key( $target_type ),
			$target_key,
			hash( 'sha256', $target_key ),
			max( 0, (int) $cursor ),
			current_time( 'mysql', true )
		) );
		return $inserted !== false;
	}

	public function next_work( $run_id, $phase ) {
		global $wpdb;
		$table = $this->table( 'work' );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table
			WHERE run_id = %d AND phase = %s AND status IN ('pending', 'running')
			ORDER BY id ASC LIMIT 1",
			(int) $run_id,
			sanitize_key( $phase )
		) );
	}

	public function get_work( $run_id, $phase, $target_key ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table( 'work' )}
			WHERE run_id = %d AND phase = %s AND target_hash = %s LIMIT 1",
			(int) $run_id,
			sanitize_key( $phase ),
			hash( 'sha256', (string) $target_key )
		) );
	}

	public function update_work( $work_id, $status, $cursor, $error = null ) {
		global $wpdb;
		$data = array(
			'status' => sanitize_key( $status ),
			'cursor_value' => max( 0, (int) $cursor ),
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%d', '%s' );
		if ( $error ) {
			$data['last_error'] = $error instanceof WP_Error
				? $error->get_error_message()
				: ( $error instanceof Throwable ? $error->getMessage() : (string) $error );
			$formats[] = '%s';
		}
		return $wpdb->update( $this->table( 'work' ), $data, array( 'id' => (int) $work_id ), $formats, array( '%d' ) ) !== false;
	}

	public function retry_work( $work_id, $error = null ) {
		global $wpdb;
		$message = $error instanceof WP_Error
			? $error->get_error_message()
			: ( $error instanceof Throwable ? $error->getMessage() : (string) $error );
		$table = $this->table( 'work' );
		return $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET status = 'pending', attempts = attempts + 1, last_error = %s, updated_at = %s WHERE id = %d",
			$message,
			current_time( 'mysql', true ),
			(int) $work_id
		) ) !== false;
	}

	public function set_work_snapshot( $work_id, $snapshot_token ) {
		global $wpdb;
		return $wpdb->update(
			$this->table( 'work' ),
			array( 'snapshot_token' => substr( (string) $snapshot_token, 0, 64 ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $work_id ),
			array( '%s', '%s' ),
			array( '%d' )
		) !== false;
	}

	public function pending_work_count( $run_id, $phase ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table( 'work' )} WHERE run_id = %d AND phase = %s AND status IN ('pending', 'running')",
			(int) $run_id,
			sanitize_key( $phase )
		) );
	}

	public function failed_work_count( $run_id, $phase ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table( 'work' )} WHERE run_id = %d AND phase = %s AND status = 'failed'",
			(int) $run_id,
			sanitize_key( $phase )
		) );
	}

	public function clear_work( $run_id, $phase ) {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->table( 'work' )} WHERE run_id = %d AND phase = %s",
			(int) $run_id,
			sanitize_key( $phase )
		) ) !== false;
	}

	public function wake_work( $run_id, $phase, $target_key ) {
		global $wpdb;
		return $wpdb->update(
			$this->table( 'work' ),
			array( 'status' => 'pending', 'updated_at' => current_time( 'mysql', true ) ),
			array(
				'run_id' => (int) $run_id,
				'phase' => sanitize_key( $phase ),
				'target_hash' => hash( 'sha256', (string) $target_key ),
				'status' => 'waiting',
			),
			array( '%s', '%s' ),
			array( '%d', '%s', '%s', '%s' )
		) !== false;
	}

	public function to_array( $run ) {
		if ( !$run ) {
			return null;
		}
		return array(
			'id' => (int) $run->id,
			'owner_id' => (int) $run->owner_id,
			'method' => $run->method,
			'status' => $run->status,
			'phase' => $run->phase,
			'config' => $this->decode_json( $run->config ),
			'checkpoint' => $this->decode_json( $run->checkpoint ),
			'counters' => $this->decode_json( $run->counters ),
			'errors' => $this->decode_json( $run->errors ),
			'error_count' => (int) $run->error_count,
			'created_at' => $run->created_at,
			'updated_at' => $run->updated_at,
			'heartbeat_at' => $run->heartbeat_at,
			'finished_at' => $run->finished_at,
			'published_at' => $run->published_at,
		);
	}

	private function decode_json( $value ) {
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function refresh_lock( $run_id ) {
		$lock = get_option( self::LOCK_OPTION, null );
		if ( is_array( $lock ) && isset( $lock['run_id'] ) && (int) $lock['run_id'] === (int) $run_id ) {
			$lock['expires_at'] = time() + self::LOCK_TTL;
			update_option( self::LOCK_OPTION, $lock, false );
		}
	}

	private function release_lock( $run_id ) {
		$lock = get_option( self::LOCK_OPTION, null );
		if ( is_array( $lock ) && isset( $lock['run_id'] ) && (int) $lock['run_id'] === (int) $run_id ) {
			delete_option( self::LOCK_OPTION );
		}
	}

	private function mark_stale( $run_id ) {
		global $wpdb;
		$run = $this->get( $run_id );
		if ( !$run || !in_array( $run->status, array( 'running', 'paused' ), true ) ) {
			return;
		}
		$wpdb->update(
			$this->table( 'runs' ),
			array( 'status' => 'failed', 'updated_at' => current_time( 'mysql', true ), 'finished_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $run_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}


	private function migrate_reference_indexes() {
		global $wpdb;
		$table = $wpdb->prefix . 'mclean_refs';
		$old_index = $wpdb->get_var( "SHOW INDEX FROM $table WHERE Key_name = 'ref_hash_unique'" );
		if ( $old_index ) {
			if ( $wpdb->query( "ALTER TABLE $table DROP INDEX ref_hash_unique" ) === false ) return false;
		}
		$new_index = $wpdb->get_var( "SHOW INDEX FROM $table WHERE Key_name = 'run_ref_hash_unique'" );
		if ( !$new_index ) {
			if ( $wpdb->query( "ALTER TABLE $table ADD UNIQUE KEY run_ref_hash_unique (run_id, ref_hash)" ) === false ) return false;
		}
		return true;
	}

	private function copy_persistent_results( $run_id ) {
		global $wpdb;
		$active_id = $this->get_active_id();
		$scan_table = $wpdb->prefix . 'mclean_scan';
		$result = $wpdb->query( $wpdb->prepare(
			"INSERT INTO $scan_table
			(run_id, time, type, postId, path, path_hash, manifest, size, ignored, deleted, issue, parentId)
			SELECT %d, source.time, source.type, source.postId, source.path,
				COALESCE(source.path_hash, SHA2(source.path, 256)), source.manifest, source.size,
				source.ignored, source.deleted, source.issue, NULL
			FROM $scan_table AS source
			WHERE source.run_id = %d AND (source.ignored = 1 OR source.deleted = 1)
			AND NOT EXISTS (
				SELECT 1 FROM $scan_table AS target
				WHERE target.run_id = %d
				AND target.type = source.type
				AND target.postId <=> source.postId
				AND target.path_hash <=> COALESCE(source.path_hash, SHA2(source.path, 256))
				AND target.issue = source.issue
				AND target.ignored = source.ignored
				AND target.deleted = source.deleted
			)",
			(int) $run_id,
			(int) $active_id,
			(int) $run_id
		) );
		if ( $result === false ) {
			return new WP_Error( 'wpmc_result_copy_failed', __( 'Media Cleaner could not preserve ignored and trashed results.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		return true;
	}

	private function initialize_started_run( $run_id ) {
		global $wpdb;
		$copied = $this->copy_persistent_results( $run_id );
		if ( is_wp_error( $copied ) ) return $copied;
		$updated = $wpdb->update(
			$this->table( 'runs' ),
			array( 'phase' => 'ready', 'updated_at' => current_time( 'mysql', true ), 'heartbeat_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $run_id, 'phase' => 'starting' ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( $updated === false ) {
			return new WP_Error( 'wpmc_run_initialize_failed', __( 'Media Cleaner could not finish initializing the scan run.', 'media-cleaner' ), array( 'database_error' => $wpdb->last_error ) );
		}
		return true;
	}
}
