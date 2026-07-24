<?php

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Tasks\Task;
use WPForms\Tasks\Tasks;

/**
 * Class MigrationOrphanEntryMetaTask.
 *
 * One-time cleanup of orphaned wp_wpforms_entry_meta rows with `entry_id = 0`.
 * These rows were created before the fix in WPForms_Process::save_meta() that
 * suppresses meta writes when entry_save() did not create a parent entry
 * (e.g. when the form has the `disable_entries` setting enabled).
 *
 * @since 1.10.2
 */
class MigrationOrphanEntryMetaTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.10.2
	 */
	public const ACTION = 'wpforms_process_migration_orphan_entry_meta';

	/**
	 * Status option name.
	 *
	 * @since 1.10.2
	 */
	public const STATUS = 'wpforms_process_migration_orphan_entry_meta_status';

	/**
	 * Start status.
	 *
	 * @since 1.10.2
	 */
	public const START = 'start';

	/**
	 * In progress status.
	 *
	 * @since 1.10.2
	 */
	public const IN_PROGRESS = 'in progress';

	/**
	 * Completed status.
	 *
	 * @since 1.10.2
	 */
	public const COMPLETED = 'completed';

	/**
	 * Default maximum rows to delete per Action Scheduler tick.
	 *
	 * @since 1.10.2
	 */
	public const CHUNK_SIZE = 2000;

	/**
	 * Entry meta handler.
	 *
	 * @since 1.10.2
	 *
	 * @var WPForms_Entry_Meta_Handler|null
	 */
	private $entry_meta_handler;

	/**
	 * Class constructor.
	 *
	 * @since 1.10.2
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 1.10.2
	 */
	public function init(): void {

		$this->entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $this->entry_meta_handler ) {
			return;
		}

		// Bail out if migration is not started or is already completed.
		$status = get_option( self::STATUS );

		if ( ! $status || $status === self::COMPLETED ) {
			return;
		}

		$this->hooks();

		if ( $status === self::START ) {
			// Mark that migration is in progress.
			update_option( self::STATUS, self::IN_PROGRESS );

			// Init migration.
			$this->init_migration();
		}
	}

	/**
	 * Delete a chunk of orphan rows. Reschedules itself while rows remain;
	 * otherwise marks the migration as completed.
	 *
	 * @since 1.10.2
	 */
	public function migrate(): void {

		$chunk_size = $this->get_chunk_size();
		$deleted    = $this->delete_chunk( $chunk_size );

		// If we filled the chunk, more rows likely remain — keep going.
		// Otherwise the table is drained, so finish.
		if ( $deleted >= $chunk_size ) {
			as_enqueue_async_action( self::ACTION, [], Tasks::GROUP );

			return;
		}

		update_option( self::STATUS, self::COMPLETED );
	}

	/**
	 * Get the maximum number of orphan rows to delete per Action Scheduler tick.
	 *
	 * @since 1.10.2
	 *
	 * @return int
	 */
	private function get_chunk_size(): int {

		/**
		 * Filter the number of orphan entry meta rows deleted per migration tick.
		 *
		 * @since 1.10.2
		 *
		 * @param int $chunk_size Number of rows to delete per tick.
		 */
		$chunk_size = (int) apply_filters( 'wpforms_pro_tasks_actions_migration_orphan_entry_meta_task_chunk_size', self::CHUNK_SIZE );

		return $chunk_size > 0 ? $chunk_size : self::CHUNK_SIZE;
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.10.2
	 */
	private function hooks(): void {

		add_action( self::ACTION, [ $this, 'migrate' ] );
	}

	/**
	 * Init migration. Enqueue the first chunk only if orphans exist;
	 * otherwise mark the migration as completed immediately.
	 *
	 * @since 1.10.2
	 */
	private function init_migration(): void {

		if ( $this->count_orphans() === 0 ) {
			update_option( self::STATUS, self::COMPLETED );

			return;
		}

		as_enqueue_async_action( self::ACTION, [], Tasks::GROUP );
	}

	/**
	 * Count orphan rows (`entry_id = 0`) currently in the entry meta table.
	 *
	 * @since 1.10.2
	 *
	 * @return int
	 */
	private function count_orphans(): int {

		global $wpdb;

		$table_name = $this->entry_meta_handler->table_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE entry_id = 0" );
	}

	/**
	 * Delete up to $chunk_size orphan rows in a single query.
	 *
	 * @since 1.10.2
	 *
	 * @param int $chunk_size Maximum number of rows to delete.
	 *
	 * @return int Number of rows deleted.
	 */
	private function delete_chunk( int $chunk_size ): int {

		global $wpdb;

		$table_name = $this->entry_meta_handler->table_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $table_name WHERE entry_id = 0 LIMIT %d",
				$chunk_size
			)
		);

		return (int) $deleted;
	}
}
