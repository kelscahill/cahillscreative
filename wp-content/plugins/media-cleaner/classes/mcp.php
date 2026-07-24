<?php

/**
 * MCP tools for Media Cleaner, served through AI Engine.
 *
 * Media Cleaner decides whether a file is used by looking at the content it can
 * read statically. That is never a proof of non-usage, so every tool here is
 * written to keep the assistant sceptical: capabilities and blind spots are
 * reported up front, results carry their warnings, and anything destructive
 * states what it will do before it is asked to do it.
 */
class Meow_WPMC_MCP {

	const MAX_ITEMS = 100;
	const SAFETY_NOTE = 'Media Cleaner scans content statically. Files used by page builders, themes, custom code, JavaScript, CSS or external sites can be reported as unused even though they are needed. Never delete on the sole basis of these results.';

	private $core;

	public function __construct( $core ) {
		$this->core = $core;
		add_action( 'init', array( $this, 'init' ), 20 );
	}

	public function init() {
		global $mwai;
		if ( !$this->core->get_option( 'mcp_support' ) || !isset( $mwai ) ) {
			return;
		}
		add_filter( 'mwai_mcp_tools', array( $this, 'register_tools' ) );
		add_filter( 'mwai_mcp_callback', array( $this, 'handle_tool_execution' ), 10, 4 );
	}

	#region Tools Definitions

