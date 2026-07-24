<?php

namespace WPForms\Pro\Admin\Analytics;

/**
 * Analytics admin AJAX handlers (Pro only).
 *
 * @since 2.0.0
 */
class Ajax {

	/**
	 * Start the engine.
	 *
	 * @since 2.0.0
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	private function hooks(): void {

		add_action( 'wp_ajax_wpforms_analytics_table_data', [ $this, 'table_data' ] );
		add_action( 'wp_ajax_wpforms_analytics_goal_set', [ $this, 'goal_set' ] );
		add_action( 'wp_ajax_wpforms_analytics_goal_get', [ $this, 'goal_get' ] );
		add_action( 'wp_ajax_wpforms_analytics_goal_delete', [ $this, 'goal_delete' ] );
	}

	/**
	 * AJAX handler for `wpforms_analytics_table_data`.
	 *
	 * Returns the stats card payload + enriched field rows for the requested
	 * form id and date range. Consumed by analytics-page.js when the user
	 * applies a new date range — keeps the page from full-reloading.
	 *
	 * @since 2.0.0
	 */
	public function table_data(): void {

		$form_id = $this->validate_request( false );
		$page    = wpforms()->obj( 'analytics_page' );

		if ( ! $page || ! $page->prepare_state( $form_id, $this->get_posted_date() ) ) {
			$this->send_error( 'invalid_form', esc_html__( 'Invalid form.', 'wpforms' ) );
		}

		wp_send_json_success( $page->build_table_data_response() );
	}

	/**
	 * AJAX handler for `wpforms_analytics_goal_set`. Saves the per-form goal
	 * post meta and returns the re-rendered Conversion Rate card content.
	 *
	 * @since 2.0.0
	 */
	public function goal_set(): void {

		$form_id = $this->validate_request( true );

		// Nonce validated in $this->validate_request().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_rate = isset( $_POST['conversion_rate'] ) ? sanitize_text_field( wp_unslash( $_POST['conversion_rate'] ) ) : '';

		// Round before validating so a value like 0.04 does not slip through as a 0% goal (always "met", and it clashes with the zero-deletes behavior).
		$rate = is_numeric( $raw_rate ) ? round( (float) $raw_rate, 1 ) : 0.0;

		if ( $rate <= 0 || $rate > 100 ) {
			$this->send_error( 'invalid_value', esc_html__( 'Please enter a goal between 0.1 and 100.', 'wpforms' ) );
		}

		$date = $this->get_posted_date();

		$goal = [
			'conversion_rate' => $rate,
			'set_at'          => current_time( 'Y-m-d' ),
		];

		update_post_meta( $form_id, 'wpforms_analytics_goal', $goal );

		wp_send_json_success(
			[
				'conversion_rate_html' => $this->render_conversion_rate_html( $form_id, $date ),
				'goal'                 => $goal,
			]
		);
	}

	/**
	 * AJAX handler for `wpforms_analytics_goal_get`. Reads the current goal. This
	 * is the documented read API; the live page prefills from the card's data-goal
	 * attribute and does not call it.
	 *
	 * @since 2.0.0
	 */
	public function goal_get(): void {

		$form_id = $this->validate_request( false );

		$goal = get_post_meta( $form_id, 'wpforms_analytics_goal', true );
		$goal = ( is_array( $goal ) && isset( $goal['conversion_rate'] ) ) ? $goal : null;

		wp_send_json_success( [ 'goal' => $goal ] );
	}

	/**
	 * AJAX handler for `wpforms_analytics_goal_delete`. Removes the goal and
	 * returns the re-rendered (Set-Goal state) card content.
	 *
	 * @since 2.0.0
	 */
	public function goal_delete(): void {

		$form_id = $this->validate_request( true );
		$date    = $this->get_posted_date();

		delete_post_meta( $form_id, 'wpforms_analytics_goal' );

		wp_send_json_success(
			[
				'conversion_rate_html' => $this->render_conversion_rate_html( $form_id, $date ),
				'goal'                 => null,
			]
		);
	}

	/**
	 * Shared AJAX request validation: nonce, form id, and capability checks.
	 * Sends a JSON error and dies on failure.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $require_manage_options Whether the action needs manage_options.
	 *
	 * @return int Validated form id.
	 */
	private function validate_request( bool $require_manage_options ): int {

		if ( ! check_ajax_referer( 'wpforms_analytics', 'nonce', false ) ) {
			$this->send_error( 'nonce', esc_html__( 'Your session has expired. Please reload the page.', 'wpforms' ) );
		}

		$form_id = absint( $_POST['form_id'] ?? 0 );

		$this->guard_form( $form_id, $require_manage_options );

		return $form_id;
	}

	/**
	 * Shared guard: valid form + capability checks. Sends a JSON error (and dies)
	 * on failure. Holds no superglobal access, so callers do the nonce check.
	 *
	 * @since 2.0.0
	 *
	 * @param int  $form_id                Resolved form id.
	 * @param bool $require_manage_options Whether the action needs manage_options.
	 */
	private function guard_form( int $form_id, bool $require_manage_options ): void {

		if ( ! $form_id || get_post_type( $form_id ) !== 'wpforms' || get_post_status( $form_id ) !== 'publish' ) {
			$this->send_error( 'invalid_form', esc_html__( 'Invalid form.', 'wpforms' ) );
		}

		if ( ! wpforms_current_user_can( 'view_entries_form_single', $form_id ) ) {
			$this->send_error( 'cap', esc_html__( 'You do not have permission to view this data.', 'wpforms' ) );
		}

		if ( $require_manage_options && ! current_user_can( 'manage_options' ) ) {
			$this->send_error( 'cap', esc_html__( 'You do not have permission to change this goal.', 'wpforms' ) );
		}
	}

	/**
	 * Re-render the Conversion Rate card content for the given form + date range,
	 * reusing the page renderer so the goal logic lives in one place.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form id.
	 * @param string $date    Date-range param (may be empty for the default range).
	 *
	 * @return string
	 */
	private function render_conversion_rate_html( int $form_id, string $date ): string {

		$page = new Page();

		$page->prepare_state( $form_id, $date );

		return $page->get_conversion_rate_html();
	}

	/**
	 * Read and sanitize the posted `date` range string.
	 *
	 * The nonce is verified by the caller before this runs. Returns an empty
	 * string when no date was posted, which `Page::prepare_state()` treats as
	 * the `$_GET` datepicker fallback.
	 *
	 * @since 2.0.0
	 *
	 * @return string Sanitized "YYYY-MM-DD - YYYY-MM-DD" string, or empty.
	 */
	private function get_posted_date(): string {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via validate_request() before this runs.
		return isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	}

	/**
	 * Send a JSON error envelope with a machine code and a display message.
	 *
	 * Mirrors the `{ code, message }` shape the analytics-page.js error handler
	 * reads. `wp_send_json_error()` ends the request, so callers do not return.
	 *
	 * @since 2.0.0
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Human-readable, already-escaped error message.
	 *
	 * @return void
	 */
	private function send_error( string $code, string $message ): void {

		wp_send_json_error(
			[
				'code'    => $code,
				'message' => $message,
			]
		);
	}
}
