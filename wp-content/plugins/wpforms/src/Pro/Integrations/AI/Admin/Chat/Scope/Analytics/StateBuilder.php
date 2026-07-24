<?php
/**
 * Suppress unrelated inspections.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics;

use WPForms\Pro\Analytics\SubfieldLabels;

/**
 * State assembly, portfolio metadata, and form loading for the Analytics scope.
 *
 * Extracted from Analytics to comply with the 20-method-per-class threshold.
 *
 * @since 2.0.0
 */
class StateBuilder {

	/**
	 * Surface slug for the Analytics admin page.
	 *
	 * @since 2.0.0
	 */
	public const ANALYTICS_SURFACE_SLUG = 'wpforms-analytics';

	/**
	 * Top N forms in the portfolio summary.
	 *
	 * @since 2.0.0
	 */
	private const PORTFOLIO_LIMIT = 50;

	/**
	 * Parent scope for fetch_* delegation.
	 *
	 * @since 2.0.0
	 *
	 * @var Analytics
	 */
	private $scope;

	/**
	 * Memoized portfolio field metadata map.
	 *
	 * Shape: `[ form_id => [ field_id => [ 'type' => string, 'label' => string ] ] ]`.
	 * Built lazily by `get_portfolio_field_metadata()`; persists for the duration
	 * of one AJAX call so multi-source turns reuse the form_data scan.
	 *
	 * @since 2.0.0
	 *
	 * @var array|null
	 */
	private $portfolio_field_metadata;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param Analytics $scope Parent scope for fetch_* delegation.
	 */
	public function __construct( Analytics $scope ) {

		$this->scope = $scope;
	}

	/**
	 * Single-form state for the Analytics admin page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $request Request payload.
	 *
	 * @return array
	 */
	public function build_single_form_state( array $request ): array {

		$form_id = $this->resolve_form_id( $request );

		$state = [
			'form_id' => $form_id,
		];

		if ( $form_id === 0 ) {
			return $state;
		}

		$goal = get_post_meta( $form_id, 'wpforms_analytics_goal', true );

		if ( is_array( $goal ) && isset( $goal['conversion_rate'] ) ) {
			$state['goal'] = $goal;
		}

		$form_summary = $this->build_form_summary( $form_id );

		if ( $form_summary !== null ) {
			$state['form_summary'] = $form_summary;
		}

		$form_stats = $this->scope->fetch_form_stats( $request );

		if ( $form_stats !== [] ) {
			$state['form_stats'] = $form_stats;
		}

		// Pre-load per-field stats for the selected window so "which field hurts
		// performance most?" answers can be rendered without spending a tool-call
		// round trip on `field_stats`.
		$field_stats = $this->scope->fetch_field_stats( $request );

		if ( ! empty( $field_stats['fields'] ) ) {
			$state['field_stats'] = $field_stats;
		}

		// Pre-load a 30-day daily submissions trend so summary-style answers
		// ("summarize this form's overall performance") can render both the
		// metrics cards and a sparkline-style line chart without spending a
		// `request_data` round trip on `form_stats_trend`. The LLM can still
		// fetch a different granularity (week / month) on demand later.
		$trend_payload                 = $request;
		$trend_payload['request_data'] = [ 'args' => [ 'granularity' => 'day' ] ];
		$form_stats_trend              = $this->scope->fetch_form_stats_trend( $trend_payload );

		if ( ! empty( $form_stats_trend['rows'] ) ) {
			$state['form_stats_trend'] = $form_stats_trend;
		}

		return $state;
	}

	/**
	 * Build a lightweight form identification summary.
	 *
	 * Ships title, description, and the field list (id, label, type) so the
	 * middleware can describe the focused form without an extra round trip.
	 * Returns null when the form is missing or unreadable.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array|null
	 */
	public function build_form_summary( int $form_id ): ?array {

		$form = $this->get_form_post( $form_id );

		if ( $form === null ) {
			return null;
		}

		return $this->build_form_summary_payload( $form_id, $form );
	}

	/**
	 * Shape the `form_summary` payload from a loaded form post object.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param object $form    Loaded form post object with `post_title`/`post_content`.
	 *
	 * @return array
	 */
	public function build_form_summary_payload( int $form_id, object $form ): array {

		$form_data   = json_decode( $form->post_content, true );
		$settings    = is_array( $form_data['settings'] ?? null ) ? $form_data['settings'] : [];
		$raw_fields  = is_array( $form_data['fields'] ?? null ) ? $form_data['fields'] : [];
		$description = (string) ( $settings['form_desc'] ?? '' );

		// Strip top-level keys with zero optimization value and high sensitivity.
		unset( $settings['payments'], $settings['providers'] );

		// Full field definitions for form-structure reasoning.
		$fields = array_values(
			array_filter(
				$raw_fields,
				static function ( $field ) {

					return is_array( $field ) && isset( $field['id'], $field['type'] );
				}
			)
		);

		return [
			'form_id'     => $form_id,
			'title'       => (string) $form->post_title,
			'description' => $description,
			'fields'      => $fields,
			'settings'    => $this->redact_sensitive_values( $settings ),
			'locations'   => $this->get_form_locations( $form_id ),
		];
	}

