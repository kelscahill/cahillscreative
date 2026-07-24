<?php

if ( !class_exists( 'Meow_WPMC_Transient_Exception', false ) ) {
	class Meow_WPMC_Transient_Exception extends RuntimeException {
		private $retry_after_ms;

		public function __construct( $message, $retry_after_ms = 2000, $previous = null ) {
			parent::__construct( $message, 0, $previous );
			$this->retry_after_ms = max( 250, min( 60000, (int) $retry_after_ms ) );
		}

		public function get_retry_after_ms() {
			return $this->retry_after_ms;
		}
	}
}

class Meow_WPMC_Core {

	
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $is_pro = false;
	public $engine = null;
	public $runs = null;
	public $catch_timeout = true; // This will halt the plugin before reaching the PHP timeout.
	public $types = "jpg|jpeg|jpe|gif|png|tiff|bmp|csv|svg|pdf|xls|xlsx|doc|docx|odt|wpd|rtf|tiff|mp3|mp4|mov|wav|lua|webp|avif|ico|woff2|woff|ttf|otf";
	public $current_method = 'media';
	public $servername = null; // meowapps.com (site URL without http/https)
	public $site_url = null; // https://meowapps.com
	public $upload_path = null; // /www/wp-content/uploads (path to uploads)
	public $upload_url = null; // wp-content/uploads (uploads without domain)
	private $option_name = 'wpmc_options';
	private $nonce = null; // Nonce for the REST API

	private $regex_file = '/[A-Za-z0-9-_,.\(\)\s]+[.]{1}(MIMETYPES)/';

	private $refcache = array();
	private $progress_key = 'wpmc_progress';
	private $run_id = 0;
	private $run_config = array();

	private $debug_logs = null;
	private $multilingual = false;
	private $languages = array();
	private $shortcode_analysis = false;
	private $trash_migration_error = null;
	private $parser_query_guard_active = false;
	private $parser_query_guard_label = '';
	private $parser_query_guard_warnings = array();
	private $request_start_time = 0;
	private $request_time_budget = null;

	public function get_shortcode_analysis() {
		return $this->shortcode_analysis;
	}

	public function is_debug() {
		return $this->debug_logs;
	}

