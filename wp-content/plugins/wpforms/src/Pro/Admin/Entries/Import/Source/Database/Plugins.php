<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\Database;

use WPForms\Pro\Admin\Entries\Import\Source\AbstractDatabaseSource;

/**
 * Registry of all database import sources.
 *
 * @since 1.10.1
 */
class Plugins {

	/**
	 * Built-in database source classes.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const SOURCES = [ FlamingoDbSource::class, NinjaFormsDbSource::class, GravityFormsDbSource::class ];

	/**
	 * Return all registered database source classes, including those added via filter.
	 *
	 * @since 1.10.1
	 *
	 * @return AbstractDatabaseSource[]
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
		 * Filter registered database import source classes.
		 *
		 * @since 1.10.1
		 *
		 * @param AbstractDatabaseSource[] $sources Indexed array of fully qualified class names.
		 */
		$sources = (array) apply_filters( 'wpforms_pro_admin_entries_import_source_databases_get_sources', $sources );

		foreach ( $sources as $key => $source ) {
			if ( ! $source instanceof AbstractDatabaseSource ) {
				unset( $sources[ $key ] );
			}
		}

		$sources = array_merge( [ new WPFormsDbSource() ], $sources );

		return $sources;
	}

	/**
	 * Return the source class name for the given plugin slug, or null if not found.
	 *
	 * @since 1.10.1
	 *
	 * @param string $slug Plugin slug (e.g. 'cf7', 'wpforms').
	 *
	 * @return AbstractDatabaseSource|null
	 */
	public static function get_by_slug( string $slug ): ?AbstractDatabaseSource {

		foreach ( static::get_sources() as $source ) {
			if ( $source->get_plugin_slug() === $slug ) {
				return $source;
			}
		}

		return null;
	}

	/**
	 * Return all database import sources with their availability status.
	 *
	 * @since 1.10.1
	 *
	 * @return array<string, array{name: string, entry_count: int, available: bool}> Map of slug => plugin data.
	 */
	public static function get_all(): array {

		static $all = null;

		if ( $all !== null ) {
			return $all;
		}

		$all = [];

		foreach ( static::get_sources() as $source ) {
			$slug = $source->get_plugin_slug();

			if ( $slug === '' ) {
				continue;
			}

			$is_available = $source->is_available();

			if ( ! $is_available ) {
				continue;
			}

			$all[ $slug ] = [
				'name'        => $source->get_name(),
				'entry_count' => $source->get_total_entry_count(),
			];
		}

		return $all;
	}
}