	public function register_tools( $tools ) {
		$category = 'Media Cleaner';

		$tools[] = array(
			'name' => 'wpmc_get_capabilities',
			'description' => 'Report what Media Cleaner can and cannot detect on this site: which parsers are active, which installed builders or plugins are NOT supported, and the current blind spots. ALWAYS call this first, before scanning or deleting anything. Its "unsupported" and "limitations" fields tell you which media must be verified by hand instead of trusted from a scan.',
			'category' => $category,
			'accessLevel' => 'read',
			'inputSchema' => array( 'type' => 'object' ),
		);

		$tools[] = array(
			'name' => 'wpmc_get_status',
			'description' => 'Report the current state of Media Cleaner: the configured scan method, the counts of issues, ignored and trashed items from the last completed scan, whether a scan is staged or paused, and whether cleanup is currently allowed (and why not, when it is locked).',
			'category' => $category,
			'accessLevel' => 'read',
			'inputSchema' => array( 'type' => 'object' ),
		);

		$tools[] = array(
			'name' => 'wpmc_scan_start',
			'description' => 'Stage a new scan and return its run id. The scan is only staged: it does not touch the published results, and nothing is deleted. Follow with wpmc_scan_step repeatedly until finished is true, then wpmc_scan_publish. Only one scan can be staged at a time.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'method' => array(
						'type' => 'string',
						'enum' => array( 'media', 'files', 'duplicates' ),
						'description' => 'media = list the Media Library and find entries not referenced in the content. files = walk the uploads folder and find files not referenced. duplicates = find files with identical content. Defaults to the method configured in the settings.',
					),
					'content' => array(
						'type' => 'boolean',
						'description' => 'Analyze the posts content to collect references. Strongly recommended: without it, almost everything looks unused.',
					),
				),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_scan_step',
			'description' => 'Run the next bounded batch of the staged scan and return the progress. Call it again while finished is false; next_action tells you what to do. A scan of a large site needs many calls, this is normal and each call is deliberately time-bounded so the server is never overloaded.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'run_id' => array( 'type' => 'integer', 'description' => 'The run id returned by wpmc_scan_start.' ),
				),
				'required' => array( 'run_id' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_scan_publish',
			'description' => 'Publish a finished scan so its results replace the previous ones and become visible in the dashboard. It fails if the scan did not complete every phase, which protects you from publishing partial evidence.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'run_id' => array( 'type' => 'integer', 'description' => 'The run id returned by wpmc_scan_start.' ),
				),
				'required' => array( 'run_id' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_scan_cancel',
			'description' => 'Cancel a staged scan and discard its temporary data. The previously published results are kept untouched.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'run_id' => array( 'type' => 'integer', 'description' => 'The run id to cancel.' ),
				),
				'required' => array( 'run_id' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_get_issues',
			'description' => 'List the media reported as unused by the last published scan. These are SUSPICIONS, not proof: read the warnings, and use wpmc_explain_issue before acting on anything that matters.',
			'category' => $category,
			'accessLevel' => 'read',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'filter' => array(
						'type' => 'string',
						'enum' => array( 'issues', 'ignored', 'trash' ),
						'description' => 'issues = reported as unused (default). ignored = kept on purpose. trash = already moved to the Media Cleaner trash.',
					),
					'search' => array( 'type' => 'string', 'description' => 'Filter by file path.' ),
					'limit' => array( 'type' => 'integer', 'description' => 'How many items to return, 1 to 100. Defaults to 25.' ),
					'skip' => array( 'type' => 'integer', 'description' => 'How many items to skip, for paging.' ),
				),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_explain_issue',
			'description' => 'Explain why one item was reported as unused: the references that were found for it, the parsers that ran, and the reasons the result could be wrong. Use it before deleting anything, and report its "verify_manually" advice to the user.',
			'category' => $category,
			'accessLevel' => 'read',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'entry_id' => array( 'type' => 'integer', 'description' => 'The id of the entry, as returned by wpmc_get_issues.' ),
				),
				'required' => array( 'entry_id' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_ignore',
			'description' => 'Mark entries as ignored, so they are kept and no longer reported. This is the safe way to dismiss a false positive. Nothing is deleted.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'entry_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Entry ids, 100 maximum per call.' ),
					'ignore' => array( 'type' => 'boolean', 'description' => 'True to ignore (default), false to stop ignoring.' ),
				),
				'required' => array( 'entry_ids' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_trash',
			'description' => 'Move entries to the Media Cleaner trash. This is REVERSIBLE with wpmc_recover: the files are moved to a private folder, not erased. Ask the user for an explicit confirmation before calling this, and tell them how many files, and which, are concerned. If the site uses a builder listed as unsupported by wpmc_get_capabilities, say so first.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'entry_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Entry ids, 100 maximum per call.' ),
				),
				'required' => array( 'entry_ids' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_recover',
			'description' => 'Restore entries from the Media Cleaner trash back to their original place. This is the undo of wpmc_trash, and the right answer whenever a doubt appears.',
			'category' => $category,
			'accessLevel' => 'write',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'entry_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Entry ids, 100 maximum per call.' ),
				),
				'required' => array( 'entry_ids' ),
			),
		);

		$tools[] = array(
			'name' => 'wpmc_delete_permanently',
			'description' => 'PERMANENTLY delete entries. The files are erased and CANNOT be recovered by Media Cleaner, only from a backup. Never call this on your own initiative, never to "clean up" after a scan, and never in a loop over a list. It requires the user to have explicitly asked for a permanent deletion, and requires confirm to be exactly PERMANENT. Prefer wpmc_trash in every other case.',
			'category' => $category,
			'accessLevel' => 'admin',
			'inputSchema' => array(
				'type' => 'object',
				'properties' => array(
					'entry_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Entry ids, 100 maximum per call.' ),
					'confirm' => array( 'type' => 'string', 'description' => 'Must be exactly PERMANENT. Only set it after the user asked for a permanent deletion, being aware the files cannot be restored.' ),
				),
				'required' => array( 'entry_ids', 'confirm' ),
			),
		);

		return $tools;
	}

	#endregion

	#region Execution

	public function handle_tool_execution( $result, $tool, $args, $id ) {
		if ( strpos( (string) $tool, 'wpmc_' ) !== 0 ) {
			return $result;
		}
		$args = is_array( $args ) ? $args : array();
		try {
			switch ( $tool ) {
				case 'wpmc_get_capabilities': return $this->tool_get_capabilities();
				case 'wpmc_get_status': return $this->tool_get_status();
				case 'wpmc_scan_start': return $this->tool_scan_start( $args );
				case 'wpmc_scan_step': return $this->tool_scan_step( $args );
				case 'wpmc_scan_publish': return $this->tool_scan_publish( $args );
				case 'wpmc_scan_cancel': return $this->tool_scan_cancel( $args );
				case 'wpmc_get_issues': return $this->tool_get_issues( $args );
				case 'wpmc_explain_issue': return $this->tool_explain_issue( $args );
				case 'wpmc_ignore': return $this->tool_ignore( $args );
				case 'wpmc_trash': return $this->tool_operate( $args, 'trash' );
				case 'wpmc_recover': return $this->tool_operate( $args, 'recover' );
				case 'wpmc_delete_permanently': return $this->tool_delete_permanently( $args );
			}
			return $result;
		}
		catch ( Throwable $e ) {
			return array( 'success' => false, 'error' => $e->getMessage() );
		}
	}

	#endregion

	#region Discovery

	private function tool_get_capabilities() {
		$is_pro = $this->core->admin && $this->core->admin->is_pro_user();
		$shortcodes = !$this->core->get_option( 'shortcodes_disabled' );
		// get_natives() are handled by the free version, get_issues() are the ones
		// detected on this site whose parser only ships with the Pro version.
		$free_supported = class_exists( 'Meow_WPMC_Support' ) ? Meow_WPMC_Support::get_natives() : array();
		$pro_only = class_exists( 'Meow_WPMC_Support' ) ? Meow_WPMC_Support::get_issues() : array();
		$supported = $is_pro ? array_merge( $free_supported, $pro_only ) : $free_supported;
		$needs_pro = $is_pro ? array() : $pro_only;

		$limitations = array(
			'Media referenced only by PHP code, a theme template or a custom field without a dedicated parser is not detected.',
			'Media built dynamically in JavaScript, or injected by an external service, is not detected.',
			'Media used on another site of a multisite, or on a staging copy sharing the uploads, is not detected.',
			'Any plugin or theme absent from the supported list stores its data in its own format, which is not read. The list below only covers what Media Cleaner knows about: a plugin it never heard of is invisible to it.',
		);
		if ( !$shortcodes ) {
			$limitations[] = 'Shortcode analysis is DISABLED in the settings: everything rendered by a shortcode (galleries, sliders) is currently invisible to the scan. This is a major source of false positives.';
		}
		if ( !empty( $needs_pro ) ) {
			$limitations[] = 'These plugins are installed but their parser is only in the Pro version, so they are NOT covered here: ' . implode( ', ', $needs_pro ) . '. Media used only by them will be reported as unused.';
		}

		return array(
			'success' => true,
			'is_pro' => $is_pro,
			'shortcode_analysis_enabled' => $shortcodes,
			'supported_plugins_detected' => array_values( array_unique( $supported ) ),
			'detected_but_needs_pro' => array_values( $needs_pro ),
			'active_parsers' => $this->list_active_parsers(),
			'limitations' => $limitations,
			'how_to_be_safe' => array(
				'Ask the user for a full backup before any deletion.',
				'Ask the user which plugins, themes or custom code use their media, and check that they are in supported_plugins_detected.',
				'Use wpmc_explain_issue on a sample of the results and check the reasons.',
				'Prefer wpmc_ignore for anything doubtful, and wpmc_trash (reversible) over wpmc_delete_permanently.',
			),
			'warning' => self::SAFETY_NOTE,
		);
	}

	private function list_active_parsers() {
		global $wp_filter;
		// The parsers register themselves on this hook, which normally only fires
		// when a scan starts. It has to run here to be able to list them.
		$this->core->safe_do_action( 'wpmc_initialize_parsers' );
		$parsers = array();
		foreach ( array( 'wpmc_scan_post', 'wpmc_scan_postmeta', 'wpmc_scan_once', 'wpmc_scan_widget' ) as $hook ) {
			if ( empty( $wp_filter[ $hook ] ) || !( $wp_filter[ $hook ] instanceof WP_Hook ) ) {
				continue;
			}
			foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$name = $callback['function'];
					if ( is_string( $name ) ) {
						$parsers[] = $name;
					}
					else if ( is_array( $name ) && count( $name ) === 2 ) {
						$owner = is_object( $name[0] ) ? get_class( $name[0] ) : $name[0];
						$parsers[] = $owner . '::' . $name[1];
					}
				}
			}
		}
		return array_values( array_unique( $parsers ) );
	}

	private function tool_get_status() {
		$runs = $this->core->runs;
		if ( !$runs ) {
			return array( 'success' => false, 'error' => 'The Media Cleaner run manager is unavailable.' );
		}
		$active = $runs->get( $runs->get_active_id() );
		$resumable = $runs->get_resumable();
		$stats = $this->get_counts();
		$cleanup_allowed = $this->core->can_cleanup();
		$cleanup_blocked_because = null;
		if ( !$cleanup_allowed ) {
			// Only trashing waits for this. Recovering, emptying the trash and ignoring
			// always work, so the reason says what is actually refused.
			$cleanup_blocked_because = $resumable
				? 'A scan is staged or paused, so wpmc_trash is refused. Publish it with wpmc_scan_publish, or cancel it with wpmc_scan_cancel. Recovering and emptying the trash still work.'
				: 'No completed scan from this version of Media Cleaner is published, so wpmc_trash is refused. Run one with wpmc_scan_start. Recovering and emptying the trash still work.';
		}

		return array(
			'success' => true,
			'method_configured' => $this->core->get_option( 'method' ),
			'content_analysis_enabled' => (bool) $this->core->get_option( 'content' ),
			'shortcode_analysis_enabled' => !$this->core->get_option( 'shortcodes_disabled' ),
			'last_published_scan' => $active ? array(
				'run_id' => (int) $active->id,
				'method' => $active->method,
				'published_at' => $active->published_at,
			) : null,
			'staged_scan' => $resumable ? array(
				'run_id' => (int) $resumable->id,
				'status' => $resumable->status,
				'phase' => $resumable->phase,
				'hint' => 'Continue it with wpmc_scan_step, or cancel it with wpmc_scan_cancel.',
			) : null,
			'counts' => $stats,
			'cleanup_allowed' => $cleanup_allowed,
			'cleanup_blocked_because' => $cleanup_blocked_because,
			'warning' => self::SAFETY_NOTE,
		);
	}

	private function get_counts() {
		global $wpdb;
		$table = $wpdb->prefix . 'mclean_scan';
		$run_id = $this->core->get_run_id();
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				SUM(CASE WHEN ignored = 0 AND deleted = 0 THEN 1 ELSE 0 END) AS issues,
				SUM(CASE WHEN ignored = 1 THEN 1 ELSE 0 END) AS ignored,
				SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) AS trashed
			FROM $table WHERE run_id = %d",
			$run_id
		) );
		return array(
			'issues' => $row ? (int) $row->issues : 0,
			'ignored' => $row ? (int) $row->ignored : 0,
			'trashed' => $row ? (int) $row->trashed : 0,
		);
	}

	#endregion

	#region Scanning

	private function tool_scan_start( $args ) {
		$runs = $this->core->runs;
		if ( !$runs ) {
			return array( 'success' => false, 'error' => 'The Media Cleaner run manager is unavailable.' );
		}
		$existing = $runs->get_resumable();
		if ( $existing ) {
			return array(
				'success' => false,
				'error' => 'A scan is already staged.',
				'run_id' => (int) $existing->id,
				'hint' => 'Continue it with wpmc_scan_step, or cancel it with wpmc_scan_cancel.',
			);
		}

		$options = $this->core->get_all_options();
		$method = isset( $args['method'] ) ? sanitize_key( $args['method'] ) : $options['method'];
		if ( !in_array( $method, array( 'media', 'files', 'duplicates' ), true ) ) {
			return array( 'success' => false, 'error' => 'Unsupported method. Use media, files or duplicates.' );
		}
		$config = $this->core->sanitize_scan_config( $options );
		if ( isset( $args['content'] ) ) {
			$content = rest_sanitize_boolean( $args['content'] );
			$config['content'] = $content;
			$config['filesystem_content'] = $content;
		}

		$storage = $this->core->prepare_private_storage();
		if ( is_wp_error( $storage ) ) {
			return array( 'success' => false, 'error' => $storage->get_error_message() );
		}

		$run = $runs->start( $method, $config, 'mcp-' . wp_generate_uuid4() );
		if ( is_wp_error( $run ) ) {
			return array( 'success' => false, 'error' => $run->get_error_message() );
		}
		$context = $this->core->set_run_context( $run->id );
		if ( is_wp_error( $context ) ) {
			return array( 'success' => false, 'error' => $context->get_error_message() );
		}
		$scan_type = $this->get_scan_steps( $method, $config );
		$runs->checkpoint( $run->id, 'ready', null, array( 'mcp' => array(
			'steps' => $scan_type,
			'index' => 0,
			'offset' => 0,
		) ) );

		$warnings = array( self::SAFETY_NOTE );
		if ( empty( $config['content'] ) ) {
			$warnings[] = 'Content analysis is DISABLED for this scan: nearly every file will be reported as unused. This is almost certainly not what the user wants.';
		}
		if ( !empty( $config['shortcodes_disabled'] ) ) {
			$warnings[] = 'Shortcode analysis is disabled: galleries and sliders rendered by shortcodes will look unused.';
		}

		return array(
			'success' => true,
			'run_id' => (int) $run->id,
			'method' => $method,
			'config_used' => array(
				'content_analysis' => (bool) $config['content'],
				'media_library_check' => (bool) $config['media_library'],
				'shortcode_analysis' => empty( $config['shortcodes_disabled'] ),
			),
			'steps' => $scan_type,
			'next_action' => 'Call wpmc_scan_step with this run_id, and repeat while finished is false.',
			'nothing_deleted' => true,
			'warnings' => $warnings,
		);
	}

	private function get_scan_steps( $method, $config ) {
		$steps = array( 'resetIssuesAndReferences' );
		$content = $method === 'files' ? !empty( $config['filesystem_content'] ) : !empty( $config['content'] );
		if ( $content ) {
			$steps[] = 'extractReferencesFromContent';
		}
		if ( $method === 'files' && !empty( $config['media_library'] ) ) {
			$steps[] = 'extractReferencesFromMedia';
		}
		if ( $method === 'duplicates' ) {
			$steps[] = 'extractReferencesFromDuplicates';
		}
		$steps[] = 'retrieveTargets';
		return $steps;
	}

	private function read_cursor( $run ) {
		$counters = json_decode( (string) $run->counters, true );
		$cursor = is_array( $counters ) && isset( $counters['mcp'] ) && is_array( $counters['mcp'] )
			? $counters['mcp'] : array();
		return array(
			'steps' => isset( $cursor['steps'] ) && is_array( $cursor['steps'] ) ? $cursor['steps'] : null,
			'index' => isset( $cursor['index'] ) ? (int) $cursor['index'] : 0,
			'offset' => isset( $cursor['offset'] ) ? (int) $cursor['offset'] : 0,
		);
	}

	private function tool_scan_step( $args ) {
		$run_id = isset( $args['run_id'] ) ? (int) $args['run_id'] : 0;
		$run = $this->core->set_run_context( $run_id );
		if ( is_wp_error( $run ) ) {
			return array( 'success' => false, 'error' => $run->get_error_message() );
		}
		// The cursor lives in the counters, not in the checkpoint. The engines write the
		// checkpoint themselves during a step, replacing whatever was there, so a cursor
		// kept in it would be erased mid-step: if the request then died, the next call
		// would read no cursor, start again from step zero and reset the references it
		// had already collected, while the work journals still counted as done. The
		// counters are merged instead of replaced, so both can write freely.
		$cursor = $this->read_cursor( $run );
		$steps = is_array( $cursor['steps'] ) && $cursor['steps']
			? $cursor['steps']
			: $this->get_scan_steps( $run->method, json_decode( (string) $run->config, true ) ?: array() );
		$index = (int) $cursor['index'];
		$offset = (int) $cursor['offset'];

		if ( $index >= count( $steps ) ) {
			return array(
				'success' => true,
				'run_id' => $run_id,
				'finished' => true,
				'next_action' => 'Call wpmc_scan_publish with this run_id to make these results the published ones.',
			);
		}

		$step = $steps[ $index ];
		$engine = $this->core->engine;
		$message = '';
		$processed = 0;
		$step_finished = true;

		try {
			switch ( $step ) {
				case 'resetIssuesAndReferences':
					$this->core->reset_issues();
					$this->core->reset_references();
					$this->core->save_progress( 'resetIssuesAndReferences' );
					break;
				case 'extractReferencesFromContent':
					$step_finished = $engine->extractRefsFromContent( $offset, (int) $this->core->get_option( 'posts_buffer' ), $message, null, $processed );
					break;
				case 'extractReferencesFromMedia':
					$step_finished = $engine->extractRefsFromLibrary( $offset, (int) $this->core->get_option( 'posts_buffer' ), $message, null, $processed );
					break;
				case 'extractReferencesFromDuplicates':
					$step_finished = $engine->extractRefsFromDuplicates( $offset, (int) $this->core->get_option( 'medias_buffer' ), $message, null, $processed );
					break;
				case 'retrieveTargets':
					$result = $this->step_retrieve_targets( $run, $offset, $processed );
					$step_finished = $result['finished'];
					$message = $result['message'];
					break;
			}
		}
		catch ( Meow_WPMC_Transient_Exception $e ) {
			return array(
				'success' => true,
				'run_id' => $run_id,
				'finished' => false,
				'retry' => true,
				'message' => $e->getMessage(),
				'next_action' => 'The server asked for a pause. Call wpmc_scan_step again with the same run_id.',
			);
		}
		catch ( Throwable $e ) {
			$this->core->runs->fail( $run_id, 'mcp_scan_failed', $e->getMessage() );
			return array( 'success' => false, 'run_id' => $run_id, 'error' => $e->getMessage(), 'scan_failed' => true );
		}

		// The engines check the time limit before handling their first item, so a step
		// can come back having processed nothing. Advancing the offset here would step
		// over an item, its references would never be collected, and the media it uses
		// could later be reported as unused. The offset is kept instead and the step is
		// retried on a fresh time budget, which is enough to get past it.
		if ( !$step_finished && $processed < 1 ) {
			return array(
				'success' => true,
				'run_id' => $run_id,
				'finished' => false,
				'retry' => true,
				'step' => $step,
				'message' => 'The server ran out of time before this step processed anything. Nothing was skipped.',
				'next_action' => 'Call wpmc_scan_step again with the same run_id.',
			);
		}

		$next_offset = $step_finished ? 0 : $offset + $processed;
		$next_index = $step_finished ? $index + 1 : $index;
		$finished = $next_index >= count( $steps );
		// The last checkpoint has to carry the phase name the run manager expects for
		// this method, otherwise the coverage is incomplete and publishing is refused.
		$phase = $finished ? $this->final_phase( $run->method ) : $step;
		$counters = array( 'mcp' => array(
			'steps' => $steps,
			'index' => $next_index,
			'offset' => $next_offset,
		) );
		// checkpoint() answers false when the database refused the write. Carrying on
		// would report a step as done, or the scan as finished, while the cursor still
		// points at the previous position.
		$saved = $this->core->runs->checkpoint( $run_id, $phase, null, $counters );
		if ( is_wp_error( $saved ) || $saved === false ) {
			return array(
				'success' => false,
				'run_id' => $run_id,
				'error' => is_wp_error( $saved ) ? $saved->get_error_message()
					: 'The scan progress could not be saved, so the scan was stopped instead of reporting progress that was not recorded.',
			);
		}

		return array(
			'success' => true,
			'run_id' => $run_id,
			'step' => $step,
			'step_number' => $index + 1,
			'total_steps' => count( $steps ),
			'processed_in_this_call' => $processed,
			'message' => $message,
			'finished' => $finished,
			'next_action' => $finished
				? 'Call wpmc_scan_publish with this run_id to make these results the published ones.'
				: 'Call wpmc_scan_step again with the same run_id.',
			'nothing_deleted' => true,
		);
	}

	private function final_phase( $method ) {
		if ( $method === 'files' ) return 'retrieveFiles_finished';
		if ( $method === 'duplicates' ) return 'retrieveDuplicates_finished';
		return 'retrieveMedia_finished';
	}

	private function step_retrieve_targets( $run, $offset, &$processed ) {
		$engine = $this->core->engine;
		$processed = 0;
		if ( $run->method === 'media' ) {
			$buffer = (int) $this->core->get_option( 'medias_buffer' );
			$ids = $engine->get_media_entries( $offset, $buffer, $this->core->get_option( 'attach_is_use' ) );
			$this->core->timeout_check_start( count( $ids ) );
			foreach ( $ids as $media_id ) {
				if ( $this->core->timeout_should_yield() ) break;
				$engine->check_media( $media_id );
				$this->core->timeout_check_additem();
				$processed++;
			}
			$finished = count( $ids ) < $buffer && $processed === count( $ids );
			return array( 'finished' => $finished, 'message' => sprintf( 'Checked %d media.', $processed ) );
		}
		if ( $run->method === 'duplicates' ) {
			$buffer = min( 100, max( 1, (int) $this->core->get_option( 'analysis_buffer' ) ) );
			$hashes = $engine->get_hash_duplicates( $offset, $buffer );
			$this->core->timeout_check_start( count( $hashes ) );
			foreach ( $hashes as $hash ) {
				if ( $this->core->timeout_should_yield() ) break;
				$engine->check_duplicates( $hash );
				$this->core->timeout_check_additem();
				$processed++;
			}
			$finished = count( $hashes ) < $buffer && $processed === count( $hashes );
			return array( 'finished' => $finished, 'message' => sprintf( 'Checked %d duplicate groups.', $processed ) );
		}
		// Filesystem: the REST layer owns the directory queue, reuse it as is.
		$rest = new Meow_WPMC_Rest( $this->core, $this->core->admin );
		$request = new WP_REST_Request( 'POST', '/media-cleaner/v1/retrieve_files' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'runId' => (int) $run->id, 'initialize' => $offset === 0, 'root' => '' ) ) );
		$response = $rest->rest_retrieve_files( $request );
		$data = $response->get_data();
		if ( empty( $data['success'] ) ) {
			throw new RuntimeException( isset( $data['message'] ) ? $data['message'] : 'The filesystem scan failed.' );
		}
		$processed = isset( $data['data']['checked'] ) ? (int) $data['data']['checked'] : 0;
		return array(
			'finished' => !empty( $data['data']['finished'] ),
			'message' => sprintf( 'Checked %d files.', $processed ),
		);
	}

	private function tool_scan_publish( $args ) {
		$run_id = isset( $args['run_id'] ) ? (int) $args['run_id'] : 0;
		$runs = $this->core->runs;
		$run = $runs->complete( $run_id );
		if ( is_wp_error( $run ) ) {
			return array(
				'success' => false,
				'error' => $run->get_error_message(),
				'hint' => 'The scan is incomplete. Keep calling wpmc_scan_step until finished is true.',
			);
		}
		$this->core->clear_run_context();
		$counts = $this->get_counts();
		return array(
			'success' => true,
			'run_id' => (int) $run->id,
			'counts' => $counts,
			'next_action' => 'Use wpmc_get_issues to read the results, and wpmc_explain_issue before acting on them.',
			'warning' => self::SAFETY_NOTE,
		);
	}

	private function tool_scan_cancel( $args ) {
		$run_id = isset( $args['run_id'] ) ? (int) $args['run_id'] : 0;
		$result = $this->core->runs->discard( $run_id );
		if ( is_wp_error( $result ) ) {
			return array( 'success' => false, 'error' => $result->get_error_message() );
		}
		$this->core->clear_run_context();
		return array(
			'success' => (bool) $result,
			'run_id' => $run_id,
			'message' => $result ? 'The staged scan was cancelled, the published results are untouched.' : 'This scan is no longer cancellable.',
		);
	}

	#endregion

	#region Results

	private function tool_get_issues( $args ) {
		global $wpdb;
		$filter = isset( $args['filter'] ) ? sanitize_key( $args['filter'] ) : 'issues';
		$filters = array(
			'issues' => 'ignored = 0 AND deleted = 0',
			'ignored' => 'ignored = 1',
			'trash' => 'deleted = 1',
		);
		if ( !isset( $filters[ $filter ] ) ) {
			$filter = 'issues';
		}
		$limit = isset( $args['limit'] ) ? max( 1, min( self::MAX_ITEMS, (int) $args['limit'] ) ) : 25;
		$skip = isset( $args['skip'] ) ? max( 0, (int) $args['skip'] ) : 0;
		$search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$table = $wpdb->prefix . 'mclean_scan';
		// Run 0 is where the results made before the runs existed live. They are read
		// like any others: the dashboard shows them, so refusing here would hide a
		// trash the user still needs to recover.
		$run_id = $this->core->get_run_id();
		$condition = $filters[ $filter ];
		$search_sql = $search === '' ? '' : $wpdb->prepare( 'AND path LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE run_id = %d AND $condition $search_sql", $run_id ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, type, postId, path, size, issue FROM $table
			WHERE run_id = %d AND $condition $search_sql
			ORDER BY size DESC LIMIT %d, %d",
			$run_id, $skip, $limit
		) );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'entry_id' => (int) $row->id,
				'media_id' => $row->postId ? (int) $row->postId : null,
				'path' => $row->path,
				'size_bytes' => (int) $row->size,
				'issue' => $row->issue,
				'is_media_library_entry' => (int) $row->type === 1,
			);
		}
		return array(
			'success' => true,
			'filter' => $filter,
			'total' => $total,
			'returned' => count( $items ),
			'items' => $items,
			'warning' => self::SAFETY_NOTE,
			'advice' => 'Use wpmc_explain_issue on the items that matter before proposing any deletion, and check wpmc_get_capabilities for the plugins that are not supported on this site.',
		);
	}

	private function tool_explain_issue( $args ) {
		global $wpdb;
		$entry_id = isset( $args['entry_id'] ) ? (int) $args['entry_id'] : 0;
		$issue = $this->core->get_issue( $entry_id );
		if ( !$issue ) {
			return array( 'success' => false, 'error' => 'This entry does not exist in the published results.' );
		}
		$table_refs = $wpdb->prefix . 'mclean_refs';
		$run_id = $this->core->get_run_id();
		$paths = (int) $issue->type === 1 ? $this->core->get_paths_from_attachment( $issue->postId ) : array( $issue->path );
		$found = array();
		foreach ( array_slice( (array) $paths, 0, 20 ) as $path ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT originType, origin FROM $table_refs WHERE run_id = %d AND mediaUrl = %s LIMIT 5",
				$run_id, $path
			) );
			foreach ( $rows as $row ) {
				$found[] = array( 'path' => $path, 'origin_type' => $row->originType, 'origin' => $row->origin );
			}
		}
		if ( $issue->postId ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT originType, origin FROM $table_refs WHERE run_id = %d AND mediaId = %d LIMIT 5",
				$run_id, (int) $issue->postId
			) );
			foreach ( $rows as $row ) {
				$found[] = array( 'media_id' => (int) $issue->postId, 'origin_type' => $row->originType, 'origin' => $row->origin );
			}
		}

		$is_pro = $this->core->admin && $this->core->admin->is_pro_user();
		$needs_pro = !$is_pro && class_exists( 'Meow_WPMC_Support' ) ? Meow_WPMC_Support::get_issues() : array();
		$shortcodes_off = (bool) $this->core->get_option( 'shortcodes_disabled' );
		$verify = array();
		if ( !empty( $needs_pro ) ) {
			$verify[] = 'These plugins are installed but their parser needs the Pro version, so they were not read: ' . implode( ', ', $needs_pro ) . '. If this media is used by one of them, the result is wrong.';
		}
		if ( $shortcodes_off ) {
			$verify[] = 'Shortcode analysis is disabled, so a gallery or slider using this media would not have been seen.';
		}
		$verify[] = 'Search the media file name in the theme files, the custom CSS and the custom code of the site.';
		$verify[] = 'Open the media in the Media Library and look at the "Used in" information.';
		$verify[] = 'Media Cleaner only knows the plugins it has a parser for. If this site uses anything else to display media, check there too.';

		return array(
			'success' => true,
			'entry_id' => $entry_id,
			'path' => $issue->path,
			'media_id' => $issue->postId ? (int) $issue->postId : null,
			'issue' => $issue->issue,
			'issue_meaning' => $this->explain_issue_code( $issue->issue ),
			'references_found' => $found,
			'why_it_is_reported' => empty( $found )
				? 'No reference to this media was found in anything Media Cleaner was able to read.'
				: 'Some references exist but did not protect this entry: read them, this result is suspicious and should not be deleted without checking.',
			'confidence' => empty( $found ) && empty( $needs_pro ) && !$shortcodes_off ? 'reasonable' : 'low',
			'confidence_meaning' => 'reasonable means nothing contradicts the result, it is never a proof. low means a known blind spot could explain it.',
			'verify_manually' => $verify,
			'warning' => self::SAFETY_NOTE,
		);
	}

	private function explain_issue_code( $code ) {
		$codes = array(
			'NO_CONTENT' => 'No reference was found in the content that was analyzed. It does not mean the file is unused.',
			'ORPHAN_MEDIA' => 'This Media Library entry is not attached to any post.',
			'ORPHAN_FILE' => 'This file is in the uploads folder but not in the Media Library.',
			'ORPHAN_RETINA' => 'This is a retina file whose original is missing.',
			'ORPHAN_WEBP' => 'This is a WebP file whose original is missing.',
			'DUPLICATE' => 'Another file has exactly the same content. One copy is always kept.',
			'NOT_NEEDED_THUMB' => 'This thumbnail size is not registered by the theme or WordPress anymore.',
		);
		return isset( $codes[ $code ] ) ? $codes[ $code ] : $code;
	}

	#endregion

	#region Cleanup

	private function read_entry_ids( $args ) {
		$ids = isset( $args['entry_ids'] ) ? (array) $args['entry_ids'] : array();
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		if ( empty( $ids ) ) {
			throw new RuntimeException( 'entry_ids is required and must contain at least one valid id.' );
		}
		if ( count( $ids ) > self::MAX_ITEMS ) {
			throw new RuntimeException( sprintf( 'Too many items: %d. Send %d at most per call.', count( $ids ), self::MAX_ITEMS ) );
		}
		return $ids;
	}

	private function tool_ignore( $args ) {
		$ids = $this->read_entry_ids( $args );
		$ignore = isset( $args['ignore'] ) ? rest_sanitize_boolean( $args['ignore'] ) : true;
		$results = array();
		$done = 0;
		foreach ( $ids as $id ) {
			$result = $this->core->ignore( $id, $ignore );
			$ok = !is_wp_error( $result ) && $result === true;
			if ( $ok ) $done++;
			$results[] = array( 'entry_id' => $id, 'success' => $ok, 'error' => is_wp_error( $result ) ? $result->get_error_message() : null );
		}
		return array(
			'success' => $done === count( $ids ),
			'ignored' => $ignore,
			'succeeded' => $done,
			'failed' => count( $ids ) - $done,
			'results' => $results,
			'nothing_deleted' => true,
		);
	}

	private function tool_operate( $args, $operation ) {
		$ids = $this->read_entry_ids( $args );
		// With Skip Trash on, trashing deletes the file outright. This tool promises
		// something reversible, so it refuses rather than quietly destroying files.
		if ( $operation === 'trash' && $this->core->get_option( 'skip_trash' ) ) {
			throw new RuntimeException( 'Media Cleaner is set to skip the trash, so trashing would delete these files permanently and wpmc_trash will not do that. Use wpmc_delete_permanently if that is really what is wanted, or turn Skip Trash off in the settings.' );
		}
		$results = array();
		$done = 0;
		$skipped = 0;
		foreach ( $ids as $id ) {
			// delete() erases an item that is already in the trash, because that is how
			// the trash is emptied. Trashing must never reach that: a retried call, or an
			// id that is already trashed, would destroy the file for good while this
			// reports it as reversible. Both operations are no-ops when there is nothing
			// to do, so repeating a call is always safe.
			$issue = $this->core->get_issue( $id );
			if ( $issue ) {
				$in_trash = (int) $issue->deleted === 1;
				if ( ( $operation === 'trash' && $in_trash ) || ( $operation === 'recover' && !$in_trash ) ) {
					$done++;
					$skipped++;
					$results[] = array(
						'entry_id' => $id,
						'success' => true,
						'skipped' => $operation === 'trash' ? 'already_in_trash' : 'not_in_trash',
						'error' => null,
					);
					continue;
				}
			}
			// initial_deleted says "this was not in the trash", which pins delete() to
			// the quarantine branch. Without it, an overlapping call could trash the row
			// between the check above and the read inside delete(), and the file would be
			// erased instead. It fails loudly rather than deleting.
			$result = $operation === 'trash'
				? $this->core->delete( $id, array( 'initial_deleted' => false ) )
				: $this->core->recover( $id );
			$ok = !is_wp_error( $result ) && $result === true;
			if ( $ok ) $done++;
			$results[] = array( 'entry_id' => $id, 'success' => $ok, 'error' => is_wp_error( $result ) ? $result->get_error_message() : null );
		}
		return array(
			'success' => $done === count( $ids ),
			'operation' => $operation,
			'succeeded' => $done,
			'failed' => count( $ids ) - $done,
			'skipped' => $skipped,
			'results' => $results,
			'reversible' => true,
			'how_to_undo' => $operation === 'trash'
				? 'These files are in the Media Cleaner trash. wpmc_recover restores them at any time, as long as the trash is not emptied.'
				: 'These files are back in place.',
		);
	}

	private function tool_delete_permanently( $args ) {
		$confirm = isset( $args['confirm'] ) ? (string) $args['confirm'] : '';
		if ( $confirm !== 'PERMANENT' ) {
			return array(
				'success' => false,
				'error' => 'Permanent deletion refused: confirm must be exactly PERMANENT, and only after the user explicitly asked for it, knowing the files cannot be restored.',
				'safer_alternative' => 'Use wpmc_trash instead, it is reversible.',
			);
		}
		$ids = $this->read_entry_ids( $args );
		// core->delete() moves an untouched entry to the trash, and erases it when it
		// is already trashed, so it is called until the entry is really gone. With the
		// "skip trash" setting the very first call already erases it.
		$results = array();
		$done = 0;
		foreach ( $ids as $id ) {
			$issue = $this->core->get_issue( $id );
			if ( !$issue ) {
				$results[] = array( 'entry_id' => $id, 'success' => false, 'error' => 'This entry no longer exists.' );
				continue;
			}
			$error = null;
			for ( $pass = 0; $pass < 2; $pass++ ) {
				$result = $this->core->delete( $id );
				if ( is_wp_error( $result ) || $result !== true ) {
					$error = is_wp_error( $result ) ? $result->get_error_message() : 'The entry could not be deleted.';
					break;
				}
				// The entry row is removed by a permanent deletion, which is the proof it is gone.
				if ( !$this->core->get_issue( $id ) ) {
					$error = null;
					break;
				}
				$error = 'The entry is still present after the deletion.';
			}
			$ok = $error === null;
			if ( $ok ) $done++;
			$results[] = array( 'entry_id' => $id, 'success' => $ok, 'error' => $error );
		}
		return array(
			'success' => $done === count( $ids ),
			'permanently_deleted' => $done,
			'failed' => count( $ids ) - $done,
			'results' => $results,
			'reversible' => false,
			'warning' => 'These files are gone. Only a backup can bring them back.',
		);
	}

	#endregion
}