	public function __construct() {
		$this->request_start_time = isset( $_SERVER['REQUEST_TIME_FLOAT'] )
			? (float) $_SERVER['REQUEST_TIME_FLOAT']
			: microtime( true );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'delete_attachment', array( $this, 'delete_attachment_related_data' ), 10, 1 );
		add_action( 'trashed_post', array( $this, 'delete_attachment_related_data' ), 10, 1 );
	}

	function plugins_loaded() {


		if ( is_admin() ) {
			new Meow_WPMC_UI( $this );
		}

		// Admin
		$this->admin = new Meow_WPMC_Admin( $this );

		// Advanced core
		if ( class_exists( 'MeowPro_WPMC_Core' ) ) {
			new MeowPro_WPMC_Core( $this );
		}

		// Only initialize variables if we are on a relevant screen
		$pages  = [ 'wpmc_dashboard', 'wpmc_settings' ];
		$page = isset( $_GET["page"] ) ? sanitize_text_field( $_GET["page"] ) : null;
		$is_wpmc_screen = in_array( $page, $pages );
		
		// Check if this is a REST request specifically for Media Cleaner
		$is_wpmc_rest = false;
		$is_mcp_rest = false;
		if ( MeowKit_WPMC_Helpers::is_rest() ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			$is_wpmc_rest = strpos( $request_uri, '/media-cleaner/v1' ) !== false;
			// MCP tools are served by AI Engine, on its own route. The scan engine
			// still has to be loaded for them to run.
			$is_mcp_rest = strpos( $request_uri, '/mcp/v1' ) !== false;
		}

		// Variables
		$this->site_url = get_site_url();
		$this->multilingual = $this->is_multilingual();
		$this->languages = $this->get_languages();
		$this->current_method = $this->get_option( 'method' );
		$this->regex_file = str_replace( "MIMETYPES", $this->types, $this->regex_file );
		$this->servername = (string) wp_parse_url( $this->site_url, PHP_URL_HOST );
		$uploaddir = wp_upload_dir();
		$this->upload_path = empty( $uploaddir['error'] ) ? untrailingslashit( wp_normalize_path( $uploaddir['basedir'] ) ) : '';
		$this->upload_url = empty( $uploaddir['error'] ) ? untrailingslashit( $uploaddir['baseurl'] ) : '';
		$this->debug_logs = $this->get_option( 'debuglogs' );
		$this->is_rest = $is_wpmc_rest;
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
		$this->shortcode_analysis = !$this->get_option( 'shortcodes_disabled' );
		
		global $wpmc;
		$wpmc = $this;

		$shouldLoad = ( defined( 'WP_CLI' ) && WP_CLI ) || $is_wpmc_screen || $is_wpmc_rest || $is_mcp_rest;

		if ( ! $shouldLoad ) {
			return;
		}

		$this->runs = new Meow_WPMC_Runs( $this );
		if ( $this->is_rest || $this->is_cli || $is_mcp_rest ) $this->runs->maybe_upgrade();

		// Language
		load_plugin_textdomain( WPMC_DOMAIN, false, basename( WPMC_PATH ) . '/languages' );

		// Install hooks and engine only if they might be used
		if ( is_admin() || $this->is_rest || $this->is_cli || $is_mcp_rest ) {
			add_action( 'wpmc_initialize_parsers', array( $this, 'initialize_parsers' ), 10, 0 );
			add_filter( 'wp_unique_filename', array( $this, 'wp_unique_filename' ), 10, 3 );
			$this->engine = new Meow_WPMC_Engine( $this, $this->admin );
		}

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_WPMC_Rest( $this, $this->admin );
		}

		// MCP tools, served through AI Engine.
		if ( class_exists( 'Meow_MWAI_Core' ) || isset( $GLOBALS['mwai'] ) ) {
			new Meow_WPMC_MCP( $this );
		}

		
	}

	public function set_run_context( $run_id ) {
		if ( !$this->runs ) {
			return new WP_Error( 'wpmc_runs_unavailable', __( 'The Media Cleaner run manager is unavailable.', 'media-cleaner' ) );
		}
		$run = $this->runs->assert_writable( (int) $run_id );
		if ( is_wp_error( $run ) ) {
			return $run;
		}
		if ( $run->status === 'paused' ) {
			$run = $this->runs->resume( (int) $run_id );
			if ( is_wp_error( $run ) ) return $run;
		}
		$this->run_id = (int) $run->id;
		$this->current_method = $run->method;
		$config = json_decode( (string) $run->config, true );
		$this->run_config = is_array( $config ) ? $config : array();
		$this->shortcode_analysis = !$this->get_option( 'shortcodes_disabled' );
		return $run;
	}

	public function clear_run_context() {
		$this->run_id = 0;
		$this->run_config = array();
		$this->current_method = $this->get_option( 'method' );
		$this->shortcode_analysis = !$this->get_option( 'shortcodes_disabled' );
	}

	public function safe_do_action( $hook_name, ...$args ) {
		global $wp_filter, $wp_actions, $wp_current_filter;
		if ( empty( $wp_filter[ $hook_name ] ) || !( $wp_filter[ $hook_name ] instanceof WP_Hook ) ) {
			return;
		}
		$wp_actions[ $hook_name ] = isset( $wp_actions[ $hook_name ] ) ? $wp_actions[ $hook_name ] + 1 : 1;
		$wp_current_filter[] = $hook_name;
		try {
			foreach ( $wp_filter[ $hook_name ]->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];
					$callback_name = $this->callback_name( $function );
					$journal = null;
					if ( $hook_name === 'wpmc_scan_once' && $this->run_id > 0 && $this->runs ) {
						$journal = $this->runs->get_work( $this->run_id, 'scanOnce', $callback_name );
						if ( $journal && $journal->status === 'complete' ) continue;
						if ( !$journal ) {
							if ( !$this->runs->enqueue_work( $this->run_id, 'scanOnce', 'parser', $callback_name ) ) {
								throw new RuntimeException( sprintf( __( 'Media Cleaner could not journal parser %s.', 'media-cleaner' ), $callback_name ) );
							}
							$journal = $this->runs->get_work( $this->run_id, 'scanOnce', $callback_name );
						}
						if ( !$journal || !$this->runs->update_work( $journal->id, 'running', $journal->cursor_value ) ) {
							throw new RuntimeException( sprintf( __( 'Media Cleaner could not start parser %s safely.', 'media-cleaner' ), $callback_name ) );
						}
					}
					$accepted_args = max( 0, (int) $callback['accepted_args'] );
					$call_args = $accepted_args === 0 ? array() : array_slice( $args, 0, $accepted_args );
					$started = microtime( true );
					$guard_was_active = $this->parser_query_guard_active;
					$previous_guard_label = $this->parser_query_guard_label;
					$this->parser_query_guard_active = true;
					$this->parser_query_guard_label = $callback_name;
					if ( !$guard_was_active ) add_filter( 'query', array( $this, 'guard_parser_query' ), PHP_INT_MAX );
					try {
						$this->timeout_check();
						call_user_func_array( $function, $call_args );
						if ( $journal ) {
							$this->write_references();
							if ( !$this->runs->update_work( $journal->id, 'complete', $journal->cursor_value ) ) {
								throw new RuntimeException( sprintf( __( 'Media Cleaner could not complete parser %s safely.', 'media-cleaner' ), $callback_name ) );
							}
						}
					}
					catch ( Meow_WPMC_Transient_Exception $e ) {
						if ( $journal ) $this->runs->update_work( $journal->id, 'pending', $journal->cursor_value, $e );
						throw $e;
					}
					catch ( Throwable $e ) {
						if ( $journal ) $this->runs->update_work( $journal->id, 'failed', $journal->cursor_value, $e );
						throw new RuntimeException( sprintf( __( '%1$s failed in %2$s: %3$s', 'media-cleaner' ), $callback_name, $hook_name, $e->getMessage() ), 0, $e );
					}
					finally {
						$this->parser_query_guard_active = $guard_was_active;
						$this->parser_query_guard_label = $previous_guard_label;
						if ( !$guard_was_active ) remove_filter( 'query', array( $this, 'guard_parser_query' ), PHP_INT_MAX );
					}
					$elapsed = microtime( true ) - $started;
					$memory_limit = $this->parse_ini_bytes( ini_get( 'memory_limit' ) );
					if ( $memory_limit > 0 && memory_get_usage( true ) > $memory_limit * 0.85 ) {
						throw new Meow_WPMC_Transient_Exception( sprintf( __( '%1$s reached the safe parser memory budget in %2$s after %3$.1f seconds.', 'media-cleaner' ), $callback_name, $hook_name, $elapsed ), 5000 );
					}
				}
			}
		}
		finally {
			array_pop( $wp_current_filter );
		}
	}

	public function guard_parser_query( $query ) {
		if ( !$this->parser_query_guard_active || !is_string( $query ) ) return $query;
		$normalized = preg_replace( '/\s+/', ' ', trim( $query ) );
		if ( !preg_match( '/^(SELECT|WITH)\b/i', $normalized ) ) return $query;
		if ( preg_match( '/\bLIMIT\s+\d+/i', $normalized ) ) return $query;
		if ( preg_match( '/^SELECT\s+(?:DISTINCT\s+)?(?:COUNT|SUM|MIN|MAX|AVG|EXISTS)\s*\(/i', $normalized ) ) return $query;
		if ( preg_match( '/\b(?:ID|post_id|meta_id|term_id|term_taxonomy_id|option_id|option_name|user_id)\s*(?:=|IN\s*\()/i', $normalized ) ) return $query;
		$label = $this->parser_query_guard_label ?: 'A compatibility parser';
		$message = sprintf(
			__( '%s attempted an unbounded database query. Its Media Cleaner parser should use pagination.', 'media-cleaner' ),
			$label
		);
		if ( empty( $this->parser_query_guard_warnings[ $label ] ) ) {
			$this->parser_query_guard_warnings[ $label ] = true;
			$this->log( $message );
		}

		// Strict mode is useful for parser development, but is unsafe for normal scans because
		// WordPress and third-party APIs can legitimately issue SELECT queries without LIMIT.
		if ( apply_filters( 'wpmc_strict_parser_query_guard', false, $normalized, $label ) ) {
			throw new RuntimeException( $message );
		}
		return $query;
	}

	private function callback_name( $callback ) {
		if ( is_string( $callback ) ) return $callback;
		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			$owner = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
			return $owner . '::' . $callback[1];
		}
		return $callback instanceof Closure ? 'closure' : 'unknown callback';
	}

	public function get_run_id( $for_write = false ) {
		if ( $this->run_id > 0 ) {
			return $this->run_id;
		}
		if ( $for_write ) {
			return 0;
		}
		return $this->runs ? $this->runs->get_active_id() : max( 0, (int) get_option( Meow_WPMC_Runs::ACTIVE_RUN_OPTION, 0 ) );
	}

	public function get_nonce( $force = false ) {
		if ( !$force && !is_user_logged_in() ) {
			return null;
		}
		if ( isset( $this->nonce ) ) {
			return $this->nonce;
		}

		$this->nonce = wp_create_nonce( 'wp_rest' );
		return $this->nonce;
	}

	function initialize_parsers() {
		include_once( 'parsers.php' );
		new Meow_WPMC_Parsers();
	}

	function deepsleep( $seconds ) {
		$start_time = time();
		while( true ) {
			if ( ( time() - $start_time ) > $seconds ) {
				return false;
			}
			get_post( array( 'posts_per_page' => 50 ) );
		}
	}

	private $start_time;
	private $time_elapsed = 0;
	private $time_remaining = 0;
	private $item_scan_avg_time = 0;
	private $wordpress_init_time = 0.5;
	private $max_execution_time;
	private $items_checked = 0;
	private $items_count = 0;

	function get_max_execution_time() {
		if ( isset( $this->max_execution_time ) )
			return $this->max_execution_time;

		$this->max_execution_time = (int) ini_get( "max_execution_time" );
		// An unlimited PHP worker can still sit behind a 30 or 60 second proxy.
		// Use a conservative fallback so every REST batch remains bounded.
		if ( $this->max_execution_time === 0 )
			$this->max_execution_time = 30;
		else if ( $this->max_execution_time < 5 )
			$this->max_execution_time = 5;

		return $this->max_execution_time;
	}

	public function get_request_time_budget() {
		if ( $this->request_time_budget !== null ) return $this->request_time_budget;
		$execution_limit = $this->get_max_execution_time();
		$hard_budget = min( 30, $execution_limit );
		$hard_budget = (float) apply_filters( 'wpmc_request_time_budget', $hard_budget, $execution_limit );
		$hard_budget = max( 5, min( $hard_budget, max( 5, $execution_limit ) ) );
		$reserve = max( 2.5, min( 6, $hard_budget * 0.2 ) );
		$this->request_time_budget = max( 2.5, $hard_budget - $reserve );
		return $this->request_time_budget;
	}

	public function parse_ini_bytes( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return 0;
		}
		if ( $value === '-1' ) {
			return -1;
		}
		$unit = strtolower( substr( $value, -1 ) );
		$number = (float) $value;
		switch ( $unit ) {
			case 'g':
				$number *= 1024;
				// Fall through.
			case 'm':
				$number *= 1024;
				// Fall through.
			case 'k':
				$number *= 1024;
		}
		return (int) $number;
	}

	public function parser_budget_check( $started_at, $label = 'Parser' ) {
		$this->timeout_check();
		$budget = max( 3, min( 12, $this->get_request_time_budget() * 0.6 ) );
		if ( !$this->is_cli && microtime( true ) - (float) $started_at > $budget ) {
			throw new Meow_WPMC_Transient_Exception( sprintf( __( '%1$s reached its %2$.1f-second scan budget.', 'media-cleaner' ), sanitize_text_field( $label ), $budget ), 3000 );
		}
		$memory_limit = $this->parse_ini_bytes( ini_get( 'memory_limit' ) );
		if ( $memory_limit > 0 && memory_get_usage( true ) > $memory_limit * 0.8 ) {
			throw new Meow_WPMC_Transient_Exception( sprintf( __( '%s reached the safe parser memory limit.', 'media-cleaner' ), sanitize_text_field( $label ) ), 5000 );
		}
	}

	public function run_paged_parser( $label, $fetch_page, $process_page, $page_size = 100, $cursor_resolver = null ) {
		if ( !is_callable( $fetch_page ) || !is_callable( $process_page ) ) {
			throw new InvalidArgumentException( __( 'A paged Media Cleaner parser requires callable fetch and process functions.', 'media-cleaner' ) );
		}
		if ( $cursor_resolver !== null && !is_callable( $cursor_resolver ) ) {
			throw new InvalidArgumentException( __( 'A Media Cleaner parser cursor resolver must be callable.', 'media-cleaner' ) );
		}
		$label = sanitize_key( $label );
		$page_size = max( 10, min( 250, (int) $page_size ) );
		$work = null;
		$offset = 0;
		if ( $this->run_id > 0 && $this->runs ) {
			$work = $this->runs->get_work( $this->run_id, 'parserPages', $label );
			if ( $work && $work->status === 'complete' ) return true;
			if ( !$work ) {
				if ( !$this->runs->enqueue_work( $this->run_id, 'parserPages', 'parser', $label ) ) {
					throw new RuntimeException( sprintf( __( 'Media Cleaner could not initialize parser page state for %s.', 'media-cleaner' ), $label ) );
				}
				$work = $this->runs->get_work( $this->run_id, 'parserPages', $label );
			}
			if ( !$work ) throw new RuntimeException( sprintf( __( 'Media Cleaner could not read parser page state for %s.', 'media-cleaner' ), $label ) );
			$offset = max( 0, (int) $work->cursor_value );
		}

		$started = microtime( true );
		do {
			$this->timeout_check();
			$rows = call_user_func( $fetch_page, $offset, $page_size );
			global $wpdb;
			if ( $wpdb->last_error ) throw new RuntimeException( sprintf( __( '%1$s database error: %2$s', 'media-cleaner' ), $label, $wpdb->last_error ) );
			if ( is_wp_error( $rows ) ) throw new RuntimeException( $rows->get_error_message() );
			if ( !is_array( $rows ) ) $rows = array();
			$count = count( $rows );
			$next_offset = $offset + $count;
			if ( $count > 0 && $cursor_resolver ) {
				$resolved_cursor = call_user_func( $cursor_resolver, $rows, $offset );
				if ( !is_numeric( $resolved_cursor ) || (int) $resolved_cursor <= $offset ) {
					throw new RuntimeException( sprintf(
						__( 'Media Cleaner parser %s returned an invalid page cursor.', 'media-cleaner' ),
						$label
					) );
				}
				$next_offset = (int) $resolved_cursor;
			}
			call_user_func( $process_page, $rows );
			$this->write_references();
			$offset = $next_offset;
			$finished = $count < $page_size;
			if ( $work && !$this->runs->update_work( $work->id, $finished ? 'complete' : 'pending', $offset ) ) {
				throw new RuntimeException( sprintf( __( 'Media Cleaner could not checkpoint parser %s.', 'media-cleaner' ), $label ) );
			}
			if ( !$finished ) $this->parser_budget_check( $started, $label );
		} while ( !$finished );
		return true;
	}

	function timeout_check_start( $count ) {
		$this->start_time = microtime( true );
		$this->items_count = $count;
		$this->items_checked = 0;
		$this->item_scan_avg_time = 0;
		$this->time_elapsed = 0;
		$this->time_remaining = $this->get_request_time_budget() - ( microtime( true ) - $this->request_start_time );
		$this->get_max_execution_time();
	}

	function timeout_get_elapsed() {
		return round( $this->time_elapsed, 2 ) . 's';
	}

	function timeout_check() {
		$now = microtime( true );
		if ( empty( $this->start_time ) ) $this->start_time = $now;
		$this->time_elapsed = $now - $this->start_time;
		$this->time_remaining = $this->get_request_time_budget() - ( $now - $this->request_start_time );
		if ( $this->catch_timeout && $this->timeout_should_yield() ) {
				error_log("Media Cleaner Timeout! Check the Media Cleaner logs for more info.");
				$this->log( "😵 Timeout! Some info for debug:" );
				$this->log( "🍀 Elapsed time: $this->time_elapsed" );
				$this->log( "🍀 WP init time: $this->wordpress_init_time" );
				$this->log( "🍀 Remaining time: $this->time_remaining" );
				$this->log( "🍀 Scan time per item: $this->item_scan_avg_time" );
				$this->log( "🍀 PHP max_execution_time: $this->max_execution_time" );
				throw new Meow_WPMC_Transient_Exception( __( 'Media Cleaner paused this batch before the server execution-time or memory limit.', 'media-cleaner' ), 2000 );
		}
	}

	public function timeout_should_yield() {
		$now = microtime( true );
		$this->time_remaining = $this->get_request_time_budget() - ( $now - $this->request_start_time );
		$next_item_reserve = max( 1.25, $this->item_scan_avg_time * 1.75 );
		if ( !$this->is_cli && $this->time_remaining <= $next_item_reserve ) return true;
		$memory_limit = $this->parse_ini_bytes( ini_get( 'memory_limit' ) );
		return $memory_limit > 0 && memory_get_usage( true ) >= $memory_limit * 0.82;
	}

	function delete_attachment_related_data( $post_id ) {

		if ( empty( $post_id ) ) return;
		
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $table_name ) ) ) === $table_name ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d AND postId = %d", $run_id, $post_id ) );
		} else {
			// Table does not exist
		}
	}

	function timeout_check_additem() {
		$this->items_checked++;
		$this->time_elapsed = microtime( true ) - $this->start_time;
		$this->item_scan_avg_time = ceil( ( $this->time_elapsed / $this->items_checked ) * 10 ) / 10;
	}

	// This checks if a new uploaded filename isn't the same one as a currently
	// filename in the trash (that would cause issues)
	function wp_unique_filename( $filename, $ext, $dir ) {
		$fullpath = trailingslashit( $dir ) . $filename;
		$relativepath = $this->clean_uploaded_filename( $fullpath );
		$trashfilepath = trailingslashit( $this->get_trashdir() ) . $relativepath;
		if ( file_exists( $trashfilepath ) ) {
			$path_parts = pathinfo( $fullpath );
			$filename_noext = $path_parts['filename'];
			$new_filename = $filename_noext . '-' . date('Ymd-His', time()) . '.' . $path_parts['extension'];
			//error_log( 'POTENTIALLY TRASH PATH: ' . $trashfilepath );
			//error_log( 'POTENTIALLY NEW FILE: ' . $new_filename );
			return $new_filename;
		}
		return $filename;
	}

	function array_to_ids_or_urls( $meta, &$ids, &$urls, $recursive = false, $filters = array(), $depth = 0 ) {
		if ( $depth > 64 || !is_array( $meta ) ) {
			return;
		}
		foreach ( $meta as $k => $m ) {

			if ( is_numeric( $m ) ) {

				if ( !empty( $filters ) && is_array( $filters ) && !in_array( $k, $filters ) ) {
					continue;
				}

				// Probably a Media ID
				if ( $m > 0 )
				{
					array_push( $ids, $m );
				}
			}

			else if ( is_array( $m ) ) {
				
				
				if ( $recursive ) {
					// If it's an array, we need to go deeper
					$this->array_to_ids_or_urls( $m, $ids, $urls, true, $filters, $depth + 1 );
				}

			}
			else if ( !empty( $m ) ) {

				if ( !empty( $filters ) && is_array( $filters ) && !in_array( $k, $filters ) ) {
					continue;
				}

				if ( is_string( $m ) && preg_match( '/^[\d\s,]+$/', $m ) && strpos( $m, ',' ) !== false ) {
					// If this is a string that contains only digits, spaces, and commas, and contains at least one comma
					// it is probably a list of IDs. So we should explode it to make an array
					// Remove any spaces

					$m = str_replace( ' ', '', $m );
					$m = explode( ',', $m );

					foreach ( $m as $mv ) {
						if ( is_numeric( $mv ) && !in_array( (int)$mv, $ids ) ) {
							array_push( $ids, (int)$mv );
						}
					}

					continue;
				}

				// If it's a string, maybe it's a file (with an extension)
				if ( preg_match( $this->regex_file, $m ) )
				{
					$clean_url = $this->clean_url( $m );
					array_push( $urls, $clean_url );
				}
			}
		}
	}

	function get_favicon() {
			// Yoast SEO plugin
			$vals = get_option( 'wpseo_titles' );
			if ( !empty( $vals ) && isset( $vals['company_logo'] ) ) {
				$url = $vals['company_logo'];
				if ( $this->is_url( $url ) )
					return $this->clean_url( $url );
			}
		}

	function get_all_shortcodes_attributes( $html, $ids_attr = array(), $urls_attr = array() ) {
		// Get all the shortcodes from html, and check for each attributes of the shortcode if it is an ID or a URL and add the value in an array to return
		$urls_values = array();
		$ids_values = array();

		$pattern = get_shortcode_regex();
		if ( preg_match_all( '/'. $pattern .'/s', $html, $matches ) )
		{
			foreach( $matches[0] as $key => $value) {
				// $matches[3] return the shortcode attribute as string
				// replace space with '&' for parse_str() function
				$get = str_replace(" ", "&" , trim( $matches[3][$key] ) );
				$get = str_replace('"', '' , $get );
				parse_str( $get, $sub_output );

				foreach ( $sub_output as $attr_key => $attr_value ) {

					if ( in_array( $attr_key, $ids_attr ) ) {
						if ( is_numeric( $attr_value ) && !in_array( (int)$attr_value, $ids_values ) ) {
							array_push( $ids_values, (int)$attr_value );
						}

						// In case of separated by commas
						else if ( strpos( $attr_value, ',' ) !== false ) {
							$attr_value = str_replace(' ', '', $attr_value );
							$pieces = explode( ',', $attr_value );
							foreach ( $pieces as $pval ) {
								if ( is_numeric( $pval ) && !in_array( (int)$pval, $ids_values ) ) {
									array_push( $ids_values, (int)$pval );
								}
							}
						}
					}

					else if ( in_array( $attr_key, $urls_attr ) ) {
						if ( !empty( trim( $attr_value ) ) && !in_array( trim( $attr_value ), $urls_values ) && !is_numeric( trim( $attr_value ) ) && strpos( trim( $attr_value ), 'http' ) !== false ) {
							array_push( $urls_values, trim( $this->clean_url( $attr_value ) ) );
						}
					}
				}
			}
		}

		// Remove duplicates
		$urls_values = array_unique( $urls_values );
		$ids_values  = array_unique( $ids_values );

		// Return the values
		$values = array(
			'urls' => $urls_values,
			'ids' => $ids_values
		);

		return $values;

	}



		/**
		 * Recursively transforms a string with WordPress shortcodes into a
		 * hierarchical tree structure (an Abstract Syntax Tree).
		 *
		 * @param string $content The string containing the shortcodes.
		 * @return array An array of nodes, where each node can be a shortcode with its
		 * own 'children' array, or a simple text node.
		 */
		function nested_shortcodes_to_array(string $content, $depth = 0): array
		{
			if ( $depth > 32 ) {
				return array();
			}
			$nodes = [];
			$last_pos = 0;

			$pattern = '/\\[' . '(\\[?)' . '([\w-]+)' . '(?![\\w-])' . '(' . '[^\\]\\/]*' . '(?:' . '\\/(?!\\])' . '[^\\]\\/]*' . ')*?' . ')' . '(?:' . '(\\/)' . '\\]' . '|' . '\\]' . '(?:' . '(' . '[^\\[]*+' . '(?:' . '\\[(?!\\/\\2\\])' . '[^\\[]*+' . ')*+' . ')' . '\\[\\/\\2\\]' . ')?' . ')' . '(\\]?)/s';

			// preg_match_all with PREG_OFFSET_CAPTURE is key to tracking positions.
			if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
				foreach ($matches as $match) {
					// Get the position and content of the full shortcode match
					$match_start_pos = $match[0][1];
					$match_full_string = $match[0][0];
					$match_end_pos = $match_start_pos + strlen($match_full_string);

					// 1. Capture any text that appeared *before* this shortcode
					if ($match_start_pos > $last_pos) {
						$text_content = substr($content, $last_pos, $match_start_pos - $last_pos);
						if (trim($text_content) !== '') {
							$nodes[] = [
								'type' => 'text',
								'content' => $text_content
							];
						}
					}

					// 2. Process the shortcode match itself
					$tag = $match[2][0];
					$attributes_string = $match[3][0];
					// Use isset since self-closing tags won't have inner content (group 5)
					$inner_content = isset($match[5]) ? $match[5][0] : null;

					// Parse attributes from the attribute string
					$parsed_attributes = [];
					if (preg_match_all('/([\w-]+)\s*=\s*(["\'])([^"\']*?)\2/', $attributes_string, $attr_matches)) {
						foreach ($attr_matches[1] as $attr_index => $key) {
							$parsed_attributes[$key] = $attr_matches[3][$attr_index];
						}
					}

					$shortcode_node = [
						'type' => 'shortcode',
						'tag' => $tag,
						'attributes' => $parsed_attributes,
					];

					// 3. This is the recursion!
					// If there is inner content, parse it with the same function.
					if ($inner_content !== null) {
						$children = $this->nested_shortcodes_to_array( $inner_content, $depth + 1 );
						if (!empty($children)) {
							$shortcode_node['children'] = $children;
						}
					}

					$nodes[] = $shortcode_node;

					// Update the last position to the end of the current match
					$last_pos = $match_end_pos;
				}
			}

			// 4. Capture any remaining text after the very last shortcode
			if ($last_pos < strlen($content)) {
				$text_content = substr($content, $last_pos);
				if (trim($text_content) !== '') {
					$nodes[] = [
						'type' => 'text',
						'content' => $text_content
					];
				}
			}

			return $nodes;
		}



	
		function get_shortcode_attributes( $shortcode_tag, $post ) {
		if ( has_shortcode( $post->post_content, $shortcode_tag ) ) {
			$output = array();
			//get shortcode regex pattern wordpress function
			$pattern = get_shortcode_regex( [ $shortcode_tag ] );
			if (   preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches ) )
			{
					$keys = array();
					$output = array();
					foreach( $matches[0] as $key => $value) {
							// $matches[3] return the shortcode attribute as string
							// replace space with '&' for parse_str() function
							$get = str_replace(" ", "&" , trim( $matches[3][$key] ) );
							$get = str_replace('"', '' , $get );
							parse_str( $get, $sub_output );

							//get all shortcode attribute keys
							$keys = array_unique( array_merge(  $keys, array_keys( $sub_output )) );
							$output[] = $sub_output;
					}
					if ( $keys && $output ) {
							// Loop the output array and add the missing shortcode attribute key
							foreach ($output as $key => $value) {
									// Loop the shortcode attribute key
									foreach ($keys as $attr_key) {
											$output[$key][$attr_key] = isset( $output[$key] )  && isset( $output[$key] ) ? $output[$key][$attr_key] : NULL;
									}
									//sort the array key
									ksort( $output[$key]);
							}
					}
			}
			return $output;
		}
		else {
				return false;
		}
	}

	// Simply use regex to get URLs from a string return an array of URLs
	function get_urls_from_string( $string ) {
		$this->assert_analysis_document_size( $string );
		$urls = array();
		// Replace the sanitized urls with the real ones to be sure to get them in the regex
		$string = str_replace( '\\', '', $string );

		$patterns = array(
			// Full URLs with protocol
			'/(https?:\/\/[^\s\"\'\>\<\?\#]+\.(' . $this->types . '))/i',
			
			// Relative URLs starting with /wp-content/uploads or /uploads (without protocol)
			'/(\/(?:wp-content\/)?uploads\/[^\s\"\'\>\<\?\#]+\.(' . $this->types . '))/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $string, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					$clean_url = $this->clean_url( $match );
					$urls[] = $clean_url;
				}
			}
		}

		return array_unique( $urls );
	}

	function get_urls_from_html( $html ) {
		if ( empty( $html ) ) {
			return array();
		}
		$this->assert_analysis_document_size( $html );


		// Proposal/fix by @copytrans
		// Discussion: https://wordpress.org/support/topic/bug-in-core-php/#post-11647775
		// Modified by Jordy again in 2021 for those who don't have MB enabled
		if ( function_exists( 'mb_encode_numericentity' ) ) {
			$convmap = [0x80, 0xffff, 0, 0xffff];
			$html = mb_encode_numericentity( $html, $convmap, 'UTF-8' );
		} else {
			$html = preg_replace_callback(
				'/[\x80-\xFF]/',
				function( $match ) {
					return '&#' . ord( $match[0] ) . ';';
				},
				$html
			);
		}

		// Remove any base64 src from the HTML to prevent regex from getting stuck and crashing the site
		// Handles both proper (data:image/...) and malformed (image/jpeg;base64,...) base64
		// Also handles HTML-encoded quotes (&quot;) and multiline base64
		$html = preg_replace( '/src=["\'](?:data:)?(?:image|video|audio)\/[^"\']+;base64,[^"\']*["\']/', '', $html );
		$html = preg_replace( '/src=&quot;(?:data:)?(?:image|video|audio)\/[^&]+;base64,[^&]*&quot;/', '', $html );
		// Catch any remaining base64 data that might cause regex issues (greedy catch-all)
		$html = preg_replace( '/;base64,[a-zA-Z0-9+\/=\s]{1000,}/', '', $html );


		// Resolve src-set and shortcodes
		if ( $this->get_shortcode_analysis() ) {
			$html = do_shortcode( $html );
			$this->assert_analysis_document_size( $html );
		}

		// Create the DOM Document
		if ( !class_exists("DOMDocument") ) {
			error_log( 'Media Cleaner: The DOM extension for PHP is not installed.' );
			throw new Error( 'The DOM extension for PHP is not installed.' );
		}

		
		if ( empty( $html ) ) {
			return array();
		}

		$previous_libxml_errors = libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		@$dom->loadHTML( $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_errors );
		$results = array();

		// <meta> tags in <head> area
		$metas = $dom->getElementsByTagName( 'meta' );
		foreach ( $metas as $meta ) {
			$property = $meta->getAttribute( 'property' );
			if ( $property == 'og:image' || $property == 'og:image:secure_url' || $property == 'twitter:image' ) {
				$url = $meta->getAttribute( 'content' );
				if ( $this->is_url( $url ) ) {
					$src = $this->clean_url( $url );
					if ( !empty( $src ) ) {
						array_push( $results, $src );
					}
				}
			}
		}

		

		// Iframe documents are not fetched while scanning. Fetching page-controlled
		// URLs here made analysis depend on networking, allowed recursive documents,
		// and could execute expensive remote rendering paths.


		// Images: src, srcset
		$imgs = $dom->getElementsByTagName( 'img' );
		foreach ( $imgs as $img ) {
			//error_log($img->getAttribute('src'));
			$src = $this->clean_url( $img->getAttribute('src') );
    			array_push( $results, $src );
			$srcset = $img->getAttribute('srcset');
			if ( !empty( $srcset ) ) {
				$setImgs = explode( ',', trim( $srcset ) );
				foreach ( $setImgs as $setImg ) {
					$finalSetImg = explode( ' ', trim( $setImg ) );
					if ( is_array( $finalSetImg ) ) {
						array_push( $results, $this->clean_url( $finalSetImg[0] ) );
					}
				}
			}
		}

		// Videos: src, poster, and attached file
		$videos = $dom->getElementsByTagName( 'video' );
		foreach ($videos as $video) {
			// Get src attribute
			$raw_video_src = $video->getAttribute( 'src' );
			$src = $this->clean_url( $raw_video_src );
			if ( !empty( $src ) ) {
				$video_id = $this->custom_attachment_url_to_postid( $raw_video_src );

				$attached_file = get_post_meta( $video_id, '_wp_attached_file', true );
				if ( !empty( $attached_file ) ) {
					array_push( $results, $attached_file );
				}
			}
			
			// Get poster attribute
			$raw_poster_src = $video->getAttribute( 'poster' );
			$poster = $this->clean_url( $raw_poster_src );
			if ( !empty( $poster ) ) {
				$poster_id = $this->custom_attachment_url_to_postid( $raw_poster_src );
				
				$attached_file = get_post_meta( $poster_id, '_wp_attached_file', true );
				if ( !empty( $attached_file ) ) {
					array_push( $results, $attached_file );
				}
			}

		}

		// Audios: src
		$audios = $dom->getElementsByTagName( 'audio' );
		foreach ( $audios as $audio ) {
			//error_log($audio->getAttribute('src'));
			$src = $this->clean_url( $audio->getAttribute('src') );
    	array_push( $results, $src );
		}

		// Sources: src
		$audios = $dom->getElementsByTagName( 'source' );
		foreach ( $audios as $audio ) {
			//error_log($audio->getAttribute('src'));
			$src = $this->clean_url( $audio->getAttribute('src') );
    	array_push( $results, $src );
		}

		// Links, href
		$urls = $dom->getElementsByTagName( 'a' );
		foreach ( $urls as $url ) {
			$url_href = $url->getAttribute('href'); // mm change
			if ( $this->is_url( $url_href ) ) { // mm change
				$src = $this->clean_url( $url_href );  // mm change
				if ( !empty( $src ) )
					array_push( $results, $src );
			}
		}

		// <link> tags in <head> area
		$urls = $dom->getElementsByTagName( 'link' );
		foreach ( $urls as $url ) {
			$url_href = $url->getAttribute( 'href' );
			if ( $this->is_url( $url_href ) ) {
				$src = $this->clean_url( $url_href );
				if ( !empty( $src ) ) {
					array_push( $results, $src );
				}
			}
		}

		// PDF
		preg_match_all( "/((https?:\/\/)?[^\\&\#\[\] \"\?]+\.pdf)/", $html, $res );
		if ( !empty( $res ) && isset( $res[1] ) && count( $res[1] ) > 0 ) {
			foreach ( $res[1] as $url ) {
				if ( $this->is_url( $url ) )
					array_push( $results, $this->clean_url( $url ) );
			}
		}

		// Background images
		preg_match_all( "/url\(\'?\"?((https?:\/\/)?[^\\&\#\[\] \"\?]+\.(jpe?g|gif|png))\'?\"?/", $html, $res );
		if ( !empty( $res ) && isset( $res[1] ) && count( $res[1] ) > 0 ) {
			foreach ( $res[1] as $url ) {
				if ( $this->is_url( $url ) )
					array_push( $results, $this->clean_url( $url ) );
			}
		}

		return $results;
	}

	private function assert_analysis_document_size( $value ) {
		$limit = max( 1024 * 1024, (int) apply_filters( 'wpmc_max_analysis_document_bytes', 8 * 1024 * 1024 ) );
		if ( is_string( $value ) && strlen( $value ) > $limit ) {
			throw new RuntimeException( sprintf(
				__( 'A content document is larger than Media Cleaner\'s safe analysis limit of %s.', 'media-cleaner' ),
				size_format( $limit )
			) );
		}
	}

	/**
	 * 
	 *  Get the IDs and URLs from the blocks of a post.
	 * 
	 * @param string $html The HTML content of the post.
	 * @param string $prefix The prefix of the blocks to look for.
	 * @param array $keys The keys to look for in the blocks.
	 * @param array $urls The array to fill with the URLs.
	 * @param array $ids The array to fill with the IDs.
	 * 
	 */
	function get_from_blocks( $html, $prefix, $keys, &$urls, &$ids ) {

		$blocks = parse_blocks( $html );

		if ( ! is_array( $blocks )  || ! isset( $blocks[0] ) ) {
			return;
		}
		

		foreach ( $blocks as $block ) {

			if ( strpos( $block['blockName'], $prefix ) === false ) {
				continue;
			}

			$this->array_to_ids_or_urls( $block, $ids, $urls, true, $keys );

		}
				
		
	}
	// Parse a meta, visit all the arrays, look for the attributes, fill $ids and $urls arrays
	// If rawMode is enabled, it will not check if the value is an ID or an URL, it will just returns it in URLs
	function get_from_meta( $meta, $lookFor, &$ids, &$urls, $rawMode = false, $depth = 0 ) {
		if ( $depth > 64 || ( !is_array( $meta ) && !is_object( $meta) ) ) {
			return;
		}
		foreach ( $meta as $key => $value ) {
			if ( is_object( $value ) || is_array( $value ) )
				$this->get_from_meta( $value, $lookFor, $ids, $urls, $rawMode, $depth + 1 );
			else if ( in_array( $key, $lookFor ) ) {
				if ( empty( $value ) ) {
					continue;
				}
				else if ( $rawMode ) {
					array_push( $urls, $value );
				}
				else if ( is_numeric( $value ) ) {
					// It this an ID?
					array_push( $ids, $value );
				}
				else {
					if ( $this->is_url( $value ) ) {
						// Is this an URL?
						array_push( $urls, $this->clean_url( $value ) );
					}
					else {
						// Is this an array of IDs, encoded as a string? (like "20,13")
						$pieces = explode( ',', $value );
						foreach ( $pieces as $pval ) {
							if ( is_numeric( $pval ) ) {
								array_push( $ids, $pval );
							}
						}
					}
				}
			}
		}
	}

	function get_images_from_themes( &$ids, &$urls ) {
		// USE CURRENT THEME AND WP API
		$ch = get_custom_header();
		if ( !empty( $ch ) && !empty( $ch->url ) ) {
			array_push( $urls, $this->clean_url( $ch->url ) );
		}
		if ( !empty( $ch ) && !empty( $ch->thumbnail_url ) && $this->is_url( $ch->thumbnail_url ) ) {
			array_push( $urls, $this->clean_url( $ch->thumbnail_url ) );
		}
		if ( !empty( $ch ) && !empty( $ch->attachment_id ) ) {
			array_push( $ids, $ch->attachment_id );
		}
		$cl = get_custom_logo();
		if ( $this->is_url( $cl ) ) {
			$urls = array_merge( $this->get_urls_from_html( $cl ), $urls );
		}
		$custom_logo = get_theme_mod( 'custom_logo' );
		if ( !empty( $custom_logo ) && is_numeric( $custom_logo ) ) {
			array_push( $ids, (int)$custom_logo );
		}
		$si = get_site_icon_url();
		if ( $this->is_url( $si ) ) {
			array_push( $urls, $this->clean_url( $si ) );
		}
		$si_id = get_option( 'site_icon' );
		if ( !empty( $si_id ) && is_numeric( $si_id ) ) {
			array_push( $ids, (int)$si_id );
		}
		$cd = get_background_image();
		if ( $this->is_url( $cd ) ) {
			array_push( $urls, $this->clean_url( $cd ) );
		}
		$photography_hero_image = get_theme_mod( 'photography_hero_image' );
		if ( !empty( $photography_hero_image ) ) {
			array_push( $ids, $photography_hero_image );
		}
		$author_profile_picture = get_theme_mod( 'author_profile_picture' );
		if ( !empty( $author_profile_picture ) ) {
			array_push( $ids, $author_profile_picture );
		}
		if ( function_exists ( 'get_uploaded_header_images' ) ) {
			$header_images = get_uploaded_header_images();
			if ( !empty( $header_images ) ) {
				foreach ( $header_images as $hi ) {
					if ( !empty ( $hi['attachment_id'] ) ) {
						array_push( $ids, $hi['attachment_id'] );
					}
				}
			}
		}
	}

	#region LOGS

	function log( $data = null, $force = false ) {
		if ( !$this->debug_logs && !$force )
			return;

		$php_logs = $this->get_option( 'php_error_logs' );
		$log_file_path = $this->get_logs_path();
		if ( !$log_file_path ) return false;
		if ( file_exists( $log_file_path ) && filesize( $log_file_path ) > 5 * 1024 * 1024 ) {
			if ( file_exists( $log_file_path . '.1' ) ) @unlink( $log_file_path . '.1' );
			@rename( $log_file_path, $log_file_path . '.1' );
		}

		$fh = @fopen( $log_file_path, 'a' );
		if ( !$fh ) { return false; }
		$date = date( "Y-m-d H:i:s" );
		if ( is_null( $data ) ) {
			fwrite( $fh, "\n" );
		}
		else {
			$message = is_scalar( $data ) ? (string) $data : wp_json_encode( $data );
			fwrite( $fh, "$date: {$message}\n" );
			if ( $php_logs ) {
				error_log( "[MEDIA CLEANER] " . $message );
			}
		}
		fclose( $fh );
		return true;
	}

	//WPMC_PREFIX

	function get_logs_path() {
		$private_root = $this->ensure_private_root();
		if ( is_wp_error( $private_root ) ) return false;
		$log_dir = trailingslashit( $private_root ) . 'logs';
		if ( !is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
		$path = trailingslashit( $log_dir ) . 'media-cleaner.log';
		$old_path = $this->get_option( 'logs_path' );
		$legacy_path = wp_normalize_path( WPMC_PATH . '/logs/media-cleaner.log' );
		$old_normalized = is_string( $old_path ) ? wp_normalize_path( $old_path ) : '';
		if ( $old_normalized === $legacy_path && $old_normalized !== $path && is_file( $old_normalized ) ) {
			if ( !@rename( $old_normalized, $path ) ) {
				if ( @copy( $old_normalized, $path ) ) @unlink( $old_normalized );
			}
		}
		if ( !file_exists( $path ) ) {
			@touch( $path );
		}
		if ( $old_path !== $path ) {
			$options = $this->get_all_options();
			$options['logs_path'] = $path;
			$this->update_options( $options );
		}
		return $path;
	}
	

	function get_logs() {
		$log_file_path = $this->get_logs_path();

		if ( !$log_file_path || !file_exists( $log_file_path ) ) {
			return __( 'No logs found.', 'media-cleaner' );
		}

		$size = filesize( $log_file_path );
		$bytes = min( 512 * 1024, max( 0, (int) $size ) );
		$handle = fopen( $log_file_path, 'rb' );
		if ( !$handle ) return __( 'No logs found.', 'media-cleaner' );
		if ( $bytes < $size ) fseek( $handle, -$bytes, SEEK_END );
		$content = $bytes > 0 ? fread( $handle, $bytes ) : '';
		fclose( $handle );
		if ( $bytes < $size ) {
			$first_newline = strpos( $content, "\n" );
			$content = $first_newline === false ? '' : substr( $content, $first_newline + 1 );
		}
		$lines = explode( "\n", $content );
		$lines = array_filter( $lines );
		$lines = array_slice( array_reverse( $lines ), 0, 2000 );
		$content = implode( "\n", $lines );
		return $content;
	}

	function clear_logs() {
		$logPath = $this->get_logs_path();
		if ( $logPath && file_exists( $logPath ) ) {
			unlink( $logPath );
		}

		$options = $this->get_all_options();
		$options['logs_path'] = null;
		$this->update_options( $options );
	}

	#endregion

	/**
	 *
	 * HELPERS
	 *
	 */

	private function random_ascii_chars($length = 8)
	{
		$characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
		$characters_length = count($characters);
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}

		return $random_string;
	}

	private function get_private_root() {
		$secret = get_option( 'wpmc_trash_secret', null );
		if ( !$secret ) {
			$candidate = wp_generate_password( 32, false, false );
			$secret = add_option( 'wpmc_trash_secret', $candidate, '', false ) ? $candidate : get_option( 'wpmc_trash_secret' );
		}
		if ( !$secret ) $secret = wp_salt( 'auth' );
		$parent = defined( 'WPMC_TRASH_DIR' ) ? dirname( WPMC_TRASH_DIR ) : WP_CONTENT_DIR;
		$base = trailingslashit( $parent ) . '.media-cleaner-private-' . substr( hash( 'sha256', $secret ), 0, 20 );
		return untrailingslashit( wp_normalize_path( $base ) );
	}

	private function protect_private_directory( $root ) {
		$guards = array(
			trailingslashit( $root ) . 'index.php' => "<?php\nhttp_response_code( 404 );\nexit;\n",
			trailingslashit( $root ) . '.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>Require all denied</IfModule>\n<IfModule !mod_authz_core.c>Deny from all</IfModule>\n",
			trailingslashit( $root ) . 'web.config' => "<?xml version=\"1.0\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>",
		);
		foreach ( $guards as $path => $content ) {
			if ( !file_exists( $path ) && @file_put_contents( $path, $content ) === false ) return false;
		}
		return true;
	}

	private function ensure_private_root() {
		$root = $this->get_private_root();
		if ( is_link( $root ) ) {
			return new WP_Error( 'wpmc_private_storage_unsafe', __( 'Media Cleaner private storage cannot be a symbolic link.', 'media-cleaner' ) );
		}
		if ( !is_dir( $root ) && !wp_mkdir_p( $root ) ) {
			return new WP_Error( 'wpmc_private_storage_unavailable', __( 'Media Cleaner could not create its private storage directory.', 'media-cleaner' ) );
		}
		if ( !$this->protect_private_directory( $root ) ) return new WP_Error( 'wpmc_private_storage_unprotected', __( 'Media Cleaner could not protect its private storage directory.', 'media-cleaner' ) );
		return $root;
	}

	function get_trashdir() {
		$trash = defined( 'WPMC_TRASH_DIR' ) ? untrailingslashit( wp_normalize_path( WPMC_TRASH_DIR ) ) : trailingslashit( $this->get_private_root() ) . 'trash';

		$legacy = trailingslashit( $this->upload_path ) . 'wpmc-trash';
		$trash_is_public = $trash === $this->upload_path || strpos( $trash, trailingslashit( $this->upload_path ) ) === 0;
		if ( is_dir( $legacy ) || is_link( $legacy ) ) {
			if ( $trash_is_public || file_exists( $trash ) || is_link( $legacy ) || !wp_mkdir_p( dirname( $trash ) ) || !@rename( $legacy, $trash ) ) {
				$this->trash_migration_error = new WP_Error(
					'wpmc_trash_migration_failed',
					__( 'The old public Media Cleaner trash could not be moved to private storage. Cleanup is blocked until that directory can be migrated.', 'media-cleaner' )
				);
			}
		}
		return $trash;
	}

	private function ensure_trash_directory() {
		$trash = $this->get_trashdir();
		if ( is_wp_error( $this->trash_migration_error ) ) return $this->trash_migration_error;
		$private_root = $this->ensure_private_root();
		if ( is_wp_error( $private_root ) ) return $private_root;
		if ( $trash === $this->upload_path || strpos( $trash, trailingslashit( $this->upload_path ) ) === 0 ) {
			return new WP_Error( 'wpmc_public_trash_directory', __( 'Media Cleaner quarantine must be outside the public uploads directory.', 'media-cleaner' ) );
		}
		if ( !is_dir( $trash ) && !wp_mkdir_p( $trash ) ) {
			return new WP_Error( 'wpmc_trash_unavailable', __( 'Media Cleaner could not create its private trash directory.', 'media-cleaner' ) );
		}
		$trash_real = realpath( $trash );
		$upload_real = realpath( $this->upload_path );
		$trash_real = $trash_real ? untrailingslashit( wp_normalize_path( $trash_real ) ) : '';
		$upload_real = $upload_real ? untrailingslashit( wp_normalize_path( $upload_real ) ) : '';
		if ( $upload_real && ( $trash_real === $upload_real || strpos( $trash_real, trailingslashit( $upload_real ) ) === 0 ) ) {
			return new WP_Error( 'wpmc_public_trash_directory', __( 'Media Cleaner quarantine resolves inside the public uploads directory.', 'media-cleaner' ) );
		}
		if ( is_link( $trash ) || !$this->protect_private_directory( $trash ) ) {
			return new WP_Error( 'wpmc_trash_unprotected', __( 'Media Cleaner quarantine is unsafe or could not be protected.', 'media-cleaner' ) );
		}
		return wp_normalize_path( $trash );
	}

	public function prepare_private_storage() {
		return $this->ensure_trash_directory();
	}

	public function test_quarantine_roundtrip() {
		$trash = $this->ensure_trash_directory();
		if ( is_wp_error( $trash ) ) return $trash;
		$source = tempnam( $this->upload_path, '.wpmc-roundtrip-' );
		if ( !$source || file_put_contents( $source, 'media-cleaner-storage-test' ) === false ) {
			if ( $source && file_exists( $source ) ) @unlink( $source );
			return new WP_Error( 'wpmc_storage_test_create_failed', __( 'Media Cleaner could not create a storage test file.', 'media-cleaner' ) );
		}
		$relative = '.wpmc-roundtrip-' . wp_generate_uuid4();
		$quarantine = $this->resolve_trash_path( $relative );
		$returned = $source . '.returned';
		$success = !is_wp_error( $quarantine ) && @rename( $source, $quarantine ) && @rename( $quarantine, $returned ) && @unlink( $returned );
		foreach ( array( $source, is_wp_error( $quarantine ) ? null : $quarantine, $returned ) as $path ) {
			if ( $path && file_exists( $path ) ) @unlink( $path );
		}
		return $success ? true : new WP_Error( 'wpmc_storage_roundtrip_failed', __( 'Files cannot be moved safely into and back out of Media Cleaner quarantine. Check mount points and permissions.', 'media-cleaner' ) );
	}

	public function resolve_trash_path( $relative_path, $must_exist = false ) {
		$relative = $this->normalize_upload_relative_path( $relative_path );
		if ( is_wp_error( $relative ) ) {
			return $relative;
		}
		$root = $this->ensure_trash_directory();
		if ( is_wp_error( $root ) ) {
			return $root;
		}
		$root_real = realpath( $root );
		if ( !$root_real ) {
			return new WP_Error( 'wpmc_trash_unavailable', __( 'Media Cleaner trash is unavailable.', 'media-cleaner' ) );
		}
		$root_real = untrailingslashit( wp_normalize_path( $root_real ) );
		$candidate = $root_real . ( $relative === '' ? '' : '/' . $relative );
		if ( is_link( $candidate ) ) {
			return new WP_Error( 'wpmc_trash_path_invalid', __( 'The trash path is unsafe.', 'media-cleaner' ) );
		}
		if ( $must_exist && !file_exists( $candidate ) ) {
			return new WP_Error( 'wpmc_trash_item_missing', __( 'The requested item no longer exists in Media Cleaner trash.', 'media-cleaner' ) );
		}
		if ( file_exists( $candidate ) ) {
			$resolved = wp_normalize_path( realpath( $candidate ) );
			if ( is_link( $candidate ) || ( $resolved !== $root_real && strpos( $resolved, trailingslashit( $root_real ) ) !== 0 ) ) {
				return new WP_Error( 'wpmc_trash_path_invalid', __( 'The trash path is unsafe.', 'media-cleaner' ) );
			}
		}
		else {
			$ancestor = dirname( $candidate );
			while ( !file_exists( $ancestor ) && dirname( $ancestor ) !== $ancestor ) {
				$ancestor = dirname( $ancestor );
			}
			$ancestor_real = realpath( $ancestor );
			$ancestor_real = $ancestor_real ? untrailingslashit( wp_normalize_path( $ancestor_real ) ) : '';
			if ( $ancestor_real !== $root_real && strpos( $ancestor_real, trailingslashit( $root_real ) ) !== 0 ) {
				return new WP_Error( 'wpmc_trash_path_invalid', __( 'The trash path is unsafe.', 'media-cleaner' ) );
			}
		}
		return $candidate;
	}

	function get_trashurl() {
		// Trash is intentionally outside the public uploads URL.
		return null;
	}

	function clean_ob(){
		$disabled = $this->get_option( 'output_buffer_cleaning_disabled' );
		$ob_content = ob_get_contents();
		if ( is_string( $ob_content ) && trim( $ob_content ) !== '' ) {

			if ( $disabled ) {
				$this->log( "🚨 If the server's response was broken, try to let Output Buffer Cleaning enabled." );
				return;
			}

			$this->log( "🧹 The response is broken due to output buffering, it will be cleaned." );
			$this->log( "📄 Output buffer content: " . $ob_content );

			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}
	}

	/**
	 *
	 * I18N RELATED HELPERS
	 *
	 */

	function is_multilingual() {
		return function_exists( 'icl_get_languages' );
	}

	function get_languages() {
		$results = array();
		if ( $this->is_multilingual() ) {
			$languages = icl_get_languages();
			foreach ( $languages as $language ) {
				if ( isset( $language['code'] ) ) {
					array_push( $results, $language['code'] );
				}
				else if ( isset( $language['language_code'] ) ) {
					array_push( $results, $language['language_code'] );
				}
			}
		}
		return $results;
	}

	function get_translated_media_ids( $mediaId ) {
		$translated_ids = array();
		foreach ( $this->languages as $language ) {
			$id = apply_filters( 'wpml_object_id', $mediaId, 'attachment', false, $language );
			if ( !empty( $id ) ) {
				array_push( $translated_ids, $id );
			}
		}
		return $translated_ids;
	}

	/**
	 *
	 * DELETE / SCANNING / RESET
	 *
	 */

	function recover_file( $path, &$did_move = null ) {
		$did_move = false;
		$relative = $this->normalize_upload_relative_path( $path );
		if ( is_wp_error( $relative ) ) return $relative;
		$original_path = $this->resolve_upload_path( $relative );
		$trash_path = $this->resolve_trash_path( $relative );
		if ( is_wp_error( $original_path ) ) return $original_path;
		if ( is_wp_error( $trash_path ) ) return $trash_path;
		if ( !file_exists( $trash_path ) ) {
			return file_exists( $original_path ) ? true : new WP_Error( 'wpmc_recovery_source_missing', __( 'The file exists in neither uploads nor Media Cleaner trash.', 'media-cleaner' ) );
		}
		if ( file_exists( $original_path ) ) {
			return new WP_Error( 'wpmc_recovery_collision', __( 'A file already exists at the recovery destination.', 'media-cleaner' ) );
		}
		if ( !wp_mkdir_p( dirname( $original_path ) ) || !@rename( $trash_path, $original_path ) ) {
			return new WP_Error( 'wpmc_recovery_move_failed', __( 'The file could not be moved out of Media Cleaner trash.', 'media-cleaner' ) );
		}
		$did_move = true;
		return true;
	}

	function recover( $id, $operation_manifest = array() ) {
		$staged = $this->results_staged_error();
		if ( $staged ) return $staged;
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$issue = $this->get_issue( $id );

		if ( empty( $issue ) ) {
			return new WP_Error( 'wpmc_issue_missing', __( 'The selected Media Cleaner result no longer exists.', 'media-cleaner' ) );
		}
		if ( empty( $operation_manifest['identity_validated'] ) ) {
			$identity = $this->validate_issue_manifest( $issue );
			if ( is_wp_error( $identity ) ) return $identity;
		}

		// Files
		if ( $issue->type === 0 ) {
			$did_move = false;
			$moved = $this->recover_file( $issue->path, $did_move );
			if ( is_wp_error( $moved ) ) return $moved;
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET deleted = 0 WHERE id = %d", $id ) );
			if ( $updated === false ) {
				if ( $did_move ) $this->trash_file( $issue->path );
				return new WP_Error( 'wpmc_recovery_database_failed', __( 'The file was moved back because its database state could not be updated.', 'media-cleaner' ) );
			}
			$this->log( "✅ Recovered {$issue->path}." );
			return true;
		}
		// Media
		else if ( $issue->type === 1 ) {

			$paths = $this->get_paths_from_attachment( $issue->postId );
			$file_manifest = json_decode( (string) $issue->manifest, true );
			$file_manifest = is_array( $file_manifest ) ? $file_manifest : array();
			$recovered_paths = array();
			foreach ( $paths as $path ) {
				if ( array_key_exists( $path, $file_manifest ) && $file_manifest[ $path ] === null ) continue;
				$did_move = false;
				$result = $this->recover_file( $path, $did_move );
				if ( is_wp_error( $result ) ) {
					foreach ( array_reverse( $recovered_paths ) as $recovered_path ) {
						$this->trash_file( $recovered_path );
					}
					return $result;
				}
				if ( $did_move ) $recovered_paths[] = $path;
			}
			$previous_post_type = get_post_type( $issue->postId );
			$post_result = wp_update_post( array( 'ID' => $issue->postId, 'post_type' => 'attachment' ), true );
			if ( is_wp_error( $post_result ) || !$post_result ) {
				foreach ( array_reverse( $recovered_paths ) as $recovered_path ) {
					$this->trash_file( $recovered_path );
				}
				return is_wp_error( $post_result ) ? $post_result : new WP_Error( 'wpmc_recovery_post_failed', __( 'The attachment record could not be restored.', 'media-cleaner' ) );
			}
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET deleted = 0 WHERE id = %d", $id ) );
			if ( $updated === false ) {
				if ( $previous_post_type && $previous_post_type !== 'attachment' ) {
					wp_update_post( array( 'ID' => $issue->postId, 'post_type' => $previous_post_type ) );
				}
				foreach ( array_reverse( $recovered_paths ) as $recovered_path ) {
					$this->trash_file( $recovered_path );
				}
				return new WP_Error( 'wpmc_recovery_database_failed', __( 'The attachment was restored, but Media Cleaner could not update its result record.', 'media-cleaner' ) );
			}
			$this->log( "✅ Recovered Media #{$issue->postId}." );
			return true;
		}
		return new WP_Error( 'wpmc_issue_type_invalid', __( 'The selected Media Cleaner result has an unsupported type.', 'media-cleaner' ) );
	}

	function trash_file( $fileIssuePath, &$did_move = null ) {
		$did_move = false;
		$relative = $this->normalize_upload_relative_path( $fileIssuePath );
		if ( is_wp_error( $relative ) ) return $relative;
		$original_path = $this->resolve_upload_path( $relative );
		$trash_path = $this->resolve_trash_path( $relative );
		if ( is_wp_error( $original_path ) ) return $original_path;
		if ( is_wp_error( $trash_path ) ) return $trash_path;
		if ( !file_exists( $original_path ) ) {
			return file_exists( $trash_path ) ? true : new WP_Error( 'wpmc_delete_source_missing', __( 'The file exists in neither uploads nor Media Cleaner trash.', 'media-cleaner' ) );
		}
		if ( is_dir( $original_path ) || is_link( $original_path ) ) {
			return new WP_Error( 'wpmc_delete_unsafe_path', __( 'Media Cleaner will not move directories or symbolic links.', 'media-cleaner' ) );
		}
		if ( file_exists( $trash_path ) ) {
			return new WP_Error( 'wpmc_trash_collision', __( 'A different file already exists at the trash destination.', 'media-cleaner' ) );
		}
		if ( !wp_mkdir_p( dirname( $trash_path ) ) || !@rename( $original_path, $trash_path ) ) {
			return new WP_Error( 'wpmc_trash_move_failed', __( 'The file could not be moved into Media Cleaner trash.', 'media-cleaner' ) );
		}
		$did_move = true;
		$this->clean_dir( dirname( $original_path ) );
		return true;
	}

	function repair( $id ) {
		$repair = $this->get_repair( $id );
		if ( empty( $repair ) ) {
			return new WP_Error( 'wpmc_repair_missing', __( 'The selected repair result no longer exists.', 'media-cleaner' ) );
		}
		$full_path = $this->get_full_upload_path( $repair->path );
		if ( !$full_path || !is_file( $full_path ) || is_link( $full_path ) ) {
			return new WP_Error( 'wpmc_repair_file_missing', __( 'The file to repair is missing or unsafe.', 'media-cleaner' ) );
		}
		$relative_path = $this->clean_uploaded_filename( $full_path );
		global $wpdb;
		$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$relative_path
		) );
		$filetype = wp_check_filetype( basename( $full_path ), null );
		$wp_upload_dir = wp_upload_dir();
		$attachment = array(
			'guid'           => trailingslashit( $wp_upload_dir['baseurl'] ) . str_replace( '%2F', '/', rawurlencode( $relative_path ) ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $full_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = $existing_id ?: wp_insert_attachment( $attachment, $full_path, 0, true );
		if ( is_wp_error( $attach_id ) || !$attach_id ) {
			return is_wp_error( $attach_id ) ? $attach_id : new WP_Error( 'wpmc_repair_insert_failed', __( 'WordPress could not create the attachment record.', 'media-cleaner' ) );
		}

		if ( wp_attachment_is_image( $attach_id ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $full_path );
			if ( is_wp_error( $attach_data ) || !is_array( $attach_data ) || !wp_update_attachment_metadata( $attach_id, $attach_data ) ) {
				return is_wp_error( $attach_data ) ? $attach_data : new WP_Error( 'wpmc_repair_metadata_failed', __( 'WordPress could not generate attachment metadata. The original file was left untouched.', 'media-cleaner' ) );
			}
		}

		$table_name = $wpdb->prefix . "mclean_scan";
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d AND (id = %d OR parentId = %d)", $this->get_run_id(), $id, $id ) );
		if ( $deleted === false ) {
			return new WP_Error( 'wpmc_repair_result_update_failed', __( 'The attachment was repaired, but Media Cleaner could not update its result record.', 'media-cleaner' ) );
		}
		$this->log( "✅ Repaired {$repair->path}." );
		return true;
	}

	function ignore( $id, $ignore ) {
		$staged = $this->results_staged_error();
		if ( $staged ) return $staged;
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$issue = $this->get_issue( $id );

		if ( empty( $issue ) ) {
			$this->log( "🚫 Issue #{$id} does not exist. Cannot ignore this." );
			return false;
		}

		if ( !$ignore ) {
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET ignored = 0 WHERE id = %d", $id ) );
		}
		else {
			// If it is in trash, recover it
			if ( $issue->deleted ) {
				$recovered = $this->recover( $id );
				if ( is_wp_error( $recovered ) || $recovered !== true ) {
					return is_wp_error( $recovered ) ? $recovered : new WP_Error( 'wpmc_ignore_recovery_failed', __( 'The item could not be recovered before it was ignored.', 'media-cleaner' ) );
				}
			}
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET ignored = 1 WHERE id = %d", $id ) );
		}
		return $updated === false ? new WP_Error( 'wpmc_ignore_database_failed', __( 'Media Cleaner could not update the ignored state.', 'media-cleaner' ) ) : true;
	}

	function clean_dir( $dir ) {
		$root = realpath( $this->upload_path );
		$current = realpath( $dir );
		if ( !$root || !$current || is_link( $dir ) ) return;
		$root = untrailingslashit( wp_normalize_path( $root ) );
		$current = untrailingslashit( wp_normalize_path( $current ) );
		if ( $current === $root || strpos( $current, trailingslashit( $root ) ) !== 0 ) return;
		try {
			$is_empty = !( new FilesystemIterator( $current, FilesystemIterator::SKIP_DOTS ) )->valid();
		}
		catch ( Throwable $e ) {
			return;
		}
		if ( $is_empty && @rmdir( $current ) ) {
			$this->clean_dir( dirname( $current ) );
		}
	}

	function get_issue( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$issue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND run_id = %d", $id, $run_id ), OBJECT );
		if ( empty( $issue ) ) {
			return false;
		}
		$issue->id = (int)$issue->id;
		$issue->postId = (int)$issue->postId;
		$issue->type = (int)$issue->type;
		$issue->deleted = (int)$issue->deleted;
		$issue->ignored = (int)$issue->ignored;
		$issue->path = stripslashes( $issue->path );
		return $issue;
	}

	function get_repair( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$repair = $wpdb->get_row( $wpdb->prepare( "SELECT
				main.id AS id,
				main.path AS path,
				GROUP_CONCAT(child.id) AS child_ids
			FROM
				$table_name AS main
				LEFT JOIN
					$table_name AS child ON main.id = child.parentId AND child.run_id = main.run_id
					WHERE main.id = %d AND main.run_id = %d
				GROUP BY main.id, main.path", $id, $run_id
			), OBJECT );
		if ( empty( $repair ) ) {
			return false;
		}

		// If $repair->path is null or empty return false
		if ( empty( $repair->path ) ) {
			$this->log( "🚫 Repair #{$id} does not have a path. Cannot repair this." );
			return false;
		}


		$repair->id = (int)$repair->id;
		$regex = "^(.*)(\\s\\(\\+.*)$";
		$repair->path = preg_replace( '/' . $regex . '/i', '$1', stripslashes( $repair->path ) );
		$repair->child_ids = $repair->child_ids ? explode( ',', $repair->child_ids ) : [];
		return $repair;
	}

	function get_issues_to_repair( $order_by = 'id', $order = 'asc', $search = '', $skip = 0, $limit = 10 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();

		$search_clause = '';
		if ( !empty( $search ) ) {
			$search_clause = $wpdb->prepare("AND main.path LIKE %s", ( '%' . $search . '%' ));
		}

		$order_clause = 'ORDER BY main.id ASC';
		if ( $order_by === 'path' ) {
			$order_clause = 'ORDER BY main.path ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}
		else if ( $order_by === 'issue' ) {
			$order_clause = 'ORDER BY main.issue ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}
		else if ( $order_by === 'size' ) {
			$order_clause = 'ORDER BY main.size ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT
				main.id AS id,
				main.path AS path,
				GROUP_CONCAT(child.id) AS child_ids,
				GROUP_CONCAT(child.path) AS child_paths,
				main.type AS type,
				main.postId AS postId,
				main.size AS size,
				main.ignored AS ignored,
				main.deleted AS deleted,
				main.issue AS issue
			FROM
				$table_name AS main
				LEFT JOIN
					$table_name AS child ON main.id = child.parentId AND child.run_id = main.run_id
				WHERE
					main.run_id = %d AND main.path IS NOT NULL AND main.parentId IS NULL
				AND main.deleted = 0 AND main.ignored = 0
				AND main.type = 0
				$search_clause
			GROUP BY main.id
			$order_clause
			LIMIT %d, %d;
			", $run_id, $skip, $limit ) );

		return $result;
	}

	function get_repair_ids ( $search = '', $cursor = 0, $limit = 100 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$cursor = absint( $cursor );
		$limit = max( 1, min( 100, absint( $limit ) ) );

		$search_clause = '';
		if ( !empty( $search ) ) {
			$search_clause = $wpdb->prepare( "AND main.path LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		}

		return $wpdb->get_col( $wpdb->prepare( "SELECT main.id
			FROM $table_name AS main
				WHERE
					main.run_id = %d
					AND main.id > %d
					AND main.path IS NOT NULL
					AND main.parentId IS NULL
					AND main.deleted = 0 AND main.ignored = 0 AND main.type = 0
				$search_clause
			ORDER BY main.id ASC
			LIMIT %d", $run_id, $cursor, $limit )
		);
	}

	function get_stats_of_issues_to_repair( $search = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();

		$search_clause = '';
		if ( !empty( $search ) ) {
			$search_clause = $wpdb->prepare("AND main.path LIKE %s", ( '%' . $search . '%' ));
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT
			COUNT(id) AS entries,
			SUM(size) AS size
			FROM (
				SELECT
					COUNT(DISTINCT main.id) as id,
					main.size as size
				FROM
					$table_name AS main
				LEFT JOIN
					$table_name AS child ON main.id = child.parentId AND child.run_id = main.run_id
				WHERE
					main.run_id = %d AND main.path IS NOT NULL AND main.parentId IS NULL AND main.deleted = 0 AND main.ignored = 0 AND main.type = 0
					$search_clause
				GROUP BY main.id
			) t;
			", $run_id ) );
	}

	function get_count_of_issues_to_repair( $search ) {
		$stats = $this->get_stats_of_issues_to_repair( $search );
		return $stats->entries;
	}

	function delete( $id, $operation_manifest = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$issue = $this->get_issue( $id );

		if ( empty( $issue ) ) {
			return new WP_Error( 'wpmc_issue_missing', __( 'The selected Media Cleaner result no longer exists.', 'media-cleaner' ) );
		}
		if ( empty( $operation_manifest['identity_validated'] ) ) {
			$identity = $this->validate_issue_manifest( $issue );
			if ( is_wp_error( $identity ) ) return $identity;
		}

		$regex = "^(.*)(\\s\\(\\+.*)$";
		$issue->path = preg_replace( '/' . $regex . '/i', '$1', $issue->path ); // remove " (+ 6 files)" from path
		$skip_trash = $this->get_option( 'skip_trash' );
		$was_deleted = isset( $operation_manifest['initial_deleted'] ) ? (bool) $operation_manifest['initial_deleted'] : $issue->deleted === 1;

		$staged = $this->results_staged_error();
		if ( $staged ) return $staged;

		// Trashing something new is only as safe as the analysis behind it, so it needs
		// results from a finished scan of this version. Emptying the trash does not:
		// that file was already set aside on purpose, and refusing here would strand
		// it. This is the one place deletion is allowed or refused, so the REST API,
		// MCP and WP-CLI all get the same answer. The capability is checked by the
		// callers, since WP-CLI has no current user.
		if ( !$was_deleted && ( !$this->runs || !$this->runs->cleanup_allowed() ) ) {
			return new WP_Error( 'wpmc_cleanup_needs_scan',
				__( 'Media Cleaner needs the results of a completed scan from this version before it can delete anything. Run a scan first. Your trash is untouched and can still be recovered or emptied.', 'media-cleaner' ) );
		}

		if ( $issue->type === 0 ) {
			if ( $was_deleted ) {
				$trash_path = $this->resolve_trash_path( $issue->path );
				if ( is_wp_error( $trash_path ) ) return $trash_path;
				if ( file_exists( $trash_path ) && ( is_dir( $trash_path ) || is_link( $trash_path ) || !@unlink( $trash_path ) ) ) {
					return new WP_Error( 'wpmc_permanent_delete_failed', __( 'The file could not be permanently removed from Media Cleaner trash.', 'media-cleaner' ) );
				}
				$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id = %d", $id ) );
				return $deleted === false ? new WP_Error( 'wpmc_delete_database_failed', __( 'The file was removed, but its Media Cleaner record could not be deleted.', 'media-cleaner' ) ) : true;
			}
			else if ( $skip_trash ) {
				$original_path = $this->resolve_upload_path( $issue->path );
				if ( is_wp_error( $original_path ) ) return $original_path;
				if ( file_exists( $original_path ) && ( is_dir( $original_path ) || is_link( $original_path ) || !@unlink( $original_path ) ) ) {
					return new WP_Error( 'wpmc_permanent_delete_failed', __( 'The file could not be permanently deleted.', 'media-cleaner' ) );
				}
				$this->clean_dir( dirname( $original_path ) );
				$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id = %d", $id ) );
				return $deleted === false ? new WP_Error( 'wpmc_delete_database_failed', __( 'The file was removed, but its Media Cleaner record could not be deleted.', 'media-cleaner' ) ) : true;
			}
			$did_move = false;
			$trashed = $this->trash_file( $issue->path, $did_move );
			if ( is_wp_error( $trashed ) ) return $trashed;
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET deleted = 1, ignored = 0, time = NOW() WHERE id = %d", $id ) );
			if ( $updated === false ) {
				if ( $did_move ) $this->recover_file( $issue->path );
				return new WP_Error( 'wpmc_trash_database_failed', __( 'The file was restored because its Media Cleaner state could not be updated.', 'media-cleaner' ) );
			}
			return true;
		}

		if ( $issue->type === 1 ) {
			if ( $was_deleted || $skip_trash  ) {
				if ( $issue->deleted === 1 ) {
					$recovered = $this->recover( $id );
					if ( is_wp_error( $recovered ) || $recovered !== true ) {
						return is_wp_error( $recovered ) ? $recovered : new WP_Error( 'wpmc_media_recovery_failed', __( 'The attachment could not be restored before permanent deletion.', 'media-cleaner' ) );
					}
				}
				$deleted_attachment = get_post( $issue->postId ) ? wp_delete_attachment( $issue->postId, true ) : true;
				if ( !$deleted_attachment ) {
					return new WP_Error( 'wpmc_attachment_delete_failed', __( 'WordPress could not permanently delete the attachment.', 'media-cleaner' ) );
				}
				$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id = %d", $id ) );
				return $deleted === false ? new WP_Error( 'wpmc_delete_database_failed', __( 'The attachment was removed, but its Media Cleaner record could not be deleted.', 'media-cleaner' ) ) : true;
			}

			$paths = $this->get_paths_from_attachment( $issue->postId );
			$file_manifest = json_decode( (string) $issue->manifest, true );
			$file_manifest = is_array( $file_manifest ) ? $file_manifest : array();
			$trashed_paths = array();
			foreach ( $paths as $path ) {
				if ( array_key_exists( $path, $file_manifest ) && $file_manifest[ $path ] === null ) continue;
				$did_move = false;
				$result = $this->trash_file( $path, $did_move );
				if ( is_wp_error( $result ) ) {
					foreach ( array_reverse( $trashed_paths ) as $trashed_path ) {
						$this->recover_file( $trashed_path );
				}
					return $result;
				}
				if ( $did_move ) $trashed_paths[] = $path;
			}
			$previous_post_type = get_post_type( $issue->postId );
			$post_result = wp_update_post( array( 'ID' => $issue->postId, 'post_type' => 'wmpc-trash' ), true );
			if ( is_wp_error( $post_result ) || !$post_result ) {
				foreach ( array_reverse( $trashed_paths ) as $trashed_path ) {
					$this->recover_file( $trashed_path );
				}
				return is_wp_error( $post_result ) ? $post_result : new WP_Error( 'wpmc_attachment_trash_failed', __( 'The attachment record could not be moved to Media Cleaner trash.', 'media-cleaner' ) );
			}
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET deleted = 1, ignored = 0, time = NOW() WHERE id = %d", $id ) );
			if ( $updated === false ) {
				if ( $previous_post_type && $previous_post_type !== 'wmpc-trash' ) {
					wp_update_post( array( 'ID' => $issue->postId, 'post_type' => $previous_post_type ) );
				}
				foreach ( array_reverse( $trashed_paths ) as $trashed_path ) {
					$this->recover_file( $trashed_path );
				}
				return new WP_Error( 'wpmc_trash_database_failed', __( 'The attachment was restored because its Media Cleaner state could not be updated.', 'media-cleaner' ) );
			}
			return true;
		}
		return new WP_Error( 'wpmc_issue_type_invalid', __( 'The selected Media Cleaner result has an unsupported type.', 'media-cleaner' ) );
	}

	function force_trash( $initialize = false, $limit = 100 ) {
		global $wpdb;
		$staged = $this->results_staged_error();
		if ( $staged ) return $staged;
		$run_id = $this->get_run_id();
		$table_name = $wpdb->prefix . 'mclean_scan';
		$tracked_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE run_id = %d AND deleted = 1", $run_id ) );
		if ( $tracked_items > 0 ) {
			return new WP_Error(
				'wpmc_force_trash_has_tracked_items',
				__( 'Retry the tracked trash items individually before removing untracked quarantine files.', 'media-cleaner' ),
				array( 'status' => 409 )
			);
		}
		$phase = 'cleanuptrash';
		$limit = max( 1, min( 100, (int) $limit ) );
		$trash = $this->ensure_trash_directory();
		if ( is_wp_error( $trash ) ) return $trash;

		if ( $initialize ) {
			if ( !$this->runs->clear_work( $run_id, $phase ) || !$this->runs->enqueue_work( $run_id, $phase, 'directory', '' ) ) {
				return new WP_Error( 'wpmc_trash_queue_failed', __( 'Media Cleaner could not initialize the trash cleanup queue.', 'media-cleaner' ) );
			}
		}
		if ( $this->runs->failed_work_count( $run_id, $phase ) > 0 ) {
			return new WP_Error( 'wpmc_trash_cleanup_failed', __( 'A trash item could not be removed. Result records were preserved.', 'media-cleaner' ) );
		}

		$work = $this->runs->next_work( $run_id, $phase );
		if ( !$work ) {
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d AND deleted = 1", $run_id ) );
			if ( $deleted === false ) {
				return new WP_Error( 'wpmc_trash_database_failed', __( 'Trash files were removed, but Media Cleaner could not update its result records.', 'media-cleaner' ) );
			}
			$this->runs->clear_work( $run_id, $phase );
			return array( 'finished' => true, 'processed' => 0, 'pending' => 0 );
		}

		$relative = (string) $work->target_key;
		$directory = $this->resolve_trash_path( $relative, true );
		if ( is_wp_error( $directory ) || !is_dir( $directory ) || is_link( $directory ) ) {
			$error = is_wp_error( $directory ) ? $directory : new WP_Error( 'wpmc_trash_directory_unsafe', __( 'A queued trash directory is missing or unsafe.', 'media-cleaner' ) );
			$this->runs->update_work( $work->id, 'failed', 0, $error );
			return $error;
		}

		$processed = 0;
		try {
			$iterator = new FilesystemIterator( $directory, FilesystemIterator::SKIP_DOTS );
			foreach ( $iterator as $entry ) {
				if ( $entry->isLink() ) {
					throw new RuntimeException( __( 'Media Cleaner will not remove a symbolic link from trash.', 'media-cleaner' ) );
				}
				if ( $entry->isDir() ) {
					$child = ltrim( $relative . '/' . $entry->getFilename(), '/' );
					if ( !$this->runs->enqueue_work( $run_id, $phase, 'directory', $child ) ) {
						throw new RuntimeException( __( 'A trash subdirectory could not be queued.', 'media-cleaner' ) );
					}
					if ( !$this->runs->update_work( $work->id, 'waiting', 0 ) ) return new WP_Error( 'wpmc_trash_queue_failed', __( 'Media Cleaner could not checkpoint a trash directory.', 'media-cleaner' ) );
					return array( 'finished' => false, 'processed' => $processed, 'pending' => $this->runs->pending_work_count( $run_id, $phase ) );
				}
				if ( !@unlink( $entry->getPathname() ) ) {
					throw new RuntimeException( sprintf( __( 'The trash file %s could not be removed.', 'media-cleaner' ), $entry->getFilename() ) );
				}
				$processed++;
				if ( $processed >= $limit ) break;
			}
		}
		catch ( Throwable $e ) {
			$error = new WP_Error( 'wpmc_trash_item_delete_failed', $e->getMessage() );
			$this->runs->update_work( $work->id, 'failed', 0, $error );
			return $error;
		}

		$has_entries = false;
		try {
			$check = new FilesystemIterator( $directory, FilesystemIterator::SKIP_DOTS );
			$has_entries = $check->valid();
		}
		catch ( UnexpectedValueException $e ) {
			$has_entries = true;
		}
		if ( $has_entries ) {
			if ( !$this->runs->update_work( $work->id, 'running', 0 ) ) return new WP_Error( 'wpmc_trash_queue_failed', __( 'Media Cleaner could not checkpoint a trash batch.', 'media-cleaner' ) );
		}
		else {
			if ( $relative !== '' && !@rmdir( $directory ) ) {
				$error = new WP_Error( 'wpmc_trash_directory_delete_failed', __( 'An empty trash directory could not be removed.', 'media-cleaner' ) );
				$this->runs->update_work( $work->id, 'failed', 0, $error );
				return $error;
			}
			if ( !$this->runs->update_work( $work->id, 'complete', 0 ) ) return new WP_Error( 'wpmc_trash_queue_failed', __( 'Media Cleaner could not complete a trash directory checkpoint.', 'media-cleaner' ) );
			$parent = $relative === '' ? '' : dirname( $relative );
			if ( !$this->runs->wake_work( $run_id, $phase, $parent === '.' ? '' : $parent ) ) return new WP_Error( 'wpmc_trash_queue_failed', __( 'Media Cleaner could not resume the parent trash directory.', 'media-cleaner' ) );
		}

		return array(
			'finished' => false,
			'processed' => $processed,
			'pending' => $this->runs->pending_work_count( $run_id, $phase ),
		);
	}

	/**
	 *
	 * SCANNING / RESET
	 *
	 */

	function add_reference_url( $urlOrUrls, $type, $origin = null, $extra = null ) {
		$urlOrUrls = !is_array( $urlOrUrls ) ? array( $urlOrUrls ) : $urlOrUrls;
		foreach ( $urlOrUrls as $url ) {
			// With files, we need both filename without resolution and filename with resolution, it's important
			// to make sure the original file is not deleted if a size exists for it.
			// With media, all URLs should be without resolution to make sure it matches Media.
			$no_res_url = $this->clean_url_from_resolution( $url );

			$this->add_reference( null, $url, $type, $origin, $extra );
			$this->add_reference( 0, $no_res_url, $type, $origin, $extra );

			if ( $this->multilingual ) {
				if ( $this->current_method == 'media' ) {
					$id  = $this->get_id_from_clean_url( $no_res_url, false );
					if( $id ) $this->add_reference_id( $id, $type, $origin, $extra );
				}
			}
		}
	}

	/**
	 * Add an issue to the mclean_scan table.
	 * 
	 * @param string $path The path to the file (relative to uploads).
	 * @param string $issue The issue code/type.
	 * @param int|null $postId Optional post ID related to the issue.
	 */
	function add_issue( $path, $issue, $postId = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$clean_path = $this->clean_uploaded_filename( $path );
		if ( $clean_path === '' ) throw new RuntimeException( __( 'Media Cleaner received an invalid issue path.', 'media-cleaner' ) );
		$path_hash = hash( 'sha256', $clean_path );
		$run_id = $this->get_run_id( true );
		if ( $run_id < 1 ) {
			throw new RuntimeException( __( 'An active scan run is required before adding issues.', 'media-cleaner' ) );
		}
		$filepath = $this->resolve_upload_path( $clean_path );
		if ( is_wp_error( $filepath ) ) throw new RuntimeException( $filepath->get_error_message() );
		$filesize = file_exists( $filepath ) ? filesize( $filepath ) : 0;
		$manifest = $this->build_file_manifest( array( $clean_path ) );
		if ( is_wp_error( $manifest ) ) throw new RuntimeException( $manifest->get_error_message() );

		// Check if this issue already exists
		$existing = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table_name WHERE run_id = %d AND path_hash = %s AND path = %s AND issue = %s",
			$run_id, $path_hash, $clean_path, $issue
		) );
		
		if ( $existing ) {
			return; // Issue already exists
		}

		// Find potential parent
		$potentialParentPath = $this->clean_url_from_resolution( $clean_path );
		$parentId = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE run_id = %d AND path_hash = %s AND path = %s", $run_id, hash( 'sha256', $potentialParentPath ), $potentialParentPath ) );
		$parentId = $parentId ? (int)$parentId : null;

		$inserted = $wpdb->insert( $table_name,
				array(
					'run_id' => $run_id,
				'time' => current_time('mysql'),
				'type' => 0,
				'postId' => $postId,
					'path' => $clean_path,
					'path_hash' => $path_hash,
					'manifest' => wp_json_encode( $manifest ),
				'size' => $filesize,
				'issue' => $issue,
				'parentId' => $parentId
			)
		);
		if ( $inserted === false ) throw new RuntimeException( sprintf( __( 'Media Cleaner could not store an issue: %s', 'media-cleaner' ), $wpdb->last_error ) );
	}

	function add_reference_id( $idOrIds, $type, $origin = null, $extra = null ) {
		$idOrIds = !is_array( $idOrIds ) ? array( $idOrIds ) : $idOrIds;
		foreach ( $idOrIds as $id ) {
			$this->add_reference( $id, "", $type, $origin );
			if ( $this->multilingual ) {
				$translatedIds = $this->get_translated_media_ids( (int)$id );
				
				// Test for WPML
				// if ( $id ===  '350') {
				// 	$translatedIds = $this->get_translated_media_ids( (int)$id );
				// 	$count = count($translatedIds);
				// 	error_log( "${id} => ${count}" );
				// }

				if ( !empty( $translatedIds ) ) {
					foreach ( $translatedIds as $translatedId ) {
						$this->add_reference( $translatedId, "", $type, $origin );
					}
				}
			}
		}
	}


	// Returns the reference with the type, origin, related to a Media ID it is referenced
	public function get_reference_for_media_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id();
		$refs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE run_id = %d AND mediaId = %d", $run_id, $id ), OBJECT );
		if ( empty( $refs ) ) {
			return false;
		}
		$ref = $refs[0];
		$ref->id = (int)$ref->id;
		$ref->mediaId = (int)$ref->mediaId;
		$ref->originType = stripslashes( $ref->originType );
		$ref->origin = stripslashes( $ref->origin );
		$ref->parentId = empty( $ref->parentId ) ? null : (int)$ref->parentId;
		return $ref;
	}

	// Return the references related to a Post ID
	public function get_references_for_post_id( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id();
		$refs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE run_id = %d AND originType LIKE %s", $run_id, "%[$id]" ), OBJECT );
		if ( empty( $refs ) ) {
			return [];
		}
		$fresh_refs = array();
		foreach ( $refs as $ref ) {
			$mediaId = (int)$ref->mediaId > 0 ? (int)$ref->mediaId : null;
			if ( !$mediaId && !empty( $ref->mediaUrl ) ) {
				$mediaId = $this->find_media_id_from_file( $ref->mediaUrl, false );
				$mediaId = !empty( $mediaId ) ? (int)$mediaId : null;
			}
			if ( !$mediaId ) {
				continue;
			}
			array_push( $fresh_refs, [
				'id' => (int)$ref->id,
				'mediaId' => $mediaId,
				'mediaUrl' => $ref->mediaUrl,
				'originType' => $ref->originType,
				'origin' => $ref->origin,
				'parentId' => empty( $ref->parentId ) ? null : (int)$ref->parentId,
			] );
		}
		return $fresh_refs;
	}

	// The references are actually not being added directly in the DB, they are being pushed
	// into a cache ($this->refcache), then written to the database via write_references().
	private function add_reference( $id, $url, $type, $origin = null, $extra = null ) {
		$type = substr( sanitize_text_field( (string) $type ), 0, 191 );
		if ( $origin !== null && !is_scalar( $origin ) ) $origin = wp_json_encode( $origin );
		// The origin is only a label for where the reference was found. If malformed content
		// makes it huge, trim it rather than dropping the reference: the reference marks a file
		// as used, and losing it could let a used file be deleted.
		if ( is_string( $origin ) && strlen( $origin ) > 8192 ) {
			$origin = substr( $origin, 0, 8192 );
		}
		if ( !empty( $id ) ) {
			$this->queue_reference( array( 'id' => $id, 'url' => null, 'type' => $type, 'origin' => $origin ) );
		}
		if ( !empty( $url ) ) {
			// A real media path is never this large, so an oversized "URL" is a parser
			// mis-extraction (a data: URI, a concatenated path blob, etc.). It can never match
			// a real file, so skip it instead of aborting the whole scan — the same treatment
			// the http/javascript URLs get just below. This is what stranded Divi users in 7.2.
			if ( is_string( $url ) && strlen( $url ) > 4096 ) {
				return;
			}
			// The URL shouldn't contain http, https, javascript at the beginning (and there are probably many more cases)
			// The URL must be cleaned before being passed as a reference.
			if ( substr( $url, 0, 5 ) === "http:" || substr( $url, 0, 6 ) === "https:" || substr( $url, 0, 11 ) === "javascript:" ) {
				return;
			}
			$this->queue_reference( array( 'id' => null, 'url' => $url, 'type' => $type, 'origin' => $origin ) );
		}
	}

	private function queue_reference( $reference ) {
		$this->refcache[] = $reference;
		if ( count( $this->refcache ) >= 250 ) $this->write_references();
	}

	function insert_references($entries)
	{
		global $wpdb;
		$table = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id( true );
		if ( $run_id < 1 ) {
			throw new RuntimeException( __( 'An active scan run is required before adding references.', 'media-cleaner' ) );
		}

		$refs_buffer = $this->get_option( 'refs_buffer' );
		if ( empty( $refs_buffer ) || $refs_buffer < 1 ) {
			$refs_buffer = 500;
		}

		$values = array();
		$place_holders = array();
		$entry_count = 0;

		$entries = array_unique( $entries, SORT_REGULAR );

		foreach ( $entries as $value ) {
			$origin = isset( $value['origin'] ) ? $value['origin'] : null;
			if ( !is_null( $value['id'] ) ) {
				// Media Reference
				$hash = md5( $value['id'] . '|' . $value['type'] . '|' . $origin );
				array_push( $values, $run_id, $value['id'], $value['type'], $origin, $hash );
				$place_holders[] = "('%d', '%d', NULL, NULL, '%s', '%s', NULL, '%s')";

				if ( $this->debug_logs ) {
					$this->log( "＋ Media #{$value['id']} (as ID)" );
				}
				$entry_count++;
			}
			else if ( !is_null( $value['url'] ) ) {
				// File Reference
				$parent_id = isset( $value['parentId'] ) ? (int) $value['parentId'] : null;
				$hash = md5( '|' . $value['url'] . '|' . $value['type'] . '|' . $origin . '|' . $parent_id );
				$url_hash = hash( 'sha256', $value['url'] );
				if ( $parent_id !== null ) {
					array_push( $values, $run_id, $value['url'], $url_hash, $value['type'], $origin, $parent_id, $hash );
					$place_holders[] = "('%d', NULL, '%s', '%s', '%s', '%s', '%d', '%s')";
					if ( $this->debug_logs ) {
						$this->log( "＋ {$value['url']} (as URL) (ParentID: {$value['parentId']})" );
					}
				}
				else {
					array_push( $values, $run_id, $value['url'], $url_hash, $value['type'], $origin, $hash );
					$place_holders[] = "('%d', NULL, '%s', '%s', '%s', '%s', NULL, '%s')";
					if ( $this->debug_logs ) {
						$this->log( "＋ {$value['url']} (as URL)" );
					}
				}
				$entry_count++;
			}

			// Flush to DB when buffer is full
			if ( $entry_count >= $refs_buffer ) {
				$this->log( "Flushing $entry_count references to the database..." );
				$this->flush_references_to_db( $table, $values, $place_holders );
				$values = array();
				$place_holders = array();
				$entry_count = 0;
			}
		}

		// Flush remaining entries
		if ( !empty( $values ) ) {
			$this->log( "Flushing remaining $entry_count references to the database..." );
			$this->flush_references_to_db( $table, $values, $place_holders );
		}
	}

	function flush_references_to_db( $table, $values, $place_holders ) {
		global $wpdb;
		if ( empty( $values ) ) {
			return;
		}
		$query = "INSERT IGNORE INTO $table (run_id, mediaId, mediaUrl, mediaUrl_hash, originType, origin, parentId, ref_hash) VALUES ";
		$query .= implode( ', ', $place_holders );
		$prepared = $wpdb->prepare( "$query ", $values );
		$result = $wpdb->query( $prepared );
		if ( $result === false ) {
			throw new RuntimeException( sprintf( __( 'Could not store Media Cleaner references: %s', 'media-cleaner' ), $wpdb->last_error ) );
		}
	}

	function reset_progress() {
		// Reset the progress by deleting the transient.
		delete_transient( $this->progress_key );
	}

	function clear_step_progress() {
		// Clear step progress when scanning completes
		delete_transient( $this->progress_key );
	}

	function save_progress( $step, $data = array() ) {
		if ( $this->run_id > 0 && $this->runs ) {
			// Target lists are intentionally not checkpointed. They can be enormous and
			// are rebuilt from deterministic server-side cursors when a scan resumes.
			unset( $data['targets'], $data['doneTargets'] );
			$saved = $this->runs->checkpoint( $this->run_id, $step, $data );
			if ( is_wp_error( $saved ) ) throw new RuntimeException( $saved->get_error_message() );
			if ( $saved !== true ) throw new RuntimeException( __( 'Media Cleaner could not persist the scan checkpoint.', 'media-cleaner' ) );
			return true;
		}
		// Save progress with step and optional data
		// Data can include type, limit, limitSize, and any other progress information
		$progress = array(
			'step' => $step,
			'time' => time(),
			'data' => $data
		);

		return set_transient( $this->progress_key, $progress, 0 );
	}

	function get_progress() {
		if ( $this->runs ) {
			$run = $this->runs->get_resumable();
			if ( $run ) {
				$public = $this->runs->to_array( $run );
				return array(
					'runId' => $public['id'],
					'status' => $public['status'],
					'step' => $public['phase'],
					'time' => strtotime( $public['updated_at'] . ' UTC' ),
					'data' => $public['checkpoint'],
					'errors' => $public['errors'],
				);
			}
		}
		return get_transient( $this->progress_key );
	}

	function get_step_progress() {
		$options = $this->get_all_options();
		return isset( $options['step_progress'] ) ? $options['step_progress'] : null;
	}

	// The cache containing the references is wrote to the DB.
	function write_references() {
		global $wpdb;
		$table = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id( true );

		$potential_parents = array();
		$potential_children = array();

		foreach ( $this->refcache as $value ) {
			$potentialParentPath = !is_null( $value['url'] ) ? $this->clean_url_from_resolution( $value['url'] ) : null;
			if ( $potentialParentPath === $value['url'] ) {
				$potential_parents[] = $value;
			}
			else {
				$potential_children[] = $value;
			}
		}

		$this->insert_references( $potential_parents );

		// Resolve parentId for potential children
		foreach ( $potential_children as &$child ) {
			$potentialParentPath = $this->clean_url_from_resolution( $child['url'] );
			$parentId = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE run_id = %d AND mediaUrl_hash = %s AND mediaUrl = %s", $run_id, hash( 'sha256', $potentialParentPath ), $potentialParentPath ) );
			if ( !empty( $parentId ) ) {
				$child['parentId'] = (int)$parentId;
			}
		}

		// Insert potential children with resolved parentIds
		$this->insert_references( $potential_children );
		$this->refcache = array();
	}

	function check_is_ignore( $file ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$clean_file = $this->clean_uploaded_filename( $file );
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name
			WHERE run_id = %d AND ignored = 1 AND path_hash = %s AND path = %s",
			$run_id,
			hash( 'sha256', $clean_file ),
			$clean_file
		) );
		if ( $count > 0 ) {
			$this->log( "🚫 Could not trash $file." );
		}
		return ($count > 0);
	}

	function find_media_id_from_file( $file, $doLog ) {
		global $wpdb;
		$postmeta_table_name = $wpdb->prefix . 'postmeta';
		$file = $this->clean_uploaded_filename( $file );
		$sql = $wpdb->prepare( "SELECT post_id
			FROM {$postmeta_table_name}
			WHERE meta_key = '_wp_attached_file'
			AND meta_value = %s", $file
		);
		$ret = $wpdb->get_var( $sql );
		if ( $doLog ) {
			if ( empty( $ret ) )
				$this->log( "🚫 File $file not found as _wp_attached_file (Library)." );
			else {
				$this->log( "✅ File $file found as Media $ret." );
			}
		}

		return $ret;
	}

	function get_thumbnails_urls( $id, $sizes_as_key = false ) {
		$sizes = get_intermediate_image_sizes();
		// For each size use wp_get_attachment_image_src() to get the URL
		$urls = array();
		foreach ( $sizes as $size ) {
			$src = wp_get_attachment_image_src( $id, $size );
			if ( $src ) {
				$urls[$size] = $this->clean_url( $src[0] );
			}
		}

		return $sizes_as_key ? $urls : array_values( $urls );
	}
	
	function get_thumbnails_urls_from_srcset( $media, $size = 'full'  ) {

		$id = is_numeric( $media ) ? (int)$media : $this->get_id_from_clean_url( $media, false );

		$image_size = $this->get_attachment_size_by_id( $id, $size );

		$sizes = array_keys( $this->get_image_sizes() );
		$sizes[] = $image_size;

		$urls = array();
		foreach ( $sizes as $image_size ) {
			$srcset     = wp_get_attachment_image_srcset( $id, $image_size );

			// Extract URLs from srcset
			if ( !empty( $srcset ) ) {
				$srcset = explode( ', ', $srcset );
				foreach ( $srcset as $src ) {
					$parts = explode( ' ', $src );
					$url = trim( $parts[0] );
					if ( !empty( $url ) ) {
						$urls[] = $this->clean_url( $url );
					}
				}
			}
		}
		
		return $urls;

	}

	function get_attachment_size_by_id( $attachment_id, $default_size = 'full' ) {

		if ( ! $attachment_id ) {
			return $default_size;
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! $url ) {
			return $default_size;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! is_array( $metadata ) ) {
			return $default_size;
		}

		$size = $default_size;

		if ( isset( $metadata['file'] ) && strpos( $url, $metadata['file'] ) === ( strlen( $url ) - strlen( $metadata['file'] ) ) ) {
			$size = array( $metadata['width'], $metadata['height'] );
		} elseif ( preg_match( '/-(\d+)x(\d+)\.(jpg|jpeg|gif|png|svg|webp)$/', $url, $match ) ) {
			// Get the image width and height.
			// Example: https://regex101.com/r/7JwGz7/1.
			$size = array( $match[1], $match[2] );
		}

		return $size;
	}

	function get_image_sizes() {
		$sizes = array();
		global $_wp_additional_image_sizes;
		foreach ( get_intermediate_image_sizes() as $s ) {
			$crop = false;
			if ( isset( $_wp_additional_image_sizes[$s] ) ) {
				$width = intval( $_wp_additional_image_sizes[$s]['width'] );
				$height = intval( $_wp_additional_image_sizes[$s]['height'] );
				$crop = $_wp_additional_image_sizes[$s]['crop'];
			} else {
				$width = get_option( $s.'_size_w' );
				$height = get_option( $s.'_size_h' );
				$crop = get_option( $s.'_crop' );
			}
			$sizes[$s] = array( 'width' => $width, 'height' => $height, 'crop' => $crop );
		}
		return $sizes;
	}

	/**
	 * Get all registered thumbnail sizes formatted for the UI.
	 * Returns an array of sizes with name, shortname, width, and height.
	 */
	function get_thumbnail_sizes() {
		$sizes = $this->get_image_sizes();
		$result = array();
		foreach ( $sizes as $name => $size ) {
			// Generate a shortname (first 2 letters uppercase)
			$shortname = strtoupper( substr( preg_replace( '/[^a-zA-Z]/', '', $name ), 0, 2 ) );
			$result[] = array(
				'name'      => $name,
				'shortname' => $shortname,
				'width'     => $size['width'] ? intval( $size['width'] ) : null,
				'height'    => $size['height'] ? intval( $size['height'] ) : null,
				'crop'      => $size['crop'],
			);
		}
		return $result;
	}

	function clean_url_from_resolution( $url ) {
		if ( !isset( $url ) ) return $url;

		$pattern = '/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/';
		$url = preg_replace( $pattern, '', $url );
		return $url;
	}

	function is_url( $url ) {
		return ( (
			!empty( $url ) ) &&
			is_string( $url ) &&
			strlen( $url ) > 4 && (
				strtolower( substr( $url, 0, 4) ) == 'http' || $url[0] == '/'
			)
		);
	}

	function get_id_from_clean_url( $clean_url ) {
		$found = false;
		$id = 0;

		if( !$found ) {
			$id = $this->find_media_id_from_file( $clean_url, false );
			if ( $id ) {
				$is_attachment = get_post_type( $id ) === 'attachment';
				if ( $is_attachment ) {
					$found = true;
				}
			}
		}

		if( !$found ) {
			$id = $this->custom_attachment_url_to_postid( $clean_url );
			if ( $id ) {
				$is_attachment = get_post_type( $id ) === 'attachment';
				if ( $is_attachment ) {
					$found = true;
				}
			}
		}

		if ( !$found ) {
			$id = $this->resolve_from_database( $clean_url );
			if ( $id ) {
				$is_attachment = get_post_type( $id ) === 'attachment';
				if ( $is_attachment ) {
					$found = true;
				}
			}
		}


		return $found ? $id : null;
	}

	function resolve_from_database( $url ) {
		global $wpdb;
		$pattern = '/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/';
		$url = preg_replace( $pattern, '', $url );
		$url = $this->get_pathinfo_from_image_src( $url );
		$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%s'", '%' . $url . '%' );
		$attachment = $wpdb->get_col( $query );
		return empty( $attachment ) ? null : $attachment[0];
	}

	function get_pathinfo_from_image_src( $image_src ) {
		$uploads = wp_upload_dir();
		$uploads_url = trailingslashit( $uploads['baseurl'] );
		if ( strpos( $image_src, $uploads_url ) === 0 )
			return ltrim( substr( $image_src, strlen( $uploads_url ) ), '/');
		else if ( strpos( $image_src, wp_make_link_relative( $uploads_url ) ) === 0 )
			return ltrim( substr( $image_src, strlen( wp_make_link_relative( $uploads_url ) ) ), '/');
		$img_info = parse_url( $image_src );
		return ltrim( $img_info['path'], '/' );
	}

	function clean_url_from_resolution_ref( &$url ) {
		$url = $this->clean_url_from_resolution( $url );
	}

	// From a url to the shortened and cleaned url (for example '2013/02/file.png')
	function clean_url( $url ) {
		if ( !is_string( $url ) || $url === '' || $this->upload_url === '' ) {
			return null;
		}
		$url = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
		$base_path = (string) wp_parse_url( $this->upload_url, PHP_URL_PATH );
		$url_path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( $url_path === '' || $base_path === '' ) {
			return null;
		}
		// The host is deliberately not compared: content routinely references the
		// uploads directory through www/non-www variants, CDN aliases, or image
		// proxies, and a missed reference is far more dangerous than an extra one.
		// The uploads path is matched anywhere so proxy prefixes also resolve.
		$marker = trailingslashit( $base_path );
		$position = strpos( $url_path, $marker );
		if ( $position === false ) {
			return null;
		}
		$relative = substr( $url_path, $position + strlen( $marker ) );
		$normalized = $this->normalize_upload_relative_path( urldecode( $relative ) );
		return is_wp_error( $normalized ) ? null : $normalized;
	}

	function custom_attachment_url_to_postid( $url ) {
		global $wpdb;
		
		// Remove the query string
		$url = preg_replace('/\?.*/', '', $url);
		
		// Try to find the attachment ID by matching the URL with the guid
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid LIKE %s AND post_type = 'attachment';", '%' . $wpdb->esc_like( $url ) ) );
		
		// If found, return the first attachment ID
		if ( !empty( $attachment ) ) {
			return ( int )$attachment[0];
		}
		
		// If not found, try to match the URL without the upload directory path
		$upload_dir = wp_upload_dir();
		$url_relative = str_replace( $upload_dir['baseurl'] . '/', '', $url );
		
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s;", '%' . $wpdb->esc_like( $url_relative ) ) );
		
		// If found, return the first attachment ID
		if ( !empty( $attachment ) ) {
			return ( int )$attachment[0];
		}
		
		// If still not found, return 0
		return 0;
	}

	// From a fullpath to the shortened and cleaned path (for example '2013/02/file.png')
	// Original version by Jordy
	// function clean_uploaded_filename( $fullpath ) {
	// 	$basedir = $this->upload_path;
	// 	$file = str_replace( $basedir, '', $fullpath );
	// 	$file = str_replace( "./", "", $file );
	// 	$file = trim( $file,  "/" );
	// 	return $file;
	// }

	// From a fullpath to the shortened and cleaned path (for example '2013/02/file.png')
	// Faster version, more difficult to read, by Mike Meinz
	function clean_uploaded_filename( $fullpath ) {
		if ( !is_string( $fullpath ) || $fullpath === '' ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $fullpath ) ) {
			$clean = $this->clean_url( $fullpath );
			return $clean === null ? '' : $clean;
		}
		$path = wp_normalize_path( rawurldecode( $fullpath ) );
		$base = untrailingslashit( wp_normalize_path( $this->upload_path ) );
		if ( $base !== '' && ( $path === $base || strpos( $path, trailingslashit( $base ) ) === 0 ) ) {
			$path = ltrim( substr( $path, strlen( $base ) ), '/' );
		}
		else if ( substr( $path, 0, 1 ) === '/' ) {
			return '';
		}
		$normalized = $this->normalize_upload_relative_path( $path );
		return is_wp_error( $normalized ) ? '' : $normalized;
	}

	public function build_file_manifest( $paths ) {
		$manifest = array();
		foreach ( array_values( array_unique( (array) $paths ) ) as $path ) {
			$relative = $this->normalize_upload_relative_path( $this->clean_uploaded_filename( $path ) );
			if ( is_wp_error( $relative ) ) return $relative;
			$absolute = $this->resolve_upload_path( $relative );
			if ( is_wp_error( $absolute ) ) return $absolute;
			$fingerprint = $this->file_fingerprint( $absolute );
			if ( is_wp_error( $fingerprint ) ) return $fingerprint;
			$manifest[ $relative ] = $fingerprint;
		}
		return $manifest;
	}

	public function file_fingerprint( $absolute_path ) {
		if ( !file_exists( $absolute_path ) ) return null;
		if ( !is_file( $absolute_path ) || !is_readable( $absolute_path ) || is_link( $absolute_path ) ) {
			return new WP_Error( 'wpmc_file_identity_unsafe', __( 'A file selected by Media Cleaner is unreadable or unsafe.', 'media-cleaner' ) );
		}
		$stat = @lstat( $absolute_path );
		$handle = @fopen( $absolute_path, 'rb' );
		if ( !$stat || !$handle ) return new WP_Error( 'wpmc_file_identity_unavailable', __( 'Media Cleaner could not capture a file identity.', 'media-cleaner' ) );
		$context = hash_init( 'sha256' );
		hash_update( $context, wp_json_encode( array(
			'size' => isset( $stat['size'] ) ? (int) $stat['size'] : 0,
			'mtime' => isset( $stat['mtime'] ) ? (int) $stat['mtime'] : 0,
			'ino' => isset( $stat['ino'] ) ? (int) $stat['ino'] : 0,
			'dev' => isset( $stat['dev'] ) ? (int) $stat['dev'] : 0,
		) ) );
		$sample_size = 64 * 1024;
		$first = fread( $handle, $sample_size );
		if ( $first === false ) {
			fclose( $handle );
			return new WP_Error( 'wpmc_file_identity_unavailable', __( 'Media Cleaner could not read a file identity sample.', 'media-cleaner' ) );
		}
		hash_update( $context, $first );
		if ( (int) $stat['size'] > $sample_size ) {
			fseek( $handle, max( 0, (int) $stat['size'] - $sample_size ), SEEK_SET );
			$last = fread( $handle, $sample_size );
			if ( $last === false ) {
				fclose( $handle );
				return new WP_Error( 'wpmc_file_identity_unavailable', __( 'Media Cleaner could not read a file identity sample.', 'media-cleaner' ) );
			}
			hash_update( $context, $last );
		}
		fclose( $handle );
		return hash_final( $context );
	}

	public function validate_issue_manifest( $issue ) {
		$manifest = json_decode( (string) $issue->manifest, true );
		if ( !is_array( $manifest ) ) return true;
		foreach ( $manifest as $relative => $expected ) {
			$upload = $this->resolve_upload_path( $relative );
			$trash = $this->resolve_trash_path( $relative );
			if ( is_wp_error( $upload ) ) return $upload;
			if ( is_wp_error( $trash ) ) return $trash;
			$existing = file_exists( $upload ) ? $upload : ( file_exists( $trash ) ? $trash : null );
			if ( $expected === null ) {
				if ( $existing ) return new WP_Error( 'wpmc_file_changed_since_scan', __( 'A file appeared after the scan. Run a new scan before cleanup.', 'media-cleaner' ) );
				continue;
			}
			if ( !$existing ) return new WP_Error( 'wpmc_file_changed_since_scan', __( 'A file disappeared after the scan. Run a new scan before cleanup.', 'media-cleaner' ) );
			$current = $this->file_fingerprint( $existing );
			if ( is_wp_error( $current ) ) return $current;
			if ( !hash_equals( (string) $expected, (string) $current ) ) {
				return new WP_Error( 'wpmc_file_changed_since_scan', __( 'A file changed after the scan. Run a new scan before cleanup.', 'media-cleaner' ) );
			}
		}
		return true;
	}

	public function normalize_upload_relative_path( $path ) {
		if ( !is_string( $path ) || strpos( $path, "\0" ) !== false ) {
			return new WP_Error( 'wpmc_invalid_path', __( 'The storage path is invalid.', 'media-cleaner' ) );
		}
		$path = wp_normalize_path( rawurldecode( trim( $path ) ) );
		if ( preg_match( '#^[a-zA-Z]:/#', $path ) || strpos( $path, '://' ) !== false ) {
			return new WP_Error( 'wpmc_absolute_path', __( 'Absolute storage paths are not allowed.', 'media-cleaner' ) );
		}
		$path = ltrim( $path, '/' );
		$segments = array();
		foreach ( explode( '/', $path ) as $segment ) {
			if ( $segment === '' || $segment === '.' ) {
				continue;
			}
			if ( $segment === '..' ) {
				return new WP_Error( 'wpmc_path_traversal', __( 'The storage path attempts to leave the uploads directory.', 'media-cleaner' ) );
			}
			$segments[] = $segment;
		}
		return implode( '/', $segments );
	}

	public function resolve_upload_path( $relative_path, $must_exist = false ) {
		$relative = $this->normalize_upload_relative_path( $relative_path );
		if ( is_wp_error( $relative ) ) {
			return $relative;
		}
		$base = realpath( $this->upload_path );
		if ( !$base ) {
			return new WP_Error( 'wpmc_upload_root_missing', __( 'The uploads directory is unavailable.', 'media-cleaner' ) );
		}
		$base = untrailingslashit( wp_normalize_path( $base ) );
		$candidate = $base . ( $relative === '' ? '' : '/' . $relative );
		if ( is_link( $candidate ) ) {
			return new WP_Error( 'wpmc_symlink_rejected', __( 'Symbolic links are not modified by Media Cleaner.', 'media-cleaner' ) );
		}
		$existing = realpath( $candidate );
		if ( $must_exist && !$existing ) {
			return new WP_Error( 'wpmc_path_missing', __( 'The requested file no longer exists.', 'media-cleaner' ) );
		}
		$resolved = $existing ? wp_normalize_path( $existing ) : $candidate;
		if ( $existing && $resolved !== $base && strpos( $resolved, trailingslashit( $base ) ) !== 0 ) {
			return new WP_Error( 'wpmc_path_outside_uploads', __( 'The requested path is outside the uploads directory.', 'media-cleaner' ) );
		}
		if ( !$existing ) {
			$ancestor = dirname( $candidate );
			while ( !file_exists( $ancestor ) && dirname( $ancestor ) !== $ancestor ) {
				$ancestor = dirname( $ancestor );
			}
			$ancestor_real = realpath( $ancestor );
			$ancestor_real = $ancestor_real ? untrailingslashit( wp_normalize_path( $ancestor_real ) ) : '';
			if ( $ancestor_real !== $base && strpos( $ancestor_real, trailingslashit( $base ) ) !== 0 ) {
				return new WP_Error( 'wpmc_path_outside_uploads', __( 'The requested path is outside the uploads directory.', 'media-cleaner' ) );
			}
		}
		if ( $existing && is_link( $candidate ) ) {
			return new WP_Error( 'wpmc_symlink_rejected', __( 'Symbolic links are not modified by Media Cleaner.', 'media-cleaner' ) );
		}
		return $candidate;
	}

	/**
	 * Check if the file or the Media ID is used in the install.
	 * That file or ID will be checked against the database of references created by the plugin
	 * by the parsers.
	 */
	function reference_exists( $file, $mediaId ) {
		global $wpdb;

		$table = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id();

		$row = null;
		if ( !empty( $mediaId ) ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT originType FROM $table WHERE run_id = %d AND mediaId = %d LIMIT 1", $run_id, $mediaId ) );
			if ( !empty( $row ) ) {
				$origin = $row->originType === 'MEDIA LIBRARY' ? 'Media Library' : 'content';
				$this->log( "✅ Media #{$mediaId} used by {$origin}" );
				return $row->originType;
			}
		}
		if ( !empty( $file ) ) {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT originType FROM $table WHERE run_id = %d AND mediaUrl_hash = %s AND mediaUrl = %s LIMIT 1",
				$run_id,
				hash( 'sha256', $file ),
				$file
			) );
			if ( !empty( $row ) ) {
				$origin = $row->originType === 'MEDIA LIBRARY' ? 'Media Library' : 'content';
				$this->log( "✅ File {$file} used by {$origin}" );
				return $row->originType;
			}
		}
		return false;
	}

	function get_full_upload_path( $relative_path ) {
		$full_path = $this->resolve_upload_path( $relative_path );
		return is_wp_error( $full_path ) ? null : $full_path;
	}

	function get_paths_from_attachment( $attachmentId ) {
		$paths = array();
		$fullpath = get_attached_file( $attachmentId );
		if ( empty( $fullpath ) ) {
			$this->log( 'Could not find attached file for Media ID ' . $attachmentId );
			return array();
		}
		$mainfile = $this->clean_uploaded_filename( $fullpath );
		array_push( $paths, $mainfile );
		$baseUp = pathinfo( $mainfile );
		$filespath = trailingslashit( $this->upload_path ) . trailingslashit( $baseUp['dirname'] );
		$meta = wp_get_attachment_metadata( $attachmentId );
		if ( isset( $meta['original_image'] ) ) {
			$original_image = $this->clean_uploaded_filename( $filespath . $meta['original_image'] );
			array_push( $paths, $original_image );
		}
		$isImage = isset( $meta, $meta['width'], $meta['height'] );
		$sizes = $this->get_image_sizes();
		if ( $isImage && isset( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $name => $attr ) {
				if  ( isset( $attr['file'] ) ) {
					$file = $this->clean_uploaded_filename( $filespath . $attr['file'] );
					array_push( $paths, $file );
				}
			}
		}
		return $paths;
	}

	function is_media_ignored( $attachmentId ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$issue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE run_id = %d AND postId = %d", $run_id, $attachmentId ), OBJECT );
		//error_log( $attachmentId );
		//error_log( print_r( $issue, 1 ) );
		if ( $issue && $issue->ignored )
			return true;
		return false;
	}

	function check_media( $attachmentId, $checkOnly = false ) {

		// Is Media ID ignored, consider as used.
		if ( $this->is_media_ignored( $attachmentId ) ) {
			return true;
		}

		// Remove everything related to this media from the database.
		if ( !$checkOnly ) {
			$this->delete_attachment_related_data( $attachmentId );
		}

		$size = 0;
		$countfiles = 0;
		$check_content = (bool) $this->get_option( 'content' );
		$check_broken_media = !$check_content;
		$fullpath = get_attached_file( $attachmentId );
		$is_broken = apply_filters( 'wpmc_is_file_broken', !file_exists( $fullpath ), $attachmentId );

		// It's a broken-only scan
		if ( $check_broken_media && !$is_broken ) {
			$is_considered_used = apply_filters( 'wpmc_check_media', true, $attachmentId, false );
			return $is_considered_used;
		}

		// Let's analyze the usage of each path (thumbnails included) for this Media ID.
		$issue = 'NO_CONTENT';
		$paths = $this->get_paths_from_attachment( $attachmentId );
		foreach ( $paths as $path ) {
			
			// If it's found in the content, we stop the scan right away
			if ( $check_content && $this->reference_exists( $path, $attachmentId ) ) {
				$is_considered_used = apply_filters( 'wpmc_check_media', true, $attachmentId, false );
				if ( $is_considered_used ) {
					return true;
				}
			}

			// Let's count the size of the files for later, in case it's unused
			$filepath = trailingslashit( $this->upload_path ) . $path;
			if ( file_exists( $filepath ) )
				$size += filesize( $filepath );
			$countfiles++;
		}
		
		// This Media ID seems not in used (or broken)
		// Let's double-check through the filter (overridable by users)
		$is_considered_used = apply_filters( 'wpmc_check_media', false, $attachmentId, $is_broken );
		if ( !$is_considered_used ) {
			if ( $is_broken ) {
				$this->log( "🚫 File {$fullpath} does not exist." );
				$issue = 'ORPHAN_MEDIA';
			}
			if ( !$checkOnly ) {
				global $wpdb;
					$table_name = $wpdb->prefix . "mclean_scan";
					$mainfile = $this->clean_uploaded_filename( $fullpath );
					$display_path = $mainfile !== '' ? $mainfile . ( $countfiles > 0 ? ( " (+ " . $countfiles . " thumbnails)" ) : "" ) : sprintf( 'Media #%d (missing attached file)', $attachmentId );
					$manifest = $this->build_file_manifest( $paths );
					if ( is_wp_error( $manifest ) ) throw new RuntimeException( $manifest->get_error_message() );
					$inserted = $wpdb->insert( $table_name,
						array(
							'run_id' => $this->get_run_id( true ),
							'time' => current_time('mysql'),
							'type' => 1,
							'size' => $size,
							'path' => $display_path,
							'path_hash' => hash( 'sha256', $display_path ),
							'manifest' => wp_json_encode( $manifest ),
							'postId' => $attachmentId,
							'issue' => $issue
						)
					);
					if ( $inserted === false ) throw new RuntimeException( sprintf( __( 'Media Cleaner could not store a media issue: %s', 'media-cleaner' ), $wpdb->last_error ) );
			}
		}
		return $is_considered_used;
	}

	// Delete all issues
	function reset_issues( $includingIgnored = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id( true );
		if ( $run_id < 1 ) throw new RuntimeException( __( 'A writable scan run is required before resetting issues.', 'media-cleaner' ) );
		if ( $includingIgnored ) {
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d AND deleted = 0", $run_id ) );
		}
		else {
			$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d AND ignored = 0 AND deleted = 0", $run_id ) );
		}
		if ( $deleted === false ) throw new RuntimeException( sprintf( __( 'Media Cleaner could not reset staged issues: %s', 'media-cleaner' ), $wpdb->last_error ) );
	}

	function is_image_extension( $ext ) {
		$ext = strtolower( $ext );
		$valid = apply_filters( 'wpmc_valid_image_extensions', array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'ico', 'webp', 'avif' ) );

		return in_array( $ext, $valid );

	}
		

	function reset_references() {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_refs";
		$run_id = $this->get_run_id( true );
		if ( $run_id < 1 ) throw new RuntimeException( __( 'A writable scan run is required before resetting references.', 'media-cleaner' ) );
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE run_id = %d", $run_id ) );
		if ( $deleted === false ) throw new RuntimeException( sprintf( __( 'Media Cleaner could not reset staged references: %s', 'media-cleaner' ), $wpdb->last_error ) );
	}

	function get_issue_for_postId( $postId ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "mclean_scan";
		$run_id = $this->get_run_id();
		$issue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE run_id = %d AND postId = %d", $run_id, $postId ), OBJECT );
		return $issue;
	}

	function echo_issue( $issue ) {
		if ( $issue == 'NO_CONTENT' ) {
			_e( "Not found in content", 'media-cleaner' );
		}
		else if ( $issue == 'ORPHAN_FILE' ) {
			_e( "Not in Library", 'media-cleaner' );
		}
		else if ( $issue == 'ORPHAN_RETINA' ) {
			_e( "Orphan Retina", 'media-cleaner' );
		}
		else if ( $issue == 'ORPHAN_WEBP' ) {
			_e( "Orphan WebP", 'media-cleaner' );
		}
		else if ( $issue == 'ORPHAN_MEDIA' ) {
			_e( "No attached file", 'media-cleaner' );
		}
		else {
			echo $issue;
		}
	}

	function get_uploads_directory_hierarchy() {
		$uploads_dir = wp_upload_dir();
		if ( !empty( $uploads_dir['error'] ) ) {
			throw new RuntimeException( $uploads_dir['error'] );
		}
		$base_dir = wp_normalize_path( $uploads_dir['basedir'] );
		$root = '/' . wp_basename( $base_dir );
		$directories = array();
	
		// Get all subdirectories of the base directory
		$dir_iterator = new RecursiveDirectoryIterator( $base_dir, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS );
		$iterator = new RecursiveIteratorIterator( $dir_iterator, RecursiveIteratorIterator::SELF_FIRST );
	
		$max_directories = (int) apply_filters( 'wpmc_directory_picker_limit', 5000 );
		foreach ( $iterator as $file ) {
			if ( count( $directories ) >= $max_directories ) {
				break;
			}
			if ( $file->isDir() ) {
				// Normalize path for consistency
				$file_path = wp_normalize_path( $file->getPathname() );
				// Remove base_dir from path
				$directory = str_replace( $base_dir, '', $file_path );
				if ( $directory ) {
					$directories[] = $root . $directory;
				}
			}
		}
	
		// Return the hierarchy as a JSON file
		return wp_json_encode( $directories );
	}

	/**
	 *
	 * Roles & Access Rights
	 *
	 */
	public function can_access_settings() {
		return apply_filters( 'wpmc_allow_setup', current_user_can( 'manage_options' ) );
	}

	// Cached per request: the mutators below can be called in batches of 100.
	private $results_staged = null;

	public function can_access_features() {
		return apply_filters( 'wpmc_allow_usage', current_user_can( 'manage_options' ) );
	}

	public function can_cleanup() {
		$allowed = current_user_can( 'manage_options' ) && $this->runs && $this->runs->cleanup_allowed();
		return $allowed && apply_filters( 'wpmc_allow_cleanup', true );
	}

	/**
	 * A staged scan copies the ignored and trashed results into itself when it starts,
	 * and that copy replaces them when it publishes. Changing them in the meantime
	 * would be silently undone, and a trashed row coming back after its file was
	 * recovered would let the next Empty Trash erase a file that is in use.
	 *
	 * So the results hold still while a scan is staged over them. The scan is the
	 * user's own, and publishing or cancelling it releases them.
	 */
	private function results_staged_error() {
		if ( $this->results_staged === null ) {
			$this->results_staged = $this->runs && $this->runs->get_resumable() ? true : false;
		}
		if ( !$this->results_staged ) return null;
		return new WP_Error( 'wpmc_scan_staged',
			__( 'A scan is staged over these results, so they cannot be changed yet. Publish it or cancel it first, then try again.', 'media-cleaner' ),
			array( 'status' => 409 ) );
	}

	#region Options

	function list_options() {
		return array(
			'method' => 'media',
			'content' => true,
			'filesystem_content' => true,
			'media_library' => false,
			'live_content' => false,
			'debuglogs' => false,
			'images_only' => false,
			'attach_is_use' => false,
			'thumbnails_only' => false,
			'dirs_filter' => '',
			'files_filter' => '',
			'hide_thumbnails' => false,
			'hide_warning' => false,
			'skip_trash' => false,
			'medias_buffer' => 100,
			'posts_buffer' => 5,
			'analysis_buffer' => 100,
			'file_op_buffer' => 20,
			'uploads_file_buffer' => 500,
			'delay' => 100,
			'refs_buffer' => 500,
			'shortcodes_disabled' => false,

			'output_buffer_cleaning_disabled' => false,
			'php_error_logs' => false,
			'posts_per_page' => 10,
			'clean_uninstall' => false,
			'repair_mode' => false,
			'expert_mode' => false,
			'mcp_support' => false,
			'logs_path' => null,
			'thumbnail_force_issues' => [],
		);
	}

	function reset_options() {
		delete_option( $this->option_name );
	}

	function get_option( $option ) {
		if ( $this->run_id > 0 && array_key_exists( $option, $this->run_config ) ) {
			return $this->run_config[ $option ];
		}
		$options = $this->get_all_options();
		return $options[$option];
	}

	function get_all_options() {
		$options = get_option( $this->option_name, null );
		$options = $this->check_options( $options );
		return $options;
	}

	// Let's work on this function if we need it.
	// Right now, it looks like the options are all updated at the same time.

	// function update_option( $option, $value ) {
	// 	if ( !array_key_exists( $name, $options ) ) {
	// 		return new WP_REST_Response([ 'success' => false, 'message' => 'This option does not exist.' ], 200 );
	// 	}
	//  $value = is_bool( $params['value'] ) ? ( $params['value'] ? '1' : '' ) : $params['value'];
	// }

	function update_options( $options ) {
		$current = get_option( $this->option_name, array() );
		$current = is_array( $current ) ? $current : array();
		$options = is_array( $options ) ? $options : array();
		$clean = array();
		foreach ( $this->list_options() as $name => $default ) {
			$value = array_key_exists( $name, $options ) ? $options[ $name ] : ( array_key_exists( $name, $current ) ? $current[ $name ] : $default );
			$clean[ $name ] = $this->sanitize_option_value( $name, $value, $default );
		}
		update_option( $this->option_name, $clean, false );
		$options = $this->sanitize_options();
		return $options;
	}

	public function sanitize_scan_config( $config ) {
		$options = $this->get_all_options();
		$config = is_array( $config ) ? $config : array();
		$names = array(
			'content', 'filesystem_content', 'media_library', 'images_only', 'attach_is_use',
			'thumbnails_only', 'dirs_filter', 'files_filter', 'shortcodes_disabled',
			'thumbnail_force_issues', 'posts_buffer', 'medias_buffer', 'analysis_buffer',
			'uploads_file_buffer', 'refs_buffer', 'delay',
		);
		$snapshot = array();
		foreach ( $names as $name ) {
			$default = array_key_exists( $name, $options ) ? $options[ $name ] : null;
			$value = array_key_exists( $name, $config ) ? $config[ $name ] : $default;
			$snapshot[ $name ] = $this->sanitize_option_value( $name, $value, $default );
		}
		return $snapshot;
	}

	private function sanitize_option_value( $name, $value, $default ) {
		$boolean_options = array(
			'content', 'filesystem_content', 'media_library', 'live_content', 'debuglogs',
			'images_only', 'attach_is_use', 'thumbnails_only', 'hide_thumbnails', 'hide_warning',
			'skip_trash', 'shortcodes_disabled', 'output_buffer_cleaning_disabled',
			'php_error_logs', 'clean_uninstall', 'repair_mode', 'expert_mode',
			'mcp_support',
		);
		if ( in_array( $name, $boolean_options, true ) ) {
			return rest_sanitize_boolean( $value );
		}

		$ranges = array(
			'medias_buffer' => array( 1, 500 ),
			'posts_buffer' => array( 1, 100 ),
			'analysis_buffer' => array( 1, 500 ),
			'file_op_buffer' => array( 1, 100 ),
			'uploads_file_buffer' => array( 10, 1000 ),
			'delay' => array( 0, 10000 ),
			'refs_buffer' => array( 10, 1000 ),
			'posts_per_page' => array( 5, 100 ),
		);
		if ( isset( $ranges[ $name ] ) ) {
			$number = is_numeric( $value ) ? (int) $value : (int) $default;
			return max( $ranges[ $name ][0], min( $ranges[ $name ][1], $number ) );
		}

		if ( $name === 'method' ) {
			$value = sanitize_key( $value );
			return in_array( $value, array( 'media', 'files', 'duplicates', 'optimize_thumbnails' ), true ) ? $value : 'media';
		}
		if ( in_array( $name, array( 'dirs_filter', 'files_filter' ), true ) ) {
			$value = is_string( $value ) ? trim( $value ) : '';
			return $value !== '' && @preg_match( $value, '' ) === false ? '' : $value;
		}
		if ( $name === 'thumbnail_force_issues' ) {
			return is_array( $value ) ? array_values( array_unique( array_map( 'sanitize_key', $value ) ) ) : array();
		}
		if ( $name === 'logs_path' ) {
			return is_string( $value ) ? sanitize_text_field( $value ) : null;
		}
		return is_scalar( $value ) || is_array( $value ) ? $value : $default;
	}

	// Upgrade from the old way of storing options to the new way.
	function check_options( $options = [] ) {
		$plugin_options = $this->list_options();
		$options = empty( $options ) ? [] : $options;
		$clean_options = array_intersect_key( $options, $plugin_options );
		$hasChanges = count( $clean_options ) !== count( $options );
		$options = $clean_options;
		foreach ( $plugin_options as $option => $default ) {
			// The option already exists
			if ( isset( $options[$option] ) ) {
				continue;
			}
			// The option does not exist, so we need to add it.
			// Let's use the old value if any, or the default value.
			$options[$option] = get_option( 'wpmc_' . $option, $default );
			delete_option( 'wpmc_' . $option );
			$hasChanges = true;
		}
		if ( $hasChanges ) {
			update_option( $this->option_name , $options );
		}

		// Runtime information is not persisted with settings. In particular, scan
		// checkpoints must never be copied into the options row.
		$options['thumbnail_sizes'] = $this->get_thumbnail_sizes();
		global $mwai;
		$options['mwai_has_mcp'] = !empty( $mwai ) && method_exists( $mwai, 'hasMCP' ) && $mwai->hasMCP();

		return $options;
	}

	// Validate and keep the options clean and logical.
	function sanitize_options() {
		$options = get_option( $this->option_name, array() );
		$options = is_array( $options ) ? $options : array();
		$clean = array();
		foreach ( $this->list_options() as $name => $default ) {
			$value = array_key_exists( $name, $options ) ? $options[ $name ] : $default;
			$clean[ $name ] = $this->sanitize_option_value( $name, $value, $default );
		}
		if ( $clean !== $options ) {
			update_option( $this->option_name, $clean, false );
		}
		return $this->check_options( $clean );
	}

	#endregion
}