	/**
	 * Load a form post object with its content, bypassing the capability check.
	 *
	 * Returns null when the form handler is unavailable, the form is missing, or
	 * its `post_content` is empty — so callers can early-return on a single guard.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return object|null Form post object, or null when unreadable.
	 */
	public function get_form_post( int $form_id ): ?object {

		$form_obj = wpforms()->obj( 'form' );

		if ( ! $form_obj ) {
			return null;
		}

		$form = $form_obj->get( $form_id, [ 'cap' => false ] );

		if ( ! $form || empty( $form->post_content ) ) {
			return null;
		}

		return $form;
	}

	/**
	 * Portfolio state for non-analytics surfaces.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function build_portfolio_state(): array {

		$db = wpforms()->obj( 'analytics_db' );

		$rows = $db ? (array) $db->get_top_forms( self::PORTFOLIO_LIMIT ) : [];

		return [
			'forms_summary' => $rows,
		];
	}

	/**
	 * Resolve a `(form_id, field_id) -> { type, label, subfields }` map across the portfolio's top forms.
	 *
	 * Reads each form's `wpforms` post-meta `form_data['fields']`. Used by
	 * `fields_ranking` for row enrichment and by the type rollup. Memoized for
	 * the lifetime of one AJAX call.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_portfolio_field_metadata(): array {

		if ( $this->portfolio_field_metadata !== null ) {
			return $this->portfolio_field_metadata;
		}

		$this->portfolio_field_metadata = [];

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db ) {
			return $this->portfolio_field_metadata;
		}

		$top_forms = (array) $db->get_top_forms( self::PORTFOLIO_LIMIT );

		$this->portfolio_field_metadata = $this->build_portfolio_field_metadata( $top_forms );

		return $this->portfolio_field_metadata;
	}

	/**
	 * Build the `[ form_id => [ field_id => { type, label, subfields } ] ]` map from top-form rows.
	 *
	 * Rows without a positive `form_id` and forms with no resolvable fields are
	 * omitted from the map.
	 *
	 * @since 2.0.0
	 *
	 * @param array $top_forms Rows from `DB::get_top_forms()` (each keyed by `form_id`).
	 *
	 * @return array
	 */
	public function build_portfolio_field_metadata( array $top_forms ): array {

		$form_ids = [];

		foreach ( $top_forms as $row ) {
			$form_id = (int) ( $row['form_id'] ?? 0 );

			if ( $form_id > 0 ) {
				$form_ids[] = $form_id;
			}
		}

		if ( $form_ids === [] ) {
			return [];
		}

		// Prime the post cache for every top form in a single query so the
		// per-form reads below are cache hits instead of an individual post
		// fetch each (avoids an N-query fan-out per ranking call). Guarded so
		// the optimization is a no-op when the core helper is unavailable.
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $form_ids, false, false );
		}

		$metadata = [];

		foreach ( $form_ids as $form_id ) {
			$by_field = $this->build_field_metadata_for_form( $form_id );

			if ( $by_field !== [] ) {
				$metadata[ $form_id ] = $by_field;
			}
		}

		return $metadata;
	}

	/**
	 * Build the `[ field_id => { type, label, subfields } ]` map for a single form.
	 *
	 * Reads `wpforms` post-meta `form_data['fields']`. Used by `fetch_field_stats()`
	 * (single-form drill-down) and by `get_portfolio_field_metadata()` (per-form
	 * iteration across the portfolio's top forms).
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array
	 */
	public function build_field_metadata_for_form( int $form_id ): array {

		$form = $this->get_form_post( $form_id );

		if ( $form === null ) {
			return [];
		}

		$form_data = json_decode( $form->post_content, true );
		$fields    = is_array( $form_data['fields'] ?? null ) ? $form_data['fields'] : [];
		$by_field  = [];

		foreach ( $fields as $field ) {
			$meta = $this->normalise_field_meta( $field );

			if ( $meta === null ) {
				continue;
			}

			$by_field[ $meta['id'] ] = $meta['data'];
		}

		return $by_field;
	}

	/**
	 * Normalize a single raw field definition into a metadata entry.
	 *
	 * WPForms field IDs start at 0 for the first field added — only a missing `id`
	 * key is treated as malformed, not a literal 0 value. Fields without a type are
	 * skipped.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $field Raw field definition from `form_data['fields']`.
	 *
	 * @return array|null Entry shaped as `[ id, data => { type, label, subfields } ]`, or null when invalid.
	 */
	public function normalise_field_meta( $field ): ?array {

		if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
			return null;
		}

		$field_type = (string) ( $field['type'] ?? '' );

		if ( $field_type === '' ) {
			return null;
		}

		return [
			'id'   => (int) $field['id'],
			'data' => [
				'type'      => $field_type,
				'label'     => SubfieldLabels::resolve_field_label( $field ),
				'subfields' => $this->resolve_subfield_labels( $field ),
			],
		];
	}

	/**
	 * Resolve human labels for a composite field's subfields.
	 *
	 * Thin delegation to the shared `SubfieldLabels` resolver so the AI ranking
	 * enrichment and the Analytics admin page / `field_stats` compose identical
	 * labels from one source of truth.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private function resolve_subfield_labels( array $field ): array {

		return SubfieldLabels::resolve( $field );
	}

	/**
	 * Redact sensitive leaf values from a settings array.
	 *
	 * Recursively walks the array and replaces any leaf whose key contains
	 * a sensitive pattern (case-insensitive) with `'[redacted]'`. Empty or
	 * null values are left untouched — nothing to protect.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Settings array to redact.
	 *
	 * @return array Redacted copy.
	 */
	public function redact_sensitive_values( array $data ): array {

		/**
		 * Filter the list of case-insensitive key substrings that trigger redaction.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $patterns Default patterns: 'secret', 'password'.
		 */
		$patterns = (array) apply_filters( // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Preserved from Analytics for backward compatibility.
			'wpforms_pro_integrations_ai_admin_chat_scope_analytics_sensitive_settings_keys',
			[ 'secret', 'password' ]
		);

		array_walk_recursive(
			$data,
			static function ( &$value, $key ) use ( $patterns ) {

				if ( $value === '' || $value === null ) {
					return;
				}

				$key_lower = strtolower( (string) $key );

				foreach ( $patterns as $pattern ) {
					if ( strpos( $key_lower, strtolower( $pattern ) ) !== false ) {
						$value = '[redacted]';

						return;
					}
				}
			}
		);

		return $data;
	}

	/**
	 * Get form embed locations trimmed to LLM-relevant fields.
	 *
	 * Reads the `wpforms_form_locations` post meta managed by `Forms\Locator`
	 * and strips internal IDs, keeping only the page title, relative URL, and
	 * post type — enough for the model to reason about traffic sources.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array List of `[ title, url, type ]` entries, or empty array.
	 */
	public function get_form_locations( int $form_id ): array {

		$raw = get_post_meta( $form_id, 'wpforms_form_locations', true );

		if ( ! is_array( $raw ) || $raw === [] ) {
			return [];
		}

		$locations = [];

		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$locations[] = [
				'title' => (string) ( $entry['title'] ?? '' ),
				'url'   => (string) ( $entry['url'] ?? '' ),
				'type'  => (string) ( $entry['type'] ?? '' ),
			];
		}

		return $locations;
	}

	/**
	 * Build a {form_id => row} map.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows Rows keyed by `form_id`.
	 *
	 * @return array
	 */
	public function index_by_form_id( array $rows ): array {

		$indexed = [];

		foreach ( $rows as $row ) {
			$row     = (array) $row;
			$form_id = (int) ( $row['form_id'] ?? 0 );

			if ( $form_id === 0 ) {
				continue;
			}

			$indexed[ $form_id ] = $row;
		}

		return $indexed;
	}

	/**
	 * Resolve the requested form_id from the payload.
	 *
	 * Precedence: `request_data.args.form_id` (LLM-driven) → `pageState.analytics.form_id`
	 * (surface-driven from URL) → 0 (unresolved). The args path lets the data source
	 * work on any surface, not just the Analytics admin page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return int Form ID, or 0 if not present.
	 */
	public function resolve_form_id( array $payload ): int {

		$args         = (array) ( $payload['request_data']['args'] ?? [] );
		$args_form_id = (int) ( $args['form_id'] ?? 0 );

		if ( $args_form_id > 0 ) {
			return $args_form_id;
		}

		return $this->page_state_form_id( $payload );
	}

	/**
	 * Load a form's decoded `form_data` payload for stats enrichment.
	 *
	 * Returns an empty array when the form can't be read so callers can pass
	 * the result straight to `Stats::get_field_stats()` — it tolerates an empty
	 * `fields_def` and just yields zero enriched rows.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array
	 */
	public function load_form_data( int $form_id ): array {

		$form_obj = wpforms()->obj( 'form' );

		if ( ! $form_obj ) {
			return [];
		}

		$form_data = $form_obj->get( $form_id, [ 'content_only' => true ] );

		return is_array( $form_data ) ? $form_data : [];
	}

	/**
	 * Read the surface-supplied `pageState.analytics.form_id`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return int Form ID, or 0 when absent.
	 */
	private function page_state_form_id( array $payload ): int {

		$page_state = (array) ( $payload['pageState'] ?? [] );
		$analytics  = (array) ( $page_state['analytics'] ?? [] );

		return (int) ( $analytics['form_id'] ?? 0 );
	}
}
