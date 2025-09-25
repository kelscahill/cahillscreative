<?php

namespace WPForms\Emails\Tasks;

use WP_Post;
use WPForms\Emails\Mailer;
use WPForms\Emails\Templates\CorruptedDataReport;
use WPForms\Emails\Templates\General;
use WPForms\Forms\CorruptedSubmission;
use WPForms\Tasks\Task;

/**
 * Task to handle email notification about corrupted submission.
 *
 * @since 1.9.8
 */
class CorruptedSubmissionTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.9.8
	 */
	public const ACTION = 'wpforms_corrupted_submission_email';

	/**
	 * Class constructor.
	 *
	 * @since 1.9.8
	 */
	public function __construct() {

		parent::__construct( self::ACTION );

		$this->init();
	}

	/**
	 * Initialize the task.
	 *
	 * @since 1.9.8
	 */
	private function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.9.8
	 */
	private function hooks(): void {

		// Register the action handler.
		add_action( self::ACTION, [ $this, 'process' ] );
	}

	/**
	 * Process the task - send corrupted email notification.
	 *
	 * @since 1.9.8
	 */
	public function process(): void {

		$reports_data = $this->get_reports_data();

		if ( empty( $reports_data ) ) {
			// Clean up reports data.
			// Make sure to delete the option only if no valid reports are found.
			delete_option( CorruptedSubmission::REPORTS_OPTION );

			return;
		}

		$admin_email = $this->get_admin_email();

		if ( empty( $admin_email ) ) {
			return;
		}

		$sent = $this->send_email( $reports_data, $admin_email );

		$this->log_result( $sent, count( $reports_data ) );
		$this->clean_up();
	}

	/**
	 * Get and validate reports data.
	 *
	 * @since 1.9.8
	 *
	 * @return array Transformed reports data, empty array if no valid reports.
	 */
	private function get_reports_data(): array {

		$reports = get_option( CorruptedSubmission::REPORTS_OPTION, [] );

		if ( empty( $reports ) || ! is_array( $reports ) ) {
			return [];
		}

		return $this->transform_reports_data( $reports );
	}

	/**
	 * Get and validate the admin email address.
	 *
	 * @since 1.9.8
	 *
	 * @return string Admin email address, empty string if invalid.
	 */
	private function get_admin_email(): string {

		$admin_email = get_option( 'admin_email' );

		return is_email( $admin_email ) ? $admin_email : '';
	}

	/**
	 * Send corrupted email notification.
	 *
	 * @since 1.9.8
	 *
	 * @param array  $reports_data Transformed reports data.
	 * @param string $admin_email  Admin email address.
	 *
	 * @return bool True if the email was sent successfully, false otherwise.
	 */
	private function send_email( array $reports_data, string $admin_email ): bool {

		$subject  = $this->get_email_subject();
		$template = $this->prepare_email_template( $reports_data );

		return ( new Mailer() )
			->template( $template )
			->subject( $subject )
			->to_email( $admin_email )
			->send();
	}

	/**
	 * Get an email subject with site name.
	 *
	 * @since 1.9.8
	 *
	 * @return string Email subject.
	 */
	private function get_email_subject(): string {

		return sprintf( /* translators: %1$s: Site name. */
			__( 'Technical Alert: WPForms "Corrupted Post Data" Detected on [%1$s]', 'wpforms-lite' ),
			get_bloginfo( 'name' )
		);
	}

	/**
	 * Prepare an email template with reports data.
	 *
	 * @since 1.9.8
	 *
	 * @param array $reports_data Transformed reports data.
	 *
	 * @return General Email template instance.
	 */
	private function prepare_email_template( array $reports_data ): General {

		return ( new CorruptedDataReport() )->set_args(
			[
				'body' => [
					'reports'      => $reports_data,
					'doc_page_url' => 'https://wpforms.com/docs/resolving-the-attempt-to-submit-corrupted-post-data-error-in-wpforms
?utm_campaign=plugin-email-alerts&utm_source=wpforms&utm_medium=email&utm_content=Learn%20More%20-%20Corrupted%20Post%20Data',
				],
			]
		);
	}

	/**
	 * Log the result of an email sending attempt.
	 *
	 * @since 1.9.8
	 *
	 * @param bool $sent          Whether email was sent successfully.
	 * @param int  $reports_count Number of reports processed.
	 */
	private function log_result( bool $sent, int $reports_count ): void {

		$title   = $sent ? 'Corrupted submission email sent successfully' : 'Failed to send corrupted submission email';
		$message = [ 'reports_count' => $reports_count ];
		$type    = [ 'security', $sent ? 'log' : 'error' ];

		wpforms_log(
			$title,
			$message,
			[
				'type' => $type,
			]
		);
	}

	/**
	 * Transform reports data structure to the new format.
	 *
	 * @since 1.9.8
	 *
	 * @param array $reports Raw reports data in old format.
	 *
	 * @return array Transformed reports data.
	 */
	private function transform_reports_data( array $reports ): array {

		$transformed = [];

		foreach ( $reports as $form_id => $form_reports ) {
			if ( empty( $form_reports ) || ! is_array( $form_reports ) ) {
				continue;
			}

			// Get the form title.
			// If the form does not exist, skip it.
			$form_title = $this->get_form_title( $form_id );

			if ( $form_title === false ) {
				continue;
			}

			// Collect and count page URLs.
			$page_url_counts = [];

			foreach ( $form_reports as $report ) {
				if ( ! empty( $report['page_url'] ) ) {
					$url                     = $report['page_url'];
					$page_url_counts[ $url ] = ( $page_url_counts[ $url ] ?? 0 ) + 1;
				}
			}

			// Sort by count (descending) to get the most frequent URLs first.
			arsort( $page_url_counts );

			// Keep only the top 3 URLs.
			$has_more_urls = count( $page_url_counts ) > 3;
			$page_urls     = array_slice( $page_url_counts, 0, 3, true );

			$transformed[ $form_id ] = [
				'count'         => count( $form_reports ),
				'page_urls'     => $page_urls,
				'has_more_urls' => $has_more_urls,
				'form_title'    => $form_title,
			];
		}

		return $transformed;
	}

	/**
	 * Generate a formatted title for a form based on its ID.
	 *
	 * @since 1.9.8
	 *
	 * @param int|string $form_id The ID of the form for which the title is being generated.
	 *
	 * @return string|bool The formatted form title. False if the form does not exist.
	 */
	private function get_form_title( $form_id ) {

		$form = wpforms()->obj( 'form' )->get( $form_id );

		if ( ! $form instanceof WP_Post ) {
			return false;
		}

		if ( ! empty( $form->post_title ) ) {
			return $form->post_title;
		}

		return sprintf(
			'%s #%d',
			__( 'Form', 'wpforms-lite' ),
			absint( $form_id )
		);
	}

	/**
	 * Clean up the reports and dismissed notices options after processing.
	 *
	 * @since 1.9.8
	 */
	private function clean_up(): void {

		// Clean up the reports option.
		delete_option( CorruptedSubmission::REPORTS_OPTION );

		$dismissed_notices = (array) get_option( 'wpforms_admin_notices', [] );

		// Clean up the dismissed notices option.
		$notice_id = str_replace( 'wpforms_', '', CorruptedSubmission::REPORTS_OPTION );

		if ( isset( $dismissed_notices[ $notice_id ] ) ) {
			unset( $dismissed_notices[ $notice_id ] );

			update_option( 'wpforms_admin_notices', $dismissed_notices );
		}
	}
}
