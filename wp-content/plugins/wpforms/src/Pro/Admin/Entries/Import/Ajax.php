<?php

namespace WPForms\Pro\Admin\Entries\Import;

use Exception;
use WP_Post;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractFileSource;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractSource;
use WPForms\Pro\Admin\Entries\Import\Source\Database\Plugins;
use WPForms\Pro\Tasks\Actions\ImportCleanupTask;

/**
 * Handles AJAX operations for importing entries.
 *
 * @since 1.10.1
 */
class Ajax {

	/**
	 * Number of entries to import per request.
	 *
	 * @since 1.10.1
	 */
	private const CHUNK_SIZE = 25;

	/**
	 * Nonce action name.
	 *
	 * @since 1.10.1
	 */
	private const NONCE_ACTION = 'wpforms-entry-import';

	/**
	 * Destination form ID.
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	private $destination_form_id;

	/**
	 * Failed entries CSV writer for the current chunk request.
	 *
	 * @since 1.10.1
	 *
	 * @var FailedEntriesCsv
	 */
	private $failed_entries_csv;

	/**
	 * Initializes the FailedEntriesCsv instance.
	 *
	 * @since 1.10.1
	 */
	public function __construct() {

		$this->failed_entries_csv = new FailedEntriesCsv();
	}

