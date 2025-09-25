<?php

namespace WPForms\Forms;

use WP_Post;
use WPForms\Admin\Notice;
use WPForms\Emails\Tasks\CorruptedSubmissionTask;
use WPForms\Tasks\Task;

/**
 * Class to handle corrupted form submissions.
 *
 * @since 1.9.8
 */
class CorruptedSubmission {

	/**
	 * Option name to store corrupted submissions.
	 *
	 * @since 1.9.8
	 */
	public const REPORTS_OPTION = 'wpforms_corrupted_submissions';

	/**
	 * Initialize the class.
	 *
	 * @since 1.9.8
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.9.8
	 */
	private function hooks(): void {

		// Listen for corrupted submission events.
		add_action( 'wpforms_process_submission_corrupted', [ $this, 'handle_corrupted_submission' ] );

		// Display an admin notice on all WPForms admin pages.
		add_action( 'admin_notices', [ $this, 'display_admin_notice' ] );

		// Register AS task.
		add_filter( 'wpforms_tasks_get_tasks', [ $this, 'register_task' ] );
	}

	/**
	 * Handle corrupted submission detection.
	 *
	 * This method is called when a corrupted form submission is detected.
	 * It stores corruption submissions data and schedules email notification.
	 *
	 * @since 1.9.8
	 *
	 * @param array $form_data Form data containing at least 'id' key with form ID.
	 */
	public function handle_corrupted_submission( array $form_data ): void {

		$form_id = absint( $form_data['id'] ?? 0 );

		if ( empty( $form_id ) ) {
			return;
		}

		// Get existing reports.
		$reports = get_option( self::REPORTS_OPTION, [] );

		if ( ! is_array( $reports ) ) {
			$reports = [];
		}

		if ( empty( $reports[ $form_id ] ) ) {
			$reports[ $form_id ] = [];
		}

		// Add to a report array with the current date and referrer URL.
		$reports[ $form_id ][] = [
			'datetime' => gmdate( 'Y-m-d H:i:s' ),
			'page_url' => $this->get_sanitized_referer_url(),
		];

		// Keep only the last 100 reports per form to prevent database bloat.
		if ( count( $reports[ $form_id ] ) > 100 ) {
			$reports[ $form_id ] = array_slice( $reports[ $form_id ], -100 );
		}

		// Save reports.
		update_option( self::REPORTS_OPTION, $reports );

		// Schedule a single daily task at midnight if not already scheduled.
		$this->schedule_midnight_task_if_needed();
	}

	/**
	 * Get sanitized referer URL for security.
	 *
	 * @since 1.9.8
	 *
	 * @return string Sanitized referer URL or empty string if invalid.
	 */
	private function get_sanitized_referer_url(): string {

		$referer = wp_get_referer();

		if ( empty( $referer ) ) {
			$referer = wpforms_current_url();
		}

		return $this->sanitize_and_validate_url( $referer );
	}

	/**
	 * Schedule a single daily task at midnight if not already scheduled.
	 *
	 * @since 1.9.8
	 */
	private function schedule_midnight_task_if_needed(): void {

		$tasks = wpforms()->obj( 'tasks' );

		// Check if a task is already scheduled.
		if ( ! $tasks || $tasks->is_scheduled( CorruptedSubmissionTask::ACTION ) !== false ) {
			return;
		}

		// Calculate the next midnight timestamp.
		$next_midnight = date_create( 'tomorrow', wp_timezone() )->format( 'U' );

		// Schedule the task to run at midnight.
		$tasks
			->create( CorruptedSubmissionTask::ACTION )
			->once( $next_midnight )
			->register();
	}

	/**
	 * Sanitize and validate URL before display.
	 *
	 * @since 1.9.8
	 *
	 * @param string $url URL to sanitize and validate.
	 *
	 * @return string Sanitized URL or placeholder if invalid.
	 */
	private function sanitize_and_validate_url( string $url ): string {

		// Remove any potential XSS attempts.
		$url = wp_strip_all_tags( $url );

		// Sanitize the URL.
		$sanitized_url = esc_url_raw( $url );

		// Validate the URL format.
		if ( ! filter_var( $sanitized_url, FILTER_VALIDATE_URL ) ) {
			return __( '[Invalid URL]', 'wpforms-lite' );
		}

		// Additional security check - ensure it's a reasonable length.
		if ( strlen( $sanitized_url ) > 2000 ) {
			return __( '[URL too long]', 'wpforms-lite' );
		}

		return $sanitized_url;
	}

	/**
	 * Display admin notice on all WPForms admin pages about corrupted submissions.
	 *
	 * @since 1.9.8
	 */
	public function display_admin_notice(): void {

		// Only show on WPForms admin pages.
		if ( ! wpforms_is_admin_page() ) {
			return;
		}

		// Check if there are any corrupted submissions.
		$reports = get_option( self::REPORTS_OPTION, [] );

		if ( empty( $reports ) || ! is_array( $reports ) ) {
			return;
		}

		// If saved reports do not have any existing form, do not display the notice.
		if ( ! $this->reports_have_exising_forms() ) {
			return;
		}

		// Display the notice using the Notice class with wp_kses for security.
		Notice::error(
			sprintf(
				wp_kses( /* translators: %s - Learn How to Troubleshoot link text. */
					__( '<strong>Corrupted Form Data Detected</strong><br/>Form submissions flagged with an "Attempt to submit corrupted post data." error has been detected. This typically occurs when AJAX form submission is enabled, but the main wpforms.js script fails to load or execute correctly. <a href="%1$s" target="_blank" rel="noopener noreferrer" class="nowrap">%2$s</a>.', 'wpforms-lite' ),
					[
						'strong' => [],
						'br'     => [],
						'a'      => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
							'class'  => [],
						],
					]
				),
				esc_url( wpforms_utm_link( 'https://wpforms.com/docs/resolving-the-attempt-to-submit-corrupted-post-data-error-in-wpforms', 'Admin', 'How to Troubleshoot - Corrupted Post Data' ) ),
				esc_html__( 'Learn How to Troubleshoot', 'wpforms-lite' )
			),
			[
				'dismiss' => true,
				'slug'    => str_replace( 'wpforms_', '', self::REPORTS_OPTION ),
			]
		);
	}

	/**
	 * Check if there are any existing forms in the reports.
	 *
	 * @since 1.9.8
	 *
	 * @return bool True if there are existing forms, false otherwise.
	 */
	private function reports_have_exising_forms(): bool {

		// Check if there are any corrupted submissions.
		$reports = get_option( self::REPORTS_OPTION, [] );

		if ( empty( $reports ) || ! is_array( $reports ) ) {
			return false;
		}

		foreach ( $reports as $form_id => $form_reports ) {
			$form = wpforms()->obj( 'form' )->get( $form_id );

			if ( $form instanceof WP_Post ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register an Action Scheduler task to send email notification about corrupted submissions.
	 *
	 * @since 1.9.8
	 *
	 * @param Task[] $tasks List of task classes.
	 *
	 * @return array
	 */
	public static function register_task( $tasks ): array {

		$tasks = (array) $tasks;

		$tasks[] = CorruptedSubmissionTask::class;

		return $tasks;
	}
}
