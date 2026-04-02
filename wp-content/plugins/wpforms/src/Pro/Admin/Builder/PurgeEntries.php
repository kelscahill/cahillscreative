<?php

namespace WPForms\Pro\Admin\Builder;

use WPForms\Pro\Tasks\Actions\PurgeEntriesTask;

/**
 * Manage automatic entry purge scheduling for forms.
 *
 * @since 1.10.0
 */
class PurgeEntries {

	/**
	 * Perform certain things on class init.
	 *
	 * @since 1.10.0
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.10.0
	 */
	private function hooks(): void {

		add_action( 'wpforms_builder_save_form', [ $this, 'manage_scheduled_task' ], 10, 2 );
		add_filter( 'wpforms_tasks_get_tasks', [ $this, 'add_task' ] );
	}

	/**
	 * Add PurgeEntriesTask to the list of available tasks.
	 *
	 * @since 1.10.0
	 *
	 * @param array $tasks List of available tasks.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function add_task( $tasks ): array {

		$tasks = (array) $tasks;

		$tasks[] = PurgeEntriesTask::class;

		return $tasks;
	}

	/**
	 * Manage scheduled task when a form is saved.
	 *
	 * @since 1.10.0
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data    Form data.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function manage_scheduled_task( $form_id, $data ): void {

		$form_id = absint( $form_id );

		if ( empty( $form_id ) ) {
			return;
		}

		$data = (array) $data;

		$purge_enable = ! empty( $data['settings']['purge_entries_enable'] );

		// If purge is enabled, ensure the global task exists.
		if ( $purge_enable && ! $this->is_task_scheduled() ) {
			$this->schedule_task();
		}
	}

	/**
	 * Check if the global task is scheduled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool True if the task is scheduled, false otherwise.
	 */
	private function is_task_scheduled(): bool {

		return (bool) wpforms()->obj( 'tasks' )->is_scheduled( PurgeEntriesTask::ACTION );
	}

	/**
	 * Schedule the global purge task.
	 *
	 * @since 1.10.0
	 */
	private function schedule_task(): void {

		// Calculate midnight in the site's timezone tomorrow.
		$tomorrow = date_create( 'tomorrow midnight', wp_timezone() );

		// Convert to the UTC timestamp for Action Scheduler.
		$timestamp_utc = $tomorrow->getTimestamp();

		// Schedule a new task to run daily at midnight (site timezone).
		wpforms()->obj( 'tasks' )->create( PurgeEntriesTask::ACTION )
			->recurring( $timestamp_utc, DAY_IN_SECONDS )
			->register();
	}
}
