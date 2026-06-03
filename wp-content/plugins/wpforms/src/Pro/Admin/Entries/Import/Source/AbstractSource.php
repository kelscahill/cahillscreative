<?php

namespace WPForms\Pro\Admin\Entries\Import\Source;

use RuntimeException;

/**
 * Abstract import source.
 *
 * @since 1.10.1
 */
abstract class AbstractSource {

	/**
	 * Return the source field definitions.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	abstract public function get_fields(): array;

	/**
	 * Return the current progress cursor.
	 *
	 * For file sources this is a line number.
	 * For database sources this is an entry ID.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	abstract public function get_cursor(): int;

	/**
	 * Return the total number of imported entries.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	abstract public function get_total(): int;

	/**
	 * Validate the source.
	 *
	 * Throws RuntimeException with a translatable message on failure.
	 *
	 * @since 1.10.1
	 *
	 * @throws RuntimeException When validation fails.
	 */
	abstract public function validate(): void;

	/**
	 * Process a chunk of up to 10 entries starting from the given cursor.
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Number of entries we should skip processing.
	 * @param int $number_entries Number of entries to process in this chunk.
	 *
	 * @return array {
	 *     @type array $entries     Normalised entry data arrays.
	 *     @type int   $next_cursor Updated cursor position after this chunk.
	 *     @type array $errors      Per-entry error messages keyed by position.
	 * }
	 */
	abstract public function process_chunk( int $cursor, int $number_entries ): array;

	/**
	 * Retrieve the arguments for the process.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	abstract public function get_args(): array;
}
