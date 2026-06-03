<?php
/**
 * CSV file import source.
 *
 * @noinspection PhpRedundantOptionalArgumentInspection
 */

namespace WPForms\Pro\Admin\Entries\Import\Source\File;

use WPForms\Pro\Admin\Entries\Import\Source\AbstractFileSource;

// WPVIP's PHP Stream Wrapper explicitly supports fopen, fwrite, file_put_contents, etc.
// for files under /wp-content/uploads/ — see https://docs.wpvip.com/vip-file-system/media-uploads/#PHP-Stream-Wrapper.
// The VIP Coding Standards sniff flags these as warnings to discourage arbitrary writes to
// the codebase, but all file operations here target the temp file already placed in
// /wp-content/uploads/ by AbstractFileSource::move_to_upload_tmp(), so they are safe.

// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fread

/**
 * CSV file import source.
 *
 * @since 1.10.1
 */
class CsvFileSource extends AbstractFileSource {

	/**
	 * Allowed MIME types.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	protected $allowed_mime_types = [ 'text/csv', 'text/plain' ];

	/**
	 * Return CSV headers from the first row.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_fields(): array {

		static $fields;

		if ( $fields ) {
			return $fields;
		}

		$fields = [];
		$handle = fopen( $this->file_path, 'rb' );

		if ( ! $handle ) {
			return $fields;
		}

		$row = fgetcsv( $handle, 0, $this->get_delimiter(), '"', '\\' );

		fclose( $handle );

		if ( ! is_array( $row ) ) {
			return $fields;
		}

		$row = array_values( $row );

		foreach ( $row as $key => $item ) {
			$fields[] = [
				'key'   => $key,
				'label' => $item === null || wpforms_is_empty_string( $item )
					? sprintf( /* translators: %1$d – CSV column number.*/
						esc_html__( 'Column #%1$d', 'wpforms' ),
						$key + 1
					)
					: $item,
			];
		}

		return $fields;
	}

	/**
	 * Return the total number of data rows (excludes header).
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total(): int {

		static $total;

		if ( $total !== null ) {
			return $total;
		}

		$handle = fopen( $this->file_path, 'rb' );

		if ( ! $handle ) {
			return 0;
		}

		// Skip the header row.
		fgetcsv( $handle, 0, $this->get_delimiter(), '"', '\\' );

		$count = 0;

		while ( fgetcsv( $handle, 0, $this->get_delimiter(), '"', '\\' ) !== false ) {
			++$count;
		}

		fclose( $handle );

		$total = $count;

		return $total;
	}

	/**
	 * Process a chunk of up to 10 entries starting from the given cursor.
	 *
	 * After processing, the chunk rows are physically removed from the file, so
	 * the next call always reads from the start without a skip loop.
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Number of entries processed so far (used for line-number reporting).
	 * @param int $number_entries Number of entries to process in this chunk.
	 *
	 * @return array
	 */
	public function process_chunk( int $cursor, int $number_entries ): array {

		$handle = fopen( $this->file_path, 'r+b' );

		if ( ! $handle ) {
			return [
				'entries'     => [],
				'next_cursor' => $cursor,
				'errors'      => [],
			];
		}

		$header_row       = fgetcsv( $handle, 0, $this->get_delimiter(), '"', '\\' );
		$after_header_pos = ftell( $handle );

		if ( ! is_array( $header_row ) ) {
			fclose( $handle );

			return [
				'entries'     => [],
				'next_cursor' => $cursor,
				'errors'      => [],
			];
		}

		$fields      = array_map( 'trim', $header_row );
		$field_count = count( $fields );

		// No cursor-skip loop needed — previously processed rows have been truncated.
		$entries = [];
		$errors  = [];
		$read    = 0;

		while ( $read < $number_entries ) {
			$raw_row = fgetcsv( $handle, 0, $this->get_delimiter(), '"', '\\' );

			if ( $raw_row === false ) {
				break;
			}

			++$read;

			$row       = array_map( 'trim', $raw_row );
			$row_count = count( $row );

			if ( $row_count > $field_count ) {
				$row = array_slice( $row, 0, $field_count );
			}

			// Pad short rows with empty strings to handle trailing optional fields trimmed by Excel/Google Sheets.
			if ( $row_count < $field_count ) {
				$row = array_pad( $row, $field_count, '' );
			}

			$entries[] = [
				'id'     => $cursor + $read,
				'fields' => $row,
			];
		}

		// Shift unprocessed rows back to right after the header in fixed-size chunks,
		// then truncate the freed tail — avoids loading the whole remainder into memory.
		$read_pos  = ftell( $handle );
		$write_pos = $after_header_pos;

		while ( ! feof( $handle ) ) {
			fseek( $handle, $read_pos );
			$chunk = fread( $handle, 65536 );

			if ( $chunk === false || $chunk === '' ) {
				break;
			}

			$read_pos += strlen( $chunk );

			fseek( $handle, $write_pos );
			fwrite( $handle, $chunk );
			$write_pos += strlen( $chunk );
		}

		ftruncate( $handle, $write_pos );
		fclose( $handle );

		$next_cursor  = $cursor + $read;
		$this->cursor = $next_cursor;

		return [
			'entries'     => $entries,
			'next_cursor' => $next_cursor,
			'errors'      => $errors,
		];
	}

	/**
	 * Return the cached delimiter, detecting it on the first call.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	private function get_delimiter(): string {

		static $delimiter;

		if ( $delimiter ) {
			return $delimiter;
		}

		$delimiter = ',';
		$handle    = fopen( $this->file_path, 'rb' );

		if ( $handle ) {
			$line = rtrim( fgets( $handle ), "\n" );

			$delimiter = substr_count( $line, ';' ) > substr_count( $line, ',' ) ? ';' : ',';

			fclose( $handle );
		}

		/**
		 * Allow changing the delimiter.
		 *
		 * @since 1.10.1
		 *
		 * @param string $delimiter Delimiter.
		 */
		$delimiter = (string) apply_filters( 'wpforms_pro_admin_entries_import_source_file_csv_file_source_delimiter', $delimiter );

		return $delimiter;
	}

	/**
	 * List of supported file extensions.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_extensions(): array {

		return [ 'csv' ];
	}
}

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fread
