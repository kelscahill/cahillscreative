<?php

namespace WPForms\Pro\Admin\Entries\Import;

use WPForms\Helpers\File;
use WPForms\Pro\Tasks\Actions\ImportCleanupTask;

/**
 * Handles creation and management of a CSV file for failed entries.
 *
 * Provides functionality to record failed entries, save session data, and
 * retrieve download URLs for the CSV file.
 *
 * @since 1.10.1
 */
class FailedEntriesCsv {

	/**
	 * List of records.
	 *
	 * @since 1.10.1
	 *
	 * @var array
	 */
	private $records = [];

	/**
	 * Append a failed source entry to the CSV file.
	 *
	 * Creates the file with a header row on the first call, then appends one
	 * data row per subsequent call. Silently bails if the upload directory is
	 * unavailable or the file cannot be created.
	 *
	 * @since 1.10.1
	 *
	 * @param array $source_entry Raw source entry data keyed by source field key.
	 */
	public function record( array $source_entry ): void {

		$this->records[] = $source_entry;
	}

	/**
	 * Write accumulated failed entries to the CSV file and persist the path in the session.
	 *
	 * Prepends a header row on the first call (when the file does not yet exist),
	 * then appends data rows on subsequent chunk calls. Silently bails if the
	 * upload directory is unavailable or the file cannot be opened.
	 *
	 * @since 1.10.1
	 *
	 * @param ImportSession $session Active import session.
	 */
	public function save( ImportSession $session ): void {

		if ( empty( $this->records ) ) {
			return;
		}

		$file_path = $this->get_file_path( $session );

		if ( ! $file_path ) {
			return;
		}

		$source_fields      = $session->get_data( 'source_fields', [] );
		$source_fields_keys = array_column( $source_fields, 'key' );
		$file_exists        = File::exists( $file_path );

		// Sort entry keys according to Source Fields order.
		foreach ( $this->records as $index => $record ) {
			$this->records[ $index ] = $this->build_row( $record, $source_fields_keys );
		}

		if ( ! $file_exists ) {
			array_unshift( $this->records, array_column( $source_fields, 'label' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fp = fopen( $file_path, $file_exists ? 'a' : 'w' ); // Append to existing or write to a new file.

		if ( $fp === false ) {
			return;
		}

		foreach ( $this->records as $record ) {
			fputcsv( $fp, $record, ',', '"', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $fp );

		if ( ! $file_exists ) {
			ImportCleanupTask::register_file( $file_path );
		}
	}

	/**
	 * Build an ordered CSV row from a source entry using field key order.
	 *
	 * @since 1.10.1
	 *
	 * @param array $source_entry Raw source entry data keyed by source field key.
	 * @param array $field_keys   Ordered list of source field keys.
	 *
	 * @return array Indexed array of string values in field key order.
	 */
	private function build_row( array $source_entry, array $field_keys ): array {

		$row = [];

		foreach ( $field_keys as $key ) {
			$row[] = isset( $source_entry[ $key ] ) ? (string) $source_entry[ $key ] : '';
		}

		return $row;
	}

	/**
	 * Return the public download URL for the failed entries CSV.
	 *
	 * Returns an empty string when no entries have failed.
	 *
	 * @since 1.10.1
	 *
	 * @param ImportSession $session Active import session.
	 *
	 * @return string
	 */
	public function get_download_url( ImportSession $session ): string {

		$file_path = $this->get_file_path( $session );

		if ( $file_path === '' ) {
			return '';
		}

		if ( ! File::exists( $file_path ) ) {
			return '';
		}

		$upload_dir = trailingslashit( File::get_upload_dir() );
		$upload_url = trailingslashit( File::get_upload_url() );

		return str_replace( $upload_dir, $upload_url, $file_path );
	}

	/**
	 * Generate and return the file path for the failed entries CSV.
	 *
	 * Returns an empty string if the directory structure cannot be created.
	 *
	 * @since 1.10.1
	 *
	 * @param ImportSession $session Active import session.
	 *
	 * @return string
	 */
	private function get_file_path( ImportSession $session ): string {

		$upload_dir = File::get_upload_dir();
		$tmp_dir    = trailingslashit( $upload_dir . 'import' );

		if ( ! File::mkdir( $upload_dir ) || ! File::mkdir( $tmp_dir ) ) {
			return '';
		}

		wpforms_create_index_html_file( $tmp_dir );

		$request_id = $session->get_request_id();

		return $tmp_dir . sanitize_file_name( 'import-failed-' . $request_id . '.csv' );
	}
}
