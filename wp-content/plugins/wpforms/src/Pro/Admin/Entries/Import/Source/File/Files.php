<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\File;

use WPForms\Pro\Admin\Entries\Import\Source\AbstractFileSource;

/**
 * Registry of all file import sources.
 *
 * @since 1.10.1
 */
class Files {

	/**
	 * Built-in file source classes.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const SOURCES = [ CsvFileSource::class ];

	/**
	 * Return all registered file source classes, including those added via filter.
	 *
	 * @since 1.10.1
	 *
	 * @return AbstractFileSource[]
	 */
	private static function get_sources(): array {

		static $sources = [];

		if ( ! empty( $sources ) ) {
			return $sources;
		}

		foreach ( self::SOURCES as $source ) {
			$sources[ $source ] = new $source();
		}

		/**
		 * Filter registered file import source classes.
		 *
		 * @since 1.10.1
		 *
		 * @param AbstractFileSource[] $sources Indexed array of fully qualified class names.
		 */
		$sources = (array) apply_filters( 'wpforms_pro_admin_entries_import_source_file_files_get_sources', $sources );

		foreach ( $sources as $key => $source ) {
			if ( ! $source instanceof AbstractFileSource ) {
				unset( $sources[ $key ] );
			}
		}

		return $sources;
	}

	/**
	 * Return the source for the given file extension, or null if not found.
	 *
	 * @since 1.10.1
	 *
	 * @param string $ext File extension (without leading dot, e.g. 'csv').
	 *
	 * @return AbstractFileSource|null
	 */
	public static function get_by_extension( string $ext ): ?AbstractFileSource {

		foreach ( static::get_sources() as $source ) {
			if ( in_array( $ext, $source->get_extensions(), true ) ) {
				return $source;
			}
		}

		return null;
	}
}
