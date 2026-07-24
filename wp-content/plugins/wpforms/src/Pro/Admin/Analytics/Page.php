<?php

namespace WPForms\Pro\Admin\Analytics;

use WP_Post;
use WPForms\Admin\Helpers\Datepicker;
use WPForms\Analytics\Analytics as AnalyticsFeature;
use WPForms\Pro\Analytics\Stats; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement

/**
 * Analytics admin page (Pro only).
 *
 * Renders the WPForms > Analytics dashboard. The page is registered as a
 * hidden admin slug — reachable only via Forms Overview column links, never
 * via the WP admin menu sidebar.
 *
 * @since 2.0.0
 */
class Page {

	/**
	 * Option holding the timestamp Form Analytics first started collecting data.
	 * Computed once (from the earliest aggregate date) and reused for the
	 * AI-notice 7-day gate.
	 *
	 * @since 2.0.0
	 */
	private const COLLECTING_SINCE_OPTION = 'wpforms_analytics_collecting_since';

	/**
	 * Number of days data must have been collected before the AI notice appears.
	 *
	 * @since 2.0.0
	 */
	private const AI_NOTICE_DELAY_DAYS = 7;

	/**
	 * Validated form id from the URL.
	 *
	 * @since 2.0.0
	 *
	 * @var int|null
	 */
	private $form_id;

	/**
	 * Validated form post.
	 *
	 * @since 2.0.0
	 *
	 * @var WP_Post|null
	 */
	private $form;

	/**
	 * Cached list of forms the current user can access, ascending by ID.
	 * Drives the form selector dropdown and the prev/next navigation in the
	 * title bar. Same shape as the Entries List page's $this->forms.
	 *
	 * @since 2.0.0
	 *
	 * @var WP_Post[]
	 */
	private $forms = [];

	/**
	 * Date range tuple from Datepicker::process_timespan(): [start, end, days, label].
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $timespan = [];

	/**
	 * Hybrid form-level stats for the selected date range. Keys: views, submissions,
	 * conversion_rate, abandonments, abandonment_pct, errors, error_pct, interactions.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $stats = [];

	/**
	 * Analytics stats class instance.
	 *
	 * @since 2.0.0
	 *
	 * @var Stats|null
	 */
	private $stats_obj;

	/**
	 * Goal post meta tuple [conversion_rate, set_at] or null when unset.
	 *
	 * @since 2.0.0
	 *
	 * @var array|null
	 */
	private $goal = null;

