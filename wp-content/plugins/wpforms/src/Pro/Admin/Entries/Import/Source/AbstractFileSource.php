<?php

namespace WPForms\Pro\Admin\Entries\Import\Source;

use RuntimeException;
use WPForms\Helpers\File;
use WPForms\Pro\Tasks\Actions\ImportCleanupTask;

/**
 * Abstract file import source.
 *
 * @since 1.10.1
 */
abstract class AbstractFileSource extends AbstractSource {

	/**
	 * Absolute path to the source file.
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	protected $file_path;

	/**
	 * Current line-number cursor (0-based data row index, excludes header row).
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	protected $cursor = 0;

	/**
	 * Allowed MIME types for this source.
	 *
	 * Concrete classes must define this.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	protected $allowed_mime_types = [];

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param string $file_path Absolute path to the file to import.
	 */
	public function __construct( string $file_path = '' ) {

		$this->file_path = $file_path;
	}

	/**
	 * Validate the file source.
	 *
	 * @since 1.10.1
	 *
	 * @throws RuntimeException When the file is invalid.
	 */
	public function validate(): void {

		$filesystem = File::get_filesystem();

		if ( ! $filesystem || ! $filesystem->is_readable( $this->file_path ) ) {
			throw new RuntimeException(
				esc_html__( 'Import file does not exist or is not readable.', 'wpforms' )
			);
		}

		$file_size = $filesystem->size( $this->file_path );

		// An empty file has no detectable MIME type and would otherwise surface
		// the misleading "Import file type is not allowed." error.
		if ( $file_size === 0 ) {
			throw new RuntimeException(
				esc_html__( 'Import file is empty.', 'wpforms' )
			);
		}

		if ( ! $this->is_valid_mime_type() ) {
			throw new RuntimeException(
				esc_html__( 'Import file is not a valid CSV.', 'wpforms' )
			);
		}

		if ( $file_size !== false && $file_size > wp_max_upload_size() ) {
			throw new RuntimeException(
				sprintf( /* translators: %s: maximum allowed upload size. */
					esc_html__( 'Import file exceeds the maximum allowed upload size (%s).', 'wpforms' ),
					esc_html( size_format( wp_max_upload_size() ) )
				)
			);
		}

		$this->move_to_upload_tmp( 'import_' . wp_hash( $this->file_path . microtime() ) . '.csv' );

		if ( empty( $this->get_fields() ) ) {
			throw new RuntimeException(
				esc_html__( 'Import file has no header row or columns.', 'wpforms' )
			);
		}

		if ( empty( $this->get_total() ) ) {
			throw new RuntimeException(
				esc_html__( 'Import file has no rows or columns.', 'wpforms' )
			);
		}
	}

	/**
	 * Move the uploaded temp file into the WPForms upload tmp directory.
	 *
	 * @since 1.10.1
	 *
	 * @param string $dest_filename Target filename (without a path).
	 *
	 * @throws RuntimeException When the directory cannot be created or the file cannot be moved.
	 */
	protected function move_to_upload_tmp( string $dest_filename ): void {

		$upload_dir = File::get_upload_dir();
		$tmp_dir    = trailingslashit( wp_normalize_path( ( $upload_dir . 'import' ) ) );

		if ( ! File::mkdir( $upload_dir ) || ! File::mkdir( $tmp_dir ) ) {
			throw new RuntimeException(
				esc_html__( 'Could not create the import temporary directory.', 'wpforms' )
			);
		}

		wpforms_create_index_html_file( $tmp_dir );

		$dest = $tmp_dir . sanitize_file_name( $dest_filename );

		$content = File::get_contents( $this->file_path );

		if ( $content === false || ! mb_check_encoding( $content, 'UTF-8' ) ) {
			throw new RuntimeException(
				esc_html__( 'Import file must be UTF-8 encoded.', 'wpforms' )
			);
		}

		$content = File::remove_utf8_bom( $content );
		// Normalize new lines to have a consistent format.
		$content = str_replace( [ "\r\n", "\r" ], "\n", $content );

		if ( ! File::put_contents( $dest, $content ) ) {
			throw new RuntimeException(
				esc_html__( 'There appears to be a problem with your import file. Please check the file and try again.', 'wpforms' )
			);
		}

		$this->file_path = $dest;

		ImportCleanupTask::register_file( $dest );
	}

	/**
	 * Return the current line-number cursor.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_cursor(): int {

		return $this->cursor;
	}

	/**
	 * Retrieves the arguments.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_args(): array {

		return [
			$this->file_path,
		];
	}

	/**
	 * List of supported file extensions.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	abstract public function get_extensions(): array;

	/**
	 * Validate that a file's MIME type matches the allowed types for a source.
	 *
	 * @since 1.10.1
	 *
	 * @return bool True if MIME type is valid, false otherwise.
	 */
	private function is_valid_mime_type(): bool {

		if ( empty( $this->allowed_mime_types ) ) {
			return false;
		}

		$detected_mime = $this->detect_mime_type( $this->file_path );

		return in_array( $detected_mime, $this->allowed_mime_types, true );
	}

	/**
	 * Detect the MIME type of file.
	 *
	 * @since 1.10.1
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return string The detected MIME type or empty string on failure.
	 */
	private function detect_mime_type( string $file_path ): string {

		if ( ! function_exists( 'mime_content_type' ) ) {
			return '';
		}

		$mime = mime_content_type( $file_path );

		return $mime !== false ? $mime : '';
	}
}
