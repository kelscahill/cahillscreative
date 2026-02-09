<?php

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Tasks\Task;
use WPForms\Tasks\Tasks;
use WPForms_Entry_Fields_Handler;
use WPForms_Entry_Handler;

/**
 * Class Migration199Task.
 * Migrate dynamic field values from entry fields to entry_fields table.
 *
 * @since 1.9.9
 */
class Migration199Task extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.9.9
	 */
	public const ACTION = 'wpforms_process_migration_199';

	/**
	 * Status option name.
	 *
	 * @since 1.9.9
	 */
	public const STATUS = 'wpforms_process_migration_199_status';

	/**
	 * Start status.
	 *
	 * @since 1.9.9
	 */
	public const START = 'start';

	/**
	 * In progress status.
	 *
	 * @since 1.9.9
	 */
	public const IN_PROGRESS = 'in progress';

	/**
	 * Completed status.
	 *
	 * @since 1.9.9
	 */
	public const COMPLETED = 'completed';

	/**
	 * Chunk size to use.
	 * Specifies how many entries to process in one db request.
	 *
	 * @since 1.9.9
	 */
	public const CHUNK_SIZE = 1000;

	/**
	 * Chunk size of the migration task.
	 * Specifies how many entry field ids to load at once for further processing.
	 *
	 * @since 1.9.9
	 */
	public const TASK_CHUNK_SIZE = self::CHUNK_SIZE * 10;

	/**
	 * Date from which should get entries to migrate.
	 *
	 * @since 1.9.9
	 */
	public const START_DATE = '2025-11-04';

	/**
	 * Entry handler.
	 *
	 * @since 1.9.9
	 *
	 * @var WPForms_Entry_Handler
	 */
	private $entry_handler;

	/**
	 * Entry fields handler.
	 *
	 * @since 1.9.9
	 *
	 * @var WPForms_Entry_Fields_Handler
	 */
	private $entry_fields_handler;

	/**
	 * Temporary table name.
	 *
	 * @since 1.9.9
	 *
	 * @var string
	 */
	private $temp_table_name;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.9
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 1.9.9
	 */
	public function init(): void {

		global $wpdb;

		$this->entry_handler        = wpforms()->obj( 'entry' );
		$this->entry_fields_handler = wpforms()->obj( 'entry_fields' );
		$this->temp_table_name      = "{$wpdb->prefix}wpforms_temp_entries";

		if ( ! $this->entry_handler || ! $this->entry_fields_handler ) {
			return;
		}

		// Bail out if migration is not started or completed.
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
	 * Add hooks.
	 *
	 * @since 1.9.9
	 */
	private function hooks(): void {

		// Register the migrate action.
		add_action( self::ACTION, [ $this, 'migrate' ] );

		// Register after process queue action.
		add_action( 'action_scheduler_after_process_queue', [ $this, 'after_process_queue' ] );
	}

	/**
	 * Migrate entry fields with dynamic values.
	 *
	 * @param int $action_index Action index.
	 *
	 * @since 1.9.9
	 */
	public function migrate( $action_index ): void {

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Using OFFSET makes a way longer request, as MySQL has to access all rows before OFFSET.
		// We follow very fast way with indexed column (id > $action_index).
		$entry_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, entry_id, form_id
				FROM $this->temp_table_name
				WHERE id > %d
				LIMIT %d",
				$action_index,
				self::TASK_CHUNK_SIZE
			)
		);

		if ( empty( $entry_records ) ) {
			return;
		}

		$entry_ids = array_column( $entry_records, 'entry_id' );

		// Get all entries data at once.
		$entries_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT entry_id, form_id, fields
				FROM {$this->entry_handler->table_name}
				WHERE entry_id IN (" . implode( ',', $entry_ids ) . ')' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			),
			OBJECT_K
		);

		// Process entries in chunks.
		$i             = 0;
		$records_count = count( $entry_records );

		while ( $i < $records_count ) {
			$records_chunk = array_slice( $entry_records, $i, self::CHUNK_SIZE );

			$this->process_entries_dynamic_fields( $records_chunk, $entries_data );

			$i += self::CHUNK_SIZE;
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * After process queue action.
	 * Set status as completed.
	 *
	 * @since 1.9.9
	 */
	public function after_process_queue(): void {

		$tasks = wpforms()->obj( 'tasks' );

		if ( ! $tasks || $tasks->is_scheduled( self::ACTION ) ) {
			return;
		}

		$this->drop_temp_table();

		// Mark that migration is finished.
		update_option( self::STATUS, self::COMPLETED );
	}

	/**
	 * Init migration.
	 *
	 * @since 1.9.9
	 *
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	private function init_migration(): void {

		// Get all entries that need processing.
		$count = $this->get_entries_for_migration();

		if ( ! $count ) {
			$this->drop_temp_table();

			return;
		}

		$index = 0;

		while ( $index < $count ) {
			// We do not use Task class here as we do not need meta. So, we reduce the number of DB requests.
			as_enqueue_async_action(
				self::ACTION,
				[ $index ],
				Tasks::GROUP
			);

			$index += self::TASK_CHUNK_SIZE;
		}
	}

	/**
	 * Process entries and update dynamic fields in entry_fields table.
	 *
	 * @param array $records      Array of entry records.
	 * @param array $entries_data Array of entry data indexed by entry_id.
	 *
	 * @since 1.9.9
	 */
	private function process_entries_dynamic_fields( $records, $entries_data ): void {

		global $wpdb;

		if ( empty( $records ) || empty( $entries_data ) ) {
			return;
		}

		foreach ( $records as $record ) {
			// Check if we have entry data for this record.
			if ( ! isset( $entries_data[ $record->entry_id ] ) ) {
				continue;
			}

			$entry        = $entries_data[ $record->entry_id ];
			$fields_array = json_decode( $entry->fields, true );

			// Skip if fields is not a valid JSON array.
			if ( ! is_array( $fields_array ) ) {
				continue;
			}

			// Process each field in the entry.
			foreach ( $fields_array as $field ) {
				// Skip if the field is not dynamic.
				if ( ! isset( $field['id'], $field['dynamic'] ) ) {
					continue;
				}

				$field_id      = $field['id'];
				$dynamic_value = $field['value'];

				// Update the entry field with the dynamic value.
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->update(
					$this->entry_fields_handler->table_name,
					[ 'value' => $dynamic_value ],
					[
						'entry_id' => $record->entry_id,
						'form_id'  => $entry->form_id,
						'field_id' => $field_id,
					],
					[ '%s' ],
					[ '%d', '%d', '%d' ]
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
	}

	/**
	 * Get entries that need migration.
	 * Store them in a temporary table.
	 *
	 * @since 1.9.9
	 *
	 * @return int
	 */
	private function get_entries_for_migration(): int {

		global $wpdb;

		$this->drop_temp_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"CREATE TABLE $this->temp_table_name
				(
				    id       BIGINT AUTO_INCREMENT PRIMARY KEY,
				    entry_id BIGINT NOT NULL,
				    form_id  BIGINT NOT NULL,
				    INDEX idx_entry_id (entry_id)
				)"
		);

		// Get entries from the wp_wpforms_entries table after START_DATE.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $this->temp_table_name (entry_id, form_id)
				SELECT entry_id, form_id
				FROM {$this->entry_handler->table_name}
				WHERE date >= %s",
				self::START_DATE
			)
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $wpdb->rows_affected;
	}

	/**
	 * Drop a temporary table.
	 *
	 * @since 1.9.9
	 */
	private function drop_temp_table(): void {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS $this->temp_table_name" );
	}
}