	/**
	 * Form definition (fields, settings) used to enrich field stats with labels/types.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $form_data = [];

	/**
	 * Hybrid field-level stats from `Stats::get_field_stats()`. Each row carries
	 * `{field_id, subfield_key, label, type (lowercase), views, click_count,
	 * focus_count, input_count, interactions, completion_rate, abandonments,
	 * abandonment_pct, errors, error_pct, avg_time_s}`. Composite fields surface
	 * one labeled row per subfield (`label` = "Field: Sub", `subfield_key` set);
	 * simple fields keep a single empty-key row. Non-interactive types are
	 * pre-filtered. The table renders each row generically, so subfield rows
	 * appear as their own rows with no field_id-keyed collation.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $field_stats = [];

	/**
	 * Determine if the class is allowed to load.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function allow_load(): bool {

		return wpforms_is_admin_page( 'analytics' );
	}

	/**
	 * Start the engine.
	 *
	 * @since 2.0.0
	 */
	public function init(): void {

		if ( ! $this->allow_load() ) {
			return;
		}

		$this->stats_obj = wpforms()->obj( 'analytics_stats' );

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	private function hooks(): void {

		add_filter( 'wpforms_admin_flyoutmenu', '__return_false' );
		add_action( 'admin_menu',            [ $this, 'register_hidden_page' ], 9 );
		add_action( 'current_screen',        [ $this, 'set_context' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wpforms_admin_page',    [ $this, 'output' ] );
		add_filter( 'admin_title',           [ $this, 'set_document_title' ], 10, 2 );

		// The page is parentless (hidden), so the sidebar highlights nothing. The
		// submenu_file filter both marks "All Forms" active and opens the WPForms
		// menu, so the analytics page reads as a sub-view of the forms list.
		add_filter( 'submenu_file', [ $this, 'highlight_all_forms_submenu' ] );
	}

	/**
	 * Register the page as a hidden admin slug.
	 *
	 * The canonical WordPress idiom for "routable but invisible" pages is to pass
	 * null as the parent_slug to add_submenu_page(). The page is added to
	 * $_registered_pages so admin.php?page=wpforms-analytics resolves, but it is
	 * not appended to any $submenu array - so no sidebar entry is rendered. This
	 * also keeps WP's get_admin_page_parent() / user_can_access_admin_page()
	 * resolution behavior intact, which the alternative
	 * add_submenu_page(parent) + remove_submenu_page() breaks (the cap inheritance
	 * lookup walks $submenu and fails when the entry was removed).
	 *
	 * @since 2.0.0
	 */
	public function register_hidden_page(): void {

		add_submenu_page(
			'', // No parent: page is hidden from the menu but the slug is routable.
			esc_html__( 'Forms Analytics', 'wpforms' ),
			esc_html__( 'Forms Analytics', 'wpforms' ),
			wpforms()->obj( 'access' )->get_menu_cap( 'view_entries' ),
			'wpforms-analytics',
			[ $this, 'admin_page_callback' ]
		);
	}

	/**
	 * Mark the WPForms "All Forms" item active and keep its menu open while the analytics page is open.
	 *
	 * Returning the overview slug highlights the "All Forms" submenu entry. The
	 * top-level menu needs more work: the page is parentless, so a `parent_file`
	 * filter would not stick — WordPress runs get_admin_page_parent() right after
	 * the filters (in menu-header.php), which recomputes $parent_file from the
	 * menu tree and resolves a parentless page to an empty string, collapsing the
	 * WPForms menu. This filter fires immediately before that call, so mapping the
	 * empty parent to the overview slug makes the lookup return wpforms-overview
	 * and the menu renders current and open. Registered only on the analytics page
	 * (hooks() is gated by allow_load()), so other screens are unaffected.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function highlight_all_forms_submenu(): string {

		global $_wp_real_parent_file;

		// The overview slug is the canonical "All Forms" parent; remapping the
		// empty parent here is the only point that survives get_admin_page_parent().
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_wp_real_parent_file[''] = 'wpforms-overview';

		return 'wpforms-overview';
	}

	/**
	 * Supply the document title for the parentless analytics page.
	 *
	 * The page is registered without a parent, so it is absent from the menu tree
	 * that `get_admin_page_title()` walks, leaving the `<title>` empty. WordPress
	 * has already appended the " &lsaquo; {site} &#8212; WordPress" suffix to
	 * `$admin_title`; prepend the page name so the tab reads like every other
	 * admin screen. Registered only on this page (hooks() is gated by allow_load()).
	 *
	 * @since 2.0.0
	 *
	 * @param string $admin_title Full document title assembled by WordPress.
	 * @param string $title       Page-title portion (empty for this parentless page).
	 *
	 * @return string
	 */
	public function set_document_title( string $admin_title, string $title ): string {

		// Only fill in the title when WordPress could not resolve one itself.
		if ( $title !== '' ) {
			return $admin_title;
		}

		return esc_html__( 'Analytics', 'wpforms' ) . $admin_title;
	}

	/**
	 * Page callback registered with add_submenu_page().
	 *
	 * Fires the wpforms_admin_page action so the existing dispatcher invokes output().
	 *
	 * @since 2.0.0
	 */
	public function admin_page_callback(): void {

		/**
		 * Fires to show the WPForms admin page.
		 *
		 * @since 2.0.0
		 */
		do_action( 'wpforms_admin_page' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Load all the context needed to render the page (form, timespan, stats, goal).
	 *
	 * Aborts via redirect if form_id is missing, the form is not published, or
	 * the current user lacks view_entries_form_single capability for this form.
	 * The form-scoped capability check is the actual security boundary - the
	 * routing-level manage_options check on add_submenu_page just makes the page
	 * reachable; this gate is what enforces per-form access on Pro.
	 *
	 * @since 2.0.0
	 */
	public function set_context(): void {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = empty( $_REQUEST['form_id'] ) ? 0 : absint( $_REQUEST['form_id'] );

		if ( ! $this->prepare_state( $form_id ) ) {
			$this->redirect_to_overview();
		}
	}

	/**
	 * Validate the form id and populate all instance state used by render
	 * methods and the AJAX response builder. Returns false when the form id is
	 * missing/unknown/draft/trashed or the user lacks per-form access. Reused
	 * by both the page-render path (set_context) and the AJAX table_data path.
	 *
	 * `$date_param` is the explicit "YYYY-MM-DD - YYYY-MM-DD" string from an
	 * AJAX caller. When null we fall back to `Datepicker::process_timespan()`
	 * which reads `$_GET['date']` — that's the right behaviour for the
	 * server-rendered page request. The AJAX path must pass the POSTed value
	 * because `process_timespan()` uses `filter_input(INPUT_GET, ...)` which
	 * doesn't pick up runtime `$_GET` mutations.
	 *
	 * @since 2.0.0
	 *
	 * @param int         $form_id    Form ID.
	 * @param string|null $date_param Optional date string ("YYYY-MM-DD - YYYY-MM-DD"). When null, reads $_GET via Datepicker::process_timespan().
	 *
	 * @return bool True when state was populated; false when the request must be rejected.
	 */
	public function prepare_state( int $form_id, ?string $date_param = null ): bool {

		if ( ! $form_id ) {
			return false;
		}

		if ( ! wpforms_current_user_can( 'view_entries_form_single', $form_id ) ) {
			return false;
		}

		$form = wpforms()->obj( 'form' )->get( $form_id, [ 'cap' => 'view_entries_form_single' ] );

		if ( ! $form instanceof WP_Post || $form->post_status !== 'publish' ) {
			return false;
		}

		$this->form     = $form;
		$this->form_id  = (int) $form->ID;
		$this->forms    = $this->load_forms();
		$this->timespan = $this->resolve_timespan( $date_param );

		// Resolve the stats object here, not only in init(): the AJAX table_data
		// path (and the goal set/delete re-render) reuse prepare_state() without
		// going through init()'s allow_load() gate, so stats_obj would be null and
		// every stat/field would collapse to zero.
		$this->stats_obj = $this->stats_obj ?? wpforms()->obj( 'analytics_stats' );

		// form_data must load before the form-level stats: the cards reconcile their
		// field-derived totals to the displayed field rows, which needs the field defs.
		$this->form_data   = wpforms()->obj( 'form' )->get( $this->form_id, [ 'content_only' => true ] );
		$this->stats       = $this->load_form_stats();
		$this->field_stats = $this->load_field_stats();

		$goal       = get_post_meta( $this->form_id, 'wpforms_analytics_goal', true );
		$this->goal = ( is_array( $goal ) && isset( $goal['conversion_rate'] ) ) ? $goal : null;

		return true;
	}

	/**
	 * Resolve a 4-tuple timespan from an explicit date string or, when null,
	 * from $_GET via Datepicker::process_timespan().
	 *
	 * Shape: [ DateTimeImmutable $start, DateTimeImmutable $end, int|string $days, string $label ].
	 * Mirrors what Datepicker::process_timespan() returns so the rest of the
	 * Page code path doesn't need to branch on AJAX vs render origin.
	 *
	 * @since 2.0.0
	 *
	 * @param string|null $date_param Explicit "YYYY-MM-DD - YYYY-MM-DD" string, or null for the $_GET fallback.
	 *
	 * @return array
	 */
	private function resolve_timespan( ?string $date_param ): array {

		if ( $date_param === null || $date_param === '' ) {
			return Datepicker::process_timespan();
		}

		$parsed = Datepicker::process_string_timespan( $date_param );

		if ( ! is_array( $parsed ) ) {
			return Datepicker::process_timespan();
		}

		[ $start_date, $end_date ] = $parsed;
		$timezone                  = wp_timezone();
		$current_date              = date_create_immutable( 'now', $timezone )->setTime( 23, 59, 59 );
		$days_diff                 = '';

		// Match Datepicker::process_timespan(): days only counts when the range ends today.
		if ( ! $current_date->diff( $end_date )->format( '%a' ) ) {
			$days_diff = $end_date->diff( $start_date )->format( '%a' );
		}

		[ $days, $timespan_label ] = $this->get_date_filter_keys( $days_diff );

		return [ $start_date, $end_date, $days, $timespan_label ];
	}

	/**
	 * Resolve a date-filter key to its `[ days_key, label ]` tuple.
	 *
	 * Delegates to the canonical lookup on `Datepicker` so the label map lives in
	 * a single place. The labels resolve via the `wpforms-lite` text domain, which
	 * is loaded under Pro, so the displayed strings are unchanged.
	 *
	 * @since 2.0.0
	 *
	 * @param string|int $key Days key, e.g. 0, 1, 7, 30, 90, 365, or '' for custom.
	 *
	 * @return array Tuple [days_key, label].
	 */
	private function get_date_filter_keys( $key ): array {

		return Datepicker::get_date_filter_choices( (string) $key );
	}

	/**
	 * Build the AJAX response payload for `wpforms_analytics_table_data`.
	 *
	 * Must be called after `prepare_state()` has populated instance state.
	 * Shape mirrors what analytics-page.js consumes: stats card values,
	 * pre-rendered conversion-rate HTML (so JS doesn't replicate goal logic),
	 * enriched field rows, and the active datepicker label.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function build_table_data_response(): array {

		[ , $chosen_filter ] = Datepicker::process_datepicker_choices( $this->timespan );

		[ $prev_url, $prev_inactive ] = $this->get_prev_form_link();
		[ $next_url, $next_inactive ] = $this->get_next_form_link();

		return [
			'stats'             => [
				'views'                => (int) $this->stats['views'],
				'interactions'         => (int) $this->stats['interactions'],
				'conversion_rate'      => (float) $this->stats['conversion_rate'],
				'conversion_rate_html' => $this->get_conversion_rate_content(),
				'abandonments'         => (int) $this->stats['abandonments'],
				'abandonment_pct'      => (float) $this->stats['abandonment_pct'],
				'errors'               => (int) $this->stats['errors'],
				'error_pct'            => (float) $this->stats['error_pct'],
			],
			'fields'            => $this->build_fields_response(),
			'chosen_filter'     => $chosen_filter,
			// The form selector + prev/next nav let the JS switch forms in place.
			'form_details_html' => $this->get_form_details_html(),
			'nav'               => [
				'prev_url'      => $prev_url,
				'prev_inactive' => $prev_inactive,
				'next_url'      => $next_url,
				'next_inactive' => $next_inactive,
			],
		];
	}

	/**
	 * Shape `$this->field_stats` for the AJAX response. Adds the `m:ss` display
	 * string and the avg-time-seconds value so the JS rebuilder can populate
	 * both the cell text and the `data-sort` attribute.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function build_fields_response(): array {

		$rows = [];

		foreach ( $this->field_stats as $row ) {
			$rows[] = [
				'field_id'        => (int) $row['field_id'],
				// Stats service stores canonical `label` / lowercase `type`.
				// The JS table renderer (assets/pro/js/admin/analytics/modules/table.js)
				// still reads `field_label` / `field_type`, so rename + upper-case at
				// the AJAX wire boundary instead of touching the JS contract.
				'field_label'     => (string) $row['label'],
				'field_type'      => strtoupper( (string) $row['type'] ),
				'views'           => (int) $row['views'],
				'interactions'    => (int) $row['interactions'],
				'abandonments'    => (int) $row['abandonments'],
				'abandonment_pct' => (float) $row['abandonment_pct'],
				'errors'          => (int) $row['errors'],
				'error_pct'       => (float) $row['error_pct'],
				'avg_time_s'      => (int) $row['avg_time_s'],
			];
		}

		return $rows;
	}

	/**
	 * Load hybrid form-level stats for the selected range.
	 *
	 * Thin pass-through to `Stats::get_form_stats()` — the canonical service
	 * also used by the AI chat Analytics scope, so the page and the chat
	 * always see the same shape and the same derivation.
	 *
	 * @since 2.0.0
	 *
	 * @return array Keys: views, submissions, conversion_rate,
	 *               interactions, abandonments, abandonment_pct,
	 *               errors, error_pct.
	 */
	private function load_form_stats(): array {

		if ( ! $this->stats_obj ) {
			return [];
		}

		return $this->stats_obj->get_form_stats(
			$this->form_id,
			$this->timespan[0]->format( 'Y-m-d' ),
			$this->timespan[1]->format( 'Y-m-d' ),
			(array) $this->form_data
		);
	}

	/**
	 * Render one stat card via the shared template.
	 *
	 * @since 2.0.0
	 *
	 * @param string $card_key Data-card identifier for JS targeting.
	 * @param string $label    Card label.
	 * @param string $content  Card content HTML (may include links).
	 * @param string $tooltip  Help-tooltip text shown when hovering the info icon.
	 */
	private function display_stat_card( string $card_key, string $label, string $content, string $tooltip = '' ): void {

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render(
			'admin/analytics/stat-card',
			[
				'card_key' => $card_key,
				'label'    => $label,
				'content'  => $content,
				'tooltip'  => $tooltip,
			],
			true
		);
	}

	/**
	 * Format a "count / pct%" dual-format string.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $count Raw count.
	 * @param float $pct   Percentage.
	 *
	 * @return string
	 */
	private function format_dual( int $count, float $pct ): string {

		return sprintf(
			'%1$s<span class="wpforms-analytics-stat-card-value-secondary"> <span class="wpforms-analytics-stat-card-divider">/</span> %2$s%%</span>',
			esc_html( number_format_i18n( $count ) ),
			esc_html( $this->format_percentage( $pct ) )
		);
	}

	/**
	 * Build the Conversion Rate card content: "Set Goal" link, or arrow + colored "Goal: X%" link when a goal is set.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_conversion_rate_content(): string {

		$current = $this->stats['conversion_rate'] ?? 0.0;
		$rate    = $this->format_percentage( (float) $current ) . '%';

		if ( $this->goal === null ) {
			return sprintf(
				'%1$s <a href="#" class="wpforms-analytics-set-goal-link">%2$s</a>',
				esc_html( $rate ),
				esc_html__( 'Set Goal', 'wpforms' )
			);
		}

		$goal_rate = (float) $this->goal['conversion_rate'];
		$state     = $current >= $goal_rate ? 'met' : 'below';

		return sprintf(
			'%1$s <span class="wpforms-analytics-goal wpforms-analytics-goal-%2$s"><i class="wpforms-analytics-goal-arrow" aria-hidden="true"></i><a href="#" class="wpforms-analytics-edit-goal-link" data-goal="%3$s" title="%6$s">%4$s %5$s%%</a></span>',
			esc_html( $rate ),
			esc_attr( $state ),
			esc_attr( (string) $goal_rate ),
			esc_html__( 'Goal:', 'wpforms' ),
			esc_html( $this->format_percentage( $goal_rate ) ),
			esc_attr__( 'Edit Conversion Rate Goal', 'wpforms' )
		);
	}

	/**
	 * Format a percentage, trimming a redundant trailing ".0" for any whole number
	 * (7.0% becomes "7%", 7.5% stays "7.5%"). Other values keep a single decimal.
	 *
	 * @since 2.0.0
	 *
	 * @param float $pct Percentage value.
	 *
	 * @return string
	 */
	private function format_percentage( float $pct ): string {

		$rounded  = round( $pct, 1 );
		$decimals = floor( $rounded ) === $rounded ? 0 : 1;

		return number_format_i18n( $pct, $decimals );
	}

	/**
	 * Public proxy for the Conversion Rate card content. Used by the goal AJAX
	 * handlers to re-render the card after a set/delete without duplicating the
	 * met/below/i18n logic. Must be called after prepare_state().
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_conversion_rate_html(): string {

		return $this->get_conversion_rate_content();
	}

	/**
	 * Render the 5 stat cards row.
	 *
	 * @since 2.0.0
	 */
	private function output_stats_cards(): void {

		?>
		<div class="wpforms-analytics-stat-cards">
		<?php
		$this->display_stat_card(
			'views',
			__( 'Views', 'wpforms' ),
			number_format_i18n( $this->stats['views'] ),
			__( 'Total number of times this form was displayed to a visitor.', 'wpforms' )
		);
		$this->display_stat_card(
			'interactions',
			__( 'Interactions', 'wpforms' ),
			number_format_i18n( $this->stats['interactions'] ),
			__( 'An interaction is counted each time a user clicks or focuses on a form field.', 'wpforms' )
		);
		$this->display_stat_card(
			'conversion-rate',
			__( 'Conversion Rate', 'wpforms' ),
			$this->get_conversion_rate_content(),
			__( 'Percentage of form views that resulted in a submission.', 'wpforms' )
		);
		$this->display_stat_card(
			'abandonments',
			__( 'Abandonments', 'wpforms' ),
			$this->format_dual( (int) $this->stats['abandonments'], (float) $this->stats['abandonment_pct'] ),
			__( 'Fields that were seen and interacted with but not submitted.', 'wpforms' )
		);
		$this->display_stat_card(
			'errors',
			__( 'Errors', 'wpforms' ),
			$this->format_dual( (int) $this->stats['errors'], (float) $this->stats['error_pct'] ),
			__( 'Validation errors encountered across all fields.', 'wpforms' )
		);
		?>
		</div>
		<?php
	}

	/**
	 * Load hybrid field-level stats for the selected range.
	 *
	 * Thin pass-through to `Stats::get_field_stats()` — the canonical service
	 * also used by the AI chat Analytics scope. Returns rows with canonical
	 * keys (`label`, lowercase `type`); the JS table contract is preserved by
	 * `build_fields_response()` and the initial server render uppercases at
	 * the template boundary.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function load_field_stats(): array {

		if ( ! $this->stats_obj ) {
			return [];
		}

		return $this->stats_obj->get_field_stats(
			$this->form_id,
			$this->timespan[0]->format( 'Y-m-d' ),
			$this->timespan[1]->format( 'Y-m-d' ),
			(array) $this->form_data
		);
	}

	/**
	 * Format seconds as "m:ss".
	 *
	 * @since 2.0.0
	 *
	 * @param int $seconds Seconds.
	 *
	 * @return string
	 */
	private function format_avg_time( int $seconds ): string {

		return sprintf( '%1$d:%2$02d', (int) floor( $seconds / 60 ), $seconds % 60 );
	}

	/**
	 * Render the "Form Fields" heading row + the field-level metrics table.
	 *
	 * @since 2.0.0
	 */
	private function output_field_table(): void {

		?>
		<div class="wpforms-analytics-field-table-wrap">

			<div class="wpforms-analytics-field-table-header">
				<h2><?php esc_html_e( 'Form Fields', 'wpforms' ); ?></h2>
				<button type="button"
						class="wpforms-btn wpforms-btn-sm wpforms-btn-blue-outline inactive wpforms-analytics-reset-order">
					<?php esc_html_e( 'Reset Order', 'wpforms' ); ?>
				</button>
			</div>

			<div class="wpforms-analytics-field-table-scroll">
				<table id="wpforms-analytics-field-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Views', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Interactions', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Abandonments', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Errors', 'wpforms' ); ?></th>
							<th><?php esc_html_e( 'Avg. Time (m:s)', 'wpforms' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $this->output_field_table_rows(); ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the data rows of the field-stats table, or a single empty-state row.
	 *
	 * @since 2.0.0
	 */
	private function output_field_table_rows(): void {

		if ( empty( $this->field_stats ) ) {
			?>
			<tr class="no-items">
				<td colspan="7"><?php esc_html_e( 'No analytics data for this period.', 'wpforms' ); ?></td>
			</tr>
			<?php

			return;
		}

		foreach ( $this->field_stats as $row ) :
			?>
			<tr>
				<td><?php echo esc_html( (string) $row['label'] ); ?></td>
				<td><?php echo esc_html( strtoupper( (string) $row['type'] ) ); ?></td>
				<td data-order="<?php echo esc_attr( $row['views'] ); ?>"><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
				<td data-order="<?php echo esc_attr( $row['interactions'] ); ?>"><?php echo esc_html( number_format_i18n( $row['interactions'] ) ); ?></td>
				<td data-order="<?php echo esc_attr( $row['abandonments'] ); ?>">
					<?php echo wp_kses_post( $this->format_dual( (int) $row['abandonments'], (float) $row['abandonment_pct'] ) ); ?>
				</td>
				<td data-order="<?php echo esc_attr( $row['errors'] ); ?>">
					<?php echo wp_kses_post( $this->format_dual( (int) $row['errors'], (float) $row['error_pct'] ) ); ?>
				</td>
				<td data-order="<?php echo esc_attr( $row['avg_time_s'] ); ?>">
					<?php echo esc_html( $this->format_avg_time( (int) $row['avg_time_s'] ) ); ?>
				</td>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Build the analytics page URL for a given form id.
	 *
	 * Single source for the `admin.php?page=wpforms-analytics&form_id=N` links
	 * used by the current-screen URL, the form selector items, and prev/next nav.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID to target.
	 *
	 * @return string
	 */
	private function get_form_analytics_url( int $form_id ): string {

		return add_query_arg(
			[
				'page'    => 'wpforms-analytics',
				'form_id' => $form_id,
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * URL of this page with form_id preserved.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_current_screen_url(): string {

		return $this->get_form_analytics_url( (int) $this->form_id );
	}

	/**
	 * Build the popdown `<li>` items for the form selector, excluding the
	 * currently-selected form. Shared by the page render and the AJAX
	 * form-switch response so both stay in sync.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function build_form_list_items_html(): string {

		$html = '';

		foreach ( $this->forms as $form ) {
			if ( (int) $form->ID === $this->form_id ) {
				continue;
			}

			$url = $this->get_form_analytics_url( absint( $form->ID ) );

			$html .= sprintf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( $url ),
				esc_html( $form->post_title )
			);
		}

		return $html;
	}

	/**
	 * Render the shared form-details fragment (sub-label + title + selector) to a
	 * string. Reused by the AJAX form-switch path so the title and dropdown are
	 * rebuilt server-side rather than reconstructed in JS.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_form_details_html(): string {

		return wpforms_render(
			'admin/components/form-details',
			[
				'sub_label'            => __( 'Select Form', 'wpforms' ),
				'current_form_id'      => $this->form_id,
				'current_form_title'   => $this->form->post_title,
				'form_list_items_html' => $this->build_form_list_items_html(),
			],
			true
		);
	}

	/**
	 * Render the SELECT FORM row: form selector + datepicker + Export/Print buttons.
	 *
	 * @since 2.0.0
	 */
	private function output_top_bar(): void {

		$current_url = $this->get_current_screen_url();

		[ $choices, $chosen_filter, $value ] = Datepicker::process_datepicker_choices( $this->timespan );

		?>
		<div class="form-details">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_form_details_html();
			?>

			<div class="form-details-actions">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo wpforms_render(
					'admin/components/datepicker',
					[
						'id'            => 'analytics',
						'action'        => $current_url,
						'chosen_filter' => $chosen_filter,
						'choices'       => $choices,
						'value'         => $value,
						'hidden_fields' => [ 'form_id' ],
					],
					true
				);
				?>

				<a href="#" class="wpforms-btn wpforms-btn-sm wpforms-btn-blue-outline wpforms-analytics-export-csv">
					<?php esc_html_e( 'Export CSV', 'wpforms' ); ?>
				</a>

				<a href="#" class="wpforms-btn wpforms-btn-sm wpforms-btn-blue-outline wpforms-analytics-print">
					<?php esc_html_e( 'Print Report', 'wpforms' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Redirect to Forms Overview.
	 *
	 * @since 2.0.0
	 */
	private function redirect_to_overview(): void {

		wp_safe_redirect( admin_url( 'admin.php?page=wpforms-overview' ) );
		exit;
	}

	/**
	 * Fetch the forms the current user can access for analytics. Filtered by
	 * the same view_entries_form_single capability that gates the page itself
	 * (matches the Entries List page's $this->forms shape).
	 *
	 * @since 2.0.0
	 *
	 * @return WP_Post[]
	 */
	private function load_forms(): array {

		if ( ! wpforms_current_user_can( 'view_forms' ) ) {
			return [];
		}

		$forms = wpforms()->obj( 'form' )->get(
			'',
			[
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'post_type'              => 'wpforms',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( ! is_array( $forms ) ) {
			return [];
		}

		return wpforms()->obj( 'access' )->filter_forms_by_current_user_capability( $forms, 'view_entries_form_single' );
	}

	/**
	 * Compute the URL + inactive flag for the left-arrow anchor in the title bar.
	 *
	 * Pagination direction: the left arrow moves to newer content, which is the next
	 * form by ID (offset +1). See get_next_form_link() for the right arrow.
	 *
	 * @since 2.0.0
	 *
	 * @return array Tuple of [url, is_inactive].
	 */
	private function get_prev_form_link(): array {

		return $this->get_adjacent_form_link( 1 );
	}

	/**
	 * Compute the URL + inactive flag for the right-arrow anchor in the title bar.
	 *
	 * Pagination direction: the right arrow moves to older content, which is the
	 * previous form by ID (offset -1). See get_prev_form_link() for the left arrow.
	 *
	 * @since 2.0.0
	 *
	 * @return array Tuple of [url, is_inactive].
	 */
	private function get_next_form_link(): array {

		return $this->get_adjacent_form_link( -1 );
	}

	/**
	 * Shared implementation for prev/next form anchor computation.
	 *
	 * @since 2.0.0
	 *
	 * @param int $offset Direction: -1 for previous, +1 for next.
	 *
	 * @return array
	 */
	private function get_adjacent_form_link( int $offset ): array {

		if ( empty( $this->forms ) ) {
			return [ '#', true ];
		}

		$form_ids      = array_column( $this->forms, 'ID' );
		$current_index = array_search( $this->form_id, $form_ids, true );

		if ( $current_index === false ) {
			return [ '#', true ];
		}

		$target_index = $current_index + $offset;

		if ( ! isset( $form_ids[ $target_index ] ) ) {
			return [ '#', true ];
		}

		$url = $this->get_form_analytics_url( (int) $form_ids[ $target_index ] );

		return [ $url, false ];
	}

	/**
	 * Render the white title bar (title + Back to All Forms + prev/next nav).
	 *
	 * Reuses .page-title + .wpforms-admin-single-navigation — no analytics-specific styling.
	 *
	 * @since 2.0.0
	 */
	private function output_page_title_bar(): void {

		[ $prev_url, $prev_inactive ] = $this->get_prev_form_link();
		[ $next_url, $next_inactive ] = $this->get_next_form_link();

		$prev_class   = 'wpforms-btn-grey' . ( $prev_inactive ? ' inactive' : '' );
		$next_class   = 'wpforms-btn-grey' . ( $next_inactive ? ' inactive' : '' );
		$overview_url = admin_url( 'admin.php?page=wpforms-overview' );
		?>

		<h1 class="page-title">
			<?php esc_html_e( 'Forms Analytics', 'wpforms' ); ?>

			<a href="<?php echo esc_url( $overview_url ); ?>"
				class="page-title-action wpforms-btn wpforms-btn-orange"
				data-action="back">
				<svg viewBox="0 0 16 14" class="page-title-action-icon">
					<path d="M16 6v2H4l4 4-1 2-7-7 7-7 1 2-4 4h12Z"/>
				</svg>
				<span class="page-title-action-text"><?php esc_html_e( 'Back to All Forms', 'wpforms' ); ?></span>
			</a>

			<div class="wpforms-admin-single-navigation">
				<div class="wpforms-admin-single-navigation-buttons">
					<a href="<?php echo esc_url( $prev_url ); ?>"
						id="wpforms-admin-single-navigation-prev-link"
						class="<?php echo esc_attr( $prev_class ); ?>"
						title="<?php esc_attr_e( 'Previous form', 'wpforms' ); ?>">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
					</a>
					<a href="<?php echo esc_url( $next_url ); ?>"
						id="wpforms-admin-single-navigation-next-link"
						class="<?php echo esc_attr( $next_class ); ?>"
						title="<?php esc_attr_e( 'Next form', 'wpforms' ); ?>">
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				</div>
			</div>
		</h1>
		<?php
	}

	/**
	 * Feature-module manifest consumed by analytics-page.js `loadModules()`.
	 *
	 * Each module is dynamically imported in the browser and attached to
	 * `window.WPFormsAnalytics[ name ]`. Mirrors the AI chat / Builder Themes
	 * module pattern so the whole Analytics feature shares one loading shape.
	 * Paths are relative to the entry script (analytics-page.js) directory.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_modules(): array {

		$min = wpforms_get_min_suffix();

		return [
			[
				'name' => 'table',
				'path' => "./modules/table{$min}.js",
			],
			[
				'name' => 'datepicker',
				'path' => "./modules/datepicker{$min}.js",
			],
			[
				'name' => 'formSwitch',
				'path' => "./modules/form-switch{$min}.js",
			],
			[
				'name' => 'goal',
				'path' => "./modules/goal{$min}.js",
			],
		];
	}

	/**
	 * Enqueue page CSS and JS.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets(): void {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.css',
			[],
			'4.6.9'
		);

		wp_enqueue_script(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.js',
			[ 'jquery' ],
			'4.6.9',
			true
		);

		wp_enqueue_style(
			'tooltipster',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.css',
			[],
			'4.2.6'
		);

		wp_enqueue_script(
			'tooltipster',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.js',
			[ 'jquery' ],
			'4.2.6',
			true
		);

		wp_register_script(
			'wpforms-simple-datatables',
			WPFORMS_PLUGIN_URL . 'assets/pro/lib/simple-datatables.min.js',
			[],
			'10.2.0',
			true
		);

		wp_enqueue_style(
			'wpforms-analytics-page',
			WPFORMS_PLUGIN_URL . "assets/pro/css/admin/analytics-page{$min}.css",
			[],
			WPFORMS_VERSION
		);

		wp_enqueue_script(
			'wpforms-analytics-page',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/analytics/analytics-page{$min}.js",
			[ 'jquery', 'wpforms-flatpickr', 'tooltipster', 'wpforms-simple-datatables' ],
			WPFORMS_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-analytics-page',
			'wpforms_analytics',
			$this->get_localize_data()
		);
	}

	/**
	 * Build the `wpforms_analytics` script data localized for analytics-page.js.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_localize_data(): array {

		return [
			'nonce'       => wp_create_nonce( 'wpforms_analytics' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'formId'      => $this->form_id,
			'locale'      => sanitize_key( wpforms_get_language_code() ),
			'delimiter'   => Datepicker::TIMESPAN_DELIMITER,
			'date_format' => Datepicker::get_wp_date_format_for_momentjs(),
			'modules'     => $this->get_modules(),
			'i18n'        => [
				'nonce_expired'   => esc_html__( 'Your session has expired. Please reload the page.', 'wpforms' ),
				'no_permission'   => esc_html__( 'You do not have permission to view this data.', 'wpforms' ),
				'network_failure' => esc_html__( 'Unable to load analytics data. Please try again.', 'wpforms' ),
				'generic_error'   => esc_html__( 'Something went wrong. Please try again.', 'wpforms' ),
				'retry'           => esc_html__( 'Try Again', 'wpforms' ),
				'reload'          => esc_html__( 'Reload', 'wpforms' ),
				'no_data'         => esc_html__( 'No analytics data for this period.', 'wpforms' ),
				'goal_range'      => esc_html__( 'Please enter a goal between 0.1 and 100.', 'wpforms' ),
			],
		];
	}

	/**
	 * Render the first-visit onboarding banner.
	 *
	 * Uses the existing wpforms-dismiss-container pattern — dismiss handler is
	 * WPForms\Admin\Education\Core::ajax_dismiss() (already wired). Stored in user
	 * meta wpforms_dismissed[edu-analytics-onboarding-banner].
	 *
	 * @since 2.0.0
	 */
	private function output_onboarding_banner(): void {

		$dismissed = get_user_meta( get_current_user_id(), 'wpforms_dismissed', true );
		$dismissed = is_array( $dismissed ) ? $dismissed : [];

		if ( ! empty( $dismissed['edu-analytics-onboarding-banner'] ) ) {
			return;
		}

		echo wpforms_render( 'admin/analytics/onboarding-banner' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the WPForms AI product-education notice.
	 *
	 * Shown once data has been collected for AI_NOTICE_DELAY_DAYS days, until the
	 * user dismisses it. Dismissal uses the standard wpforms-dismiss-container
	 * pattern (WPForms\Admin\Education\Core::ajax_dismiss), stored in user meta
	 * wpforms_dismissed[edu-analytics-ai-notice] — manual-only, never re-shown.
	 *
	 * @since 2.0.0
	 */
	private function output_ai_notice(): void {

		if ( ! $this->should_show_ai_notice() ) {
			return;
		}

		echo wpforms_render( 'admin/analytics/ai-notice' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Whether the WPForms AI notice should render: data has been collecting for
	 * at least AI_NOTICE_DELAY_DAYS days and the user has not dismissed it.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function should_show_ai_notice(): bool {

		$dismissed = get_user_meta( get_current_user_id(), 'wpforms_dismissed', true );
		$dismissed = is_array( $dismissed ) ? $dismissed : [];

		if ( ! empty( $dismissed['edu-analytics-ai-notice'] ) ) {
			return false;
		}

		$collecting_since = $this->get_collecting_since_timestamp();

		if ( ! $collecting_since ) {
			return false;
		}

		return ( time() - $collecting_since ) >= self::AI_NOTICE_DELAY_DAYS * DAY_IN_SECONDS;
	}

	/**
	 * Resolve the timestamp Form Analytics first started collecting data.
	 *
	 * Computed once from the earliest aggregate date and cached in an option, so
	 * later visits read a single option instead of re-querying. Returns 0 when no
	 * data has been collected yet (nothing is cached in that case, so it is
	 * recomputed on the next visit).
	 *
	 * @since 2.0.0
	 *
	 * @return int Unix timestamp, or 0 when no data has been collected yet.
	 */
	private function get_collecting_since_timestamp(): int {

		$collecting_since = (int) get_option( self::COLLECTING_SINCE_OPTION, 0 );

		if ( $collecting_since ) {
			return $collecting_since;
		}

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db || ! method_exists( $db, 'get_first_collected_date' ) ) {
			return 0;
		}

		$first_date = $db->get_first_collected_date();

		if ( $first_date === '' ) {
			return 0;
		}

		$collecting_since = (int) strtotime( $first_date );

		update_option( self::COLLECTING_SINCE_OPTION, $collecting_since, false );

		return $collecting_since;
	}

	/**
	 * Render the Conversion Rate goal popover once. JS moves it under the
	 * Conversion Rate card on open.
	 *
	 * @since 2.0.0
	 */
	private function output_goal_popover(): void {

		/**
		 * Filter the Learn More URL in the conversion-goal popover.
		 *
		 * @since 2.0.0
		 *
		 * @param string $url Default UTM-wrapped wpforms.com URL.
		 */
		$learn_more_url = apply_filters(
			'wpforms_pro_admin_analytics_page_output_goal_popover_learn_more_url',
			wpforms_utm_link(
				AnalyticsFeature::DOC_URL,
				'analytics',
				'Conversion Goal Learn More'
			)
		);

		echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/analytics/goal-popover',
			[
				'learn_more_url' => $learn_more_url,
			],
			true
		);
	}

	/**
	 * Render the analytics page.
	 *
	 * Body is filled in subsequent tasks; this base step only emits the wrapper so the
	 * WPForms branded header and footer render correctly around an empty card area.
	 *
	 * @since 2.0.0
	 */
	public function output(): void {
		?>
		<div id="wpforms-analytics" class="wrap wpforms-admin-wrap wpforms-analytics-page">

			<?php $this->output_page_title_bar(); ?>

			<div class="wpforms-admin-content">
				<?php $this->output_onboarding_banner(); ?>
				<?php $this->output_ai_notice(); ?>
				<?php $this->output_top_bar(); ?>
				<?php $this->output_stats_cards(); ?>
				<?php $this->output_goal_popover(); ?>
				<?php $this->output_field_table(); ?>
			</div>
		</div>
		<?php
	}
}