// Check the DB. If does not exist, let's create it.
function wpmc_check_database() {
	wpmc_create_database();
}

function wpmc_create_database() {
	global $wpdb;
	$table_name = $wpdb->prefix . "mclean_scan";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		run_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		time DATETIME NULL,
		type TINYINT(1) NOT NULL,
		postId BIGINT(20) NULL,
		path TEXT NULL,
		path_hash CHAR(64) NULL,
		manifest LONGTEXT NULL,
		size BIGINT(20) UNSIGNED NULL,
		ignored TINYINT(1) NOT NULL DEFAULT 0,
		deleted TINYINT(1) NOT NULL DEFAULT 0,
		issue VARCHAR(191) NOT NULL,
		parentId BIGINT(20) NULL,
		PRIMARY KEY  (id),
		KEY run_state_index (run_id, deleted, ignored, id),
		KEY run_post_index (run_id, postId),
		KEY run_path_index (run_id, path_hash),
		KEY run_parent_index (run_id, parentId)
	) " . $charset_collate . ";" ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	$table_name = $wpdb->prefix . "mclean_refs";
	$charset_collate = $wpdb->get_charset_collate();
	// This key doesn't work on too many installs because of the 'Specified key was too long' issue
	// KEY mediaLookUp (mediaId, mediaUrl)
	$sql = "CREATE TABLE $table_name (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		run_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		mediaId BIGINT(20) NULL,
		mediaUrl TEXT NULL,
		mediaUrl_hash CHAR(64) NULL,
		originType VARCHAR(191) NOT NULL,
		origin TEXT NULL,
		parentId BIGINT(20) NULL,
		ref_hash VARCHAR(32) NULL,
		PRIMARY KEY  (id),
		KEY run_media_index (run_id, mediaId),
		KEY run_url_index (run_id, mediaUrl_hash),
		KEY run_origin_index (run_id, originType),
		UNIQUE KEY run_ref_hash_unique (run_id, ref_hash)
	) " . $charset_collate . ";";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$runs_table = $wpdb->prefix . 'mclean_runs';
	$sql = "CREATE TABLE $runs_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		owner_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		method VARCHAR(32) NOT NULL,
		status VARCHAR(24) NOT NULL,
		phase VARCHAR(64) NOT NULL,
		config LONGTEXT NULL,
		checkpoint LONGTEXT NULL,
		counters LONGTEXT NULL,
		errors LONGTEXT NULL,
		error_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		heartbeat_at DATETIME NOT NULL,
		finished_at DATETIME NULL,
		published_at DATETIME NULL,
		PRIMARY KEY (id),
		KEY status_heartbeat_index (status, heartbeat_at),
		KEY owner_status_index (owner_id, status)
	) " . $charset_collate . ";";
	dbDelta( $sql );

	$work_table = $wpdb->prefix . 'mclean_work';
	$sql = "CREATE TABLE $work_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		run_id BIGINT(20) UNSIGNED NOT NULL,
		phase VARCHAR(64) NOT NULL,
		target_type VARCHAR(32) NOT NULL,
		target_key TEXT NOT NULL,
		target_hash CHAR(64) NOT NULL,
		cursor_value BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		status VARCHAR(24) NOT NULL DEFAULT 'pending',
		attempts SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
		last_error TEXT NULL,
		snapshot_token CHAR(64) NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY run_target_unique (run_id, phase, target_hash),
		KEY lease_index (run_id, phase, status, id)
	) " . $charset_collate . ";";
	dbDelta( $sql );

	$operations_table = $wpdb->prefix . 'mclean_operations';
	$sql = "CREATE TABLE $operations_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		run_id BIGINT(20) UNSIGNED NOT NULL,
		issue_id BIGINT(20) UNSIGNED NOT NULL,
		operation VARCHAR(24) NOT NULL,
		state VARCHAR(32) NOT NULL,
		request_key VARCHAR(64) NOT NULL,
		manifest LONGTEXT NULL,
		error_code VARCHAR(64) NULL,
		error_message TEXT NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY request_issue_unique (request_key, issue_id, operation),
		KEY run_state_index (run_id, state, id)
	) " . $charset_collate . ";";
	dbDelta( $sql );

	$duplicates_table = $wpdb->prefix . 'mclean_duplicates';
	$sql = "CREATE TABLE $duplicates_table (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		run_id BIGINT(20) UNSIGNED NOT NULL,
		media_id BIGINT(20) UNSIGNED NOT NULL,
		path TEXT NOT NULL,
		path_hash CHAR(64) NOT NULL,
		size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		content_hash CHAR(64) NULL,
		is_canonical TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY run_media_unique (run_id, media_id),
		KEY run_size_index (run_id, size),
		KEY run_content_index (run_id, content_hash)
	) " . $charset_collate . ";";
	dbDelta( $sql );
}

