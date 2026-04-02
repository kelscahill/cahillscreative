<?php

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Pro\Admin\DashboardWidget;
use WPForms\Pro\Forms\Fields\Camera\Field as CameraField;
use WPForms\Pro\Forms\Fields\FileUpload\Field as FileUploadField;
use WPForms\Pro\Forms\Fields\Richtext\Field as RichtextField;
use WPForms\Tasks\Task;

/**
 * Class PurgeEntriesTask is responsible for automatically deleting old form entries.
 *
 * @since 1.10.0
 */
class PurgeEntriesTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.10.0
	 */
	public const ACTION = 'wpforms_purge_old_entries';

	/**
	 * Default number of days to retain entries before purging.
	 *
	 * @since 1.10.0
	 */
	private const DEFAULT_RETENTION_DAYS = 365;

	/**
	 * Class constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );

		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.10.0
	 */
	private function hooks(): void {

		add_action( self::ACTION, [ $this, 'process' ] );
	}

	/**
	 * Process the purge task for all forms with purge enabled.
	 *
	 * @since 1.10.0
	 */
	public function process(): void {

		// Get all forms.
		$forms = wpforms()->obj( 'form' )->get(
			'',
			[
				'numberposts'            => - 1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( empty( $forms ) ) {
			return;
		}

		// Process each form.
		foreach ( $forms as $form ) {
			$form_data = wpforms_decode( $form->post_content );

			$this->process_form( $form->ID, $form_data );
		}
	}

	/**
	 * Process the purge task for a specific form.
	 *
	 * @since 1.10.0
	 *
	 * @param int   $form_id   Form ID.
	 * @param array $form_data Form data.
	 */
	private function process_form( int $form_id, array $form_data ): void {

		// Check if purge is enabled.
		if ( empty( $form_data['settings']['purge_entries_enable'] ) ) {
			return;
		}

		// Get a number of days to retain.
		$days = ! empty( $form_data['settings']['purge_entries_days'] ) ? absint( $form_data['settings']['purge_entries_days'] ) : self::DEFAULT_RETENTION_DAYS;

		if ( empty( $days ) ) {
			return;
		}

		// Calculate the date threshold.
		$date_threshold = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// Get old entries.
		$entries = $this->get_old_entries( $form_id, $date_threshold );

		if ( empty( $entries ) ) {
			return;
		}

		// Delete entries.
		$this->delete_entries( $entries, $form_id );
	}

	/**
	 * Get old entries.
	 *
	 * @since 1.10.0
	 *
	 * @param int    $form_id        Form ID.
	 * @param string $date_threshold Date threshold.
	 *
	 * @return array Entries to delete.
	 */
	private function get_old_entries( int $form_id, string $date_threshold ): array {

		global $wpdb;

		$table_name = wpforms()->obj( 'entry' )->table_name;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT entry_id FROM {$table_name} WHERE form_id = %d AND date < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$form_id,
				$date_threshold
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete uploaded files for entries.
	 *
	 * @since 1.10.0
	 *
	 * @param array $entry_ids Entry IDs.
	 */
	private function delete_uploaded_files( array $entry_ids ): void { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		// Allow force deleting uploaded files.
		add_filter( 'wpforms_pro_forms_fields_file_upload_field_delete_uploaded_file_force', '__return_true' );
		add_filter( 'wpforms_pro_forms_fields_camera_field_delete_uploaded_file_force', '__return_true' );

		// Delete uploaded files (file upload, camera, and rich text fields).
		array_map( [ FileUploadField::class, 'delete_uploaded_files_from_entry' ], $entry_ids );
		array_map( [ CameraField::class, 'delete_uploaded_files_from_entry' ], $entry_ids );
		array_map( [ RichtextField::class, 'delete_uploaded_files_from_entry' ], $entry_ids );

		// Remove force deleting uploaded files filter.
		remove_filter( 'wpforms_pro_forms_fields_file_upload_field_delete_uploaded_file_force', '__return_true' );
		remove_filter( 'wpforms_pro_forms_fields_camera_field_delete_uploaded_file_force', '__return_true' );
	}

	/**
	 * Delete entries.
	 *
	 * @since 1.10.0
	 *
	 * @param array $entry_ids Entry IDs to delete.
	 * @param int   $form_id   Form ID.
	 */
	private function delete_entries( array $entry_ids, int $form_id ): void {

		global $wpdb;

		// Delete uploaded files before removing the entry itself.
		$this->delete_uploaded_files( $entry_ids );

		/**
		 * Fires before entries are purged from the database.
		 *
		 * Allows addons to clean up related data (e.g., PDF files, attachments).
		 *
		 * @since 1.10.0
		 *
		 * @param array $entry_ids Entry IDs being deleted.
		 * @param int   $form_id   Form ID.
		 */
		do_action( 'wpforms_pro_tasks_actions_purge_entries_task_delete_entries', $entry_ids, $form_id );

		// Delete entry meta and fields.
		wpforms()->obj( 'entry_meta' )->delete_where_in( 'entry_id', $entry_ids );
		wpforms()->obj( 'entry_fields' )->delete_where_in( 'entry_id', $entry_ids );

		$where_ids  = wpforms_wpdb_prepare_in( $entry_ids, '%d' );
		$table_name = wpforms()->obj( 'entry' )->table_name;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE entry_id IN ( {$where_ids} ) AND form_id = %d",
				$form_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Log the deletion.
		if ( $deleted > 0 ) {

			// Clear the dashboard widget cache to reflect updated counts.
			DashboardWidget::clear_widget_cache();

			wpforms_log(
				'Auto Purge - Entries Deleted',
				[
					'entry_count' => $deleted,
					'entry_ids'   => $entry_ids,
				],
				[
					'type'    => 'log',
					'form_id' => $form_id,
				]
			);
		}
	}
}