	/**
	 * Initialize hooks if conditions are met for the current user.
	 *
	 * @since 1.10.1
	 */
	public function init(): void {

		if ( ! wpforms_is_admin_ajax() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @since 1.10.1
	 */
	private function hooks(): void {

		add_action( 'wp_ajax_wpforms_entries_import_get_plugin_forms', [ $this, 'ajax_get_plugin_forms' ] );
		add_action( 'wp_ajax_wpforms_import_load_plugin_import', [ $this, 'ajax_load_plugin_import' ] );

		add_action( 'wp_ajax_wpforms_import_load_file_import', [ $this, 'ajax_load_file_import' ] );
		add_action( 'wp_ajax_wpforms_import_chunk', [ $this, 'ajax_import_chunk' ] );
		add_action( 'wp_ajax_wpforms_import_cancel', [ $this, 'ajax_import_cancel' ] );
	}

	/**
	 * AJAX handler for getting forms from a plugin.
	 *
	 * @since 1.10.1
	 */
	public function ajax_get_plugin_forms(): void {

		$this->validate_permission();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$plugin = isset( $_POST['plugin'] ) ? sanitize_key( $_POST['plugin'] ) : '';

		if ( empty( $plugin ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No plugin specified.', 'wpforms' ) ] );
		}

		$source = Plugins::get_by_slug( $plugin );

		if ( ! $source ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid plugin.', 'wpforms' ) ] );
		}

		wp_send_json_success( [ 'forms' => $source->get_forms() ] );
	}

	/**
	 * AJAX handler for validating plugin import.
	 *
	 * Returns source fields, destination fields, and entry count for mapping.
	 *
	 * @since 1.10.1
	 */
	public function ajax_load_plugin_import(): void {

		$this->validate_permission();
		$this->set_form_id();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$plugin         = isset( $_POST['plugin'] ) ? sanitize_key( $_POST['plugin'] ) : '';
		$source_form_id = isset( $_POST['source_form_id'] ) ? absint( $_POST['source_form_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $plugin ) || empty( $source_form_id ) ) {
			wp_send_json_error( esc_html__( 'Missing required parameters.', 'wpforms' ) );
		}

		if ( $plugin === 'wpforms' && $source_form_id === $this->destination_form_id ) {
			wp_send_json_error( esc_html__( 'Source and destination forms cannot be the same.', 'wpforms' ) );
		}

		try {
			$source = EntryImporter::get_source( $plugin, [ $source_form_id ] );

			$this->send_import_init_response( $source, $plugin );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Handle the validate file request.
	 *
	 * @since 1.10.1
	 */
	public function ajax_load_file_import(): void {

		$this->validate_permission();
		$this->set_form_id();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$file_path = isset( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['file']['tmp_name'] ) ) : '';
		$extension = isset( $_FILES['file']['name'] ) ? pathinfo( sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ), PATHINFO_EXTENSION ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$real_path = realpath( $file_path );

		if ( $real_path === false ) {
			wp_send_json_error( esc_html__( 'No file found.', 'wpforms' ) );
		}

		try {
			$source = EntryImporter::get_source( $extension, [ $real_path ] );

			$this->send_import_init_response( $source, $extension );
		} catch ( Exception $e ) {
			wpforms_log( 'Entry import init failed', $e->getMessage(), [ 'type' => [ 'entry', 'error' ] ] );
			wp_send_json_error(
				[
					'message'    => $e->getMessage(),
					'error_type' => 'file_validation',
				]
			);
		}
	}

	/**
	 * Handle a chunk request: process 25 entries, advance session cursor.
	 *
	 * Expected POST params: request_id, nonce.
	 *
	 * @since 1.10.1
	 */
	public function ajax_import_chunk(): void {

		$this->validate_permission();

		$session = $this->get_import_session();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['map'] ) || ! is_array( $_POST['map'] ) ) {
			wp_send_json_error( esc_html__( 'No mapped fields.', 'wpforms' ) );
		}

		$map = [];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $_POST['map'] as $destination_field_id => $source_field_id ) {
			$map[ wpforms_sanitize_key( $destination_field_id ) ] = wpforms_sanitize_key( $source_field_id );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( $session->get_data( 'status' ) === 'failed' ) {
			wp_send_json_error( esc_html__( 'Import session has already failed.', 'wpforms' ) );
		}

		try {
			$source_identifier = $session->get_data( 'source_identifier' ) ?? $session->get_data( 'source_type' );
			$source_args       = $session->get_data( 'source_args', [] );

			$source     = EntryImporter::get_source( $source_identifier, $source_args );
			$chunk_size = $this->get_chunk_size();

			$result    = $source->process_chunk( $session->get_data( 'cursor', 0 ), $chunk_size );
			$importing = count( $result['entries'] );
			$errors    = $result['errors'];

			if ( $importing === 0 ) {
				$session->advance(
					$result['next_cursor'],
					0,
					$errors
				);

				$this->maybe_complete_import( $session );

				$session->fail();

				wp_send_json_error(
					[
						'message' => esc_html__( 'Import stopped. No entries for import.', 'wpforms' ),
						'errors'  => $errors,
					]
				);
			}

			$form_handler = wpforms()->obj( 'form' );
			$form         = $form_handler ? $form_handler->get( $this->destination_form_id ) : null;

			// Catch the case where the form was trashed or deleted after the mapping step.
			if ( ! $form instanceof WP_Post || $form->post_status !== 'publish' ) {
				$session->fail();

				wp_send_json_error(
					[
						'message' => esc_html__( 'Import stopped. The selected form is no longer available.', 'wpforms' ),
						'errors'  => $errors,
					]
				);
			}

			$form_data = wpforms_decode( $form->post_content );

			if ( empty( $form_data ) ) {
				$session->fail();

				wp_send_json_error(
					[
						'message' => esc_html__( "Import stopped. WPForms form wasn't found.", 'wpforms' ),
						'errors'  => $errors,
					]
				);
			}

			$importer = new EntryImporter( $this->destination_form_id, $source_identifier, $this->failed_entries_csv );
			$imported = $importer->import_entries( $result, $form_data, $map );
			$errors   = $result['errors'];

			$this->failed_entries_csv->save( $session );

			$session->advance(
				$result['next_cursor'],
				$imported,
				$errors
			);

			$this->maybe_complete_import( $session );

			wp_send_json_success(
				[
					'total'       => $session->get_data( 'total' ),
					'imported'    => $session->get_data( 'imported' ),
					'is_complete' => false,
				]
			);
		} catch ( Exception $e ) {
			wpforms_log( 'Entry import chunk failed', $e->getMessage(), [ 'type' => [ 'entry', 'error' ] ] );
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Handle a cancel request: delete the session transient.
	 *
	 * Expected POST params: request_id, nonce.
	 *
	 * @since 1.10.1
	 */
	public function ajax_import_cancel(): void {

		$this->validate_permission();

		$session = $this->get_import_session();

		$session->delete();

		wp_send_json_success();
	}

	/**
	 * Validate the form ID and user permissions.
	 *
	 * @since 1.10.1
	 */
	private function set_form_id(): void {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['form_id'] ) ) {
			wp_send_json_error( esc_html__( 'Form ID is required.', 'wpforms' ) );
		}

		$this->destination_form_id = absint( $_POST['form_id'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$this->validate_destination_form_permission();
		$this->validate_destination_form_exists();
	}

	/**
	 * Ensure the selected destination form still exists.
	 *
	 * Catches the case where the form was deleted in another tab between
	 * the user picking it and clicking Continue.
	 *
	 * @since 1.10.1
	 */
	private function validate_destination_form_exists(): void {

		$form_handler = wpforms()->obj( 'form' );
		$form         = $form_handler ? $form_handler->get( $this->destination_form_id ) : null;

		if ( ! $form instanceof WP_Post || $form->post_status !== 'publish' ) {
			wp_send_json_error( esc_html__( 'Import stopped. The selected form is no longer available.', 'wpforms' ) );
		}
	}

	/**
	 * Validate user permissions for the destination form during entry import.
	 *
	 * @since 1.10.1
	 */
	private function validate_destination_form_permission(): void {

		if ( wpforms_current_user_can( 'edit_entries_form_single', $this->destination_form_id ) === false ) {
			wp_send_json_error( esc_html__( 'You do not have permission to import entries for current form.', 'wpforms' ) );
		}
	}

	/**
	 * Validate user has permission to import entries for the current form.
	 *
	 * @since 1.10.1
	 */
	private function validate_permission(): void {

		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Security check failed.', 'wpforms' ) );
		}

		$permission_message = __( 'You do not have permission to import entries.', 'wpforms' );

		if ( ! wpforms_current_user_can( [ 'edit_forms', 'view_entries' ] ) ) {
			wp_send_json_error( esc_html( $permission_message ) );
		}
	}

	/**
	 * Retrieve and validate the import session with a permission check.
	 *
	 * @since 1.10.1
	 *
	 * @return ImportSession
	 */
	private function get_import_session(): ImportSession {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['request_id'] ) ) {
			wp_send_json_error( esc_html__( 'Request ID is required.', 'wpforms' ) );
		}

		$request_id = sanitize_text_field( wp_unslash( $_POST['request_id'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$session = new ImportSession( $request_id );

		if ( ! $session->load() ) {
			wp_send_json_error( esc_html__( 'Import session not found or expired.', 'wpforms' ) );
		}

		$this->destination_form_id = $session->get_data( 'form_id' );

		$this->validate_destination_form_permission();

		return $session;
	}

	/**
	 * Create an import session and send an initialization response.
	 *
	 * @since 1.10.1
	 *
	 * @param AbstractSource $source            The import source.
	 * @param string         $source_identifier Source identifier (plugin slug or file extension).
	 */
	private function send_import_init_response( AbstractSource $source, string $source_identifier ): void {

		// Stop early when the source has no entries — there is nothing to map.
		if ( $source->get_total() < 1 ) {
			wp_send_json_error( esc_html__( 'Import stopped. No entries for import.', 'wpforms' ) );
		}

		$session            = ImportSession::create( $source, $this->destination_form_id, $source_identifier );
		$importer           = new EntryImporter( $this->destination_form_id, $source_identifier, $this->failed_entries_csv );
		$tasks              = wpforms()->obj( 'tasks' );
		$destination_fields = $importer->get_destination_fields();

		if ( $source instanceof AbstractFileSource ) {
			$destination_fields[] = [
				'key'     => 'entry_date',
				'label'   => esc_html__( 'Entry Date', 'wpforms' ),
				'type'    => 'meta',
				'default' => [
					'value' => 'current',
					'label' => esc_html__( 'Date/Time of Import', 'wpforms' ),
				],
			];
		}

		if ( $tasks && ! $tasks->is_scheduled( ImportCleanupTask::ACTION ) ) {
			$tasks
				->create( ImportCleanupTask::ACTION )
				->recurring( time() + ImportCleanupTask::INTERVAL, ImportCleanupTask::INTERVAL )
				->params()
				->register();
		}

		wp_send_json_success(
			[
				'request_id'                => $session->get_request_id(),
				'source_fields'             => $source->get_fields(),
				'destination_fields'        => $destination_fields,
				'total'                     => $source->get_total(),
				'unsupported_fields_notice' => $importer->get_unsupported_fields_notice( $source ),
			]
		);
	}

	/**
	 * Check if the import session is complete and send a success response.
	 *
	 * @since 1.10.1
	 *
	 * @param ImportSession $session Instance of the import session being checked.
	 */
	private function maybe_complete_import( ImportSession $session ): void {

		if ( ! $session->is_complete() ) {
			return;
		}

		$errors = $this->sort_errors( (array) $session->get_data( 'errors' ) );

		wp_send_json_success(
			[
				'total'                           => $session->get_data( 'total' ),
				'imported'                        => $session->get_data( 'imported' ),
				'skipped'                         => $session->get_data( 'total' ) - $session->get_data( 'imported' ),
				'with_issue'                      => $this->count_entries_with_issue( $errors ),
				'download_failed_entries_csv_url' => $this->failed_entries_csv->get_download_url( $session ),
				'errors'                          => $errors,
				'is_complete'                     => true,
			]
		);
	}

	/**
	 * Count unique entries that have at least one error.
	 *
	 * A single entry can produce multiple errors (e.g., several invalid fields),
	 * so we deduplicate by entry ID.
	 *
	 * @since 1.10.1
	 *
	 * @param array $errors List of error items.
	 *
	 * @return int
	 */
	private function count_entries_with_issue( array $errors ): int {

		return count( array_unique( array_column( $errors, 'entry_id' ) ) );
	}

	/**
	 * Sort import errors so destination errors come first, preserving original order within each group.
	 *
	 * @since 1.10.1
	 *
	 * @param array $errors List of error items, each with a `type` of `destination` or `source`.
	 *
	 * @return array
	 */
	private function sort_errors( array $errors ): array {

		if ( empty( $errors ) ) {
			return $errors;
		}

		$destination = [];
		$source      = [];

		foreach ( $errors as $error ) {
			if ( ( $error['type'] ?? '' ) === 'destination' ) {
				$destination[] = $error;

				continue;
			}

			$source[] = $error;
		}

		return array_merge( $destination, $source );
	}

	/**
	 * Retrieve the chunk size for processing data.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	private function get_chunk_size(): int {

		/**
		 * Allow changing the chunk size for entry import.
		 *
		 * @since 1.10.1
		 *
		 * @param int $chunk_size The number of entries to process in each chunk. Default: 25.
		 */
		return (int) apply_filters( 'wpforms_pro_admin_entries_import_ajax_get_chunk_size', self::CHUNK_SIZE );
	}
}