function wpmc_remove_database() {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "mclean_scan";
	$table_name2 = $wpdb->prefix . "mclean_refs";
	$table_name3 = $wpdb->prefix . "wpmcleaner";
	$table_name4 = $wpdb->prefix . "mclean_runs";
	$table_name5 = $wpdb->prefix . "mclean_work";
	$table_name6 = $wpdb->prefix . "mclean_operations";
	$table_name7 = $wpdb->prefix . "mclean_duplicates";
	$sql = "DROP TABLE IF EXISTS $table_name1, $table_name2, $table_name3, $table_name4, $table_name5, $table_name6, $table_name7;";
	$wpdb->query( $sql );
}

#region Install / Uninstall

/*
	INSTALL / UNINSTALL
*/

function wpmc_install() {
	$previous_schema = (int) get_option( Meow_WPMC_Runs::SCHEMA_OPTION, 0 );
	wpmc_create_database();
	$runs = new Meow_WPMC_Runs( null );
	if ( $runs->tables_exist( true ) ) {
		if ( $previous_schema < 2 ) {
			delete_transient( 'wpmc_progress' );
		}
		// Schema 2 to 4 force-disabled shortcode analysis, which caused false
		// positives for rendered shortcodes (Foo Gallery and others). Undo it once.
		if ( $previous_schema >= 2 && $previous_schema < 5 ) {
			$options = get_option( 'wpmc_options', array() );
			if ( is_array( $options ) && !empty( $options['shortcodes_disabled'] ) ) {
				$options['shortcodes_disabled'] = false;
				update_option( 'wpmc_options', $options, false );
			}
		}
		update_option( Meow_WPMC_Runs::SCHEMA_OPTION, Meow_WPMC_Runs::SCHEMA_VERSION, false );
	}
}

function wpmc_reset () {
	wpmc_remove_database();
	delete_option( Meow_WPMC_Runs::ACTIVE_RUN_OPTION );
	delete_option( Meow_WPMC_Runs::LOCK_OPTION );
	delete_option( Meow_WPMC_Runs::SCHEMA_OPTION );
	delete_option( Meow_WPMC_Runs::SCHEMA_LOCK_OPTION );
	wpmc_create_database();
	$runs = new Meow_WPMC_Runs( null );
	if ( $runs->tables_exist( true ) ) {
		update_option( Meow_WPMC_Runs::SCHEMA_OPTION, Meow_WPMC_Runs::SCHEMA_VERSION, false );
	}
}

#endregion
