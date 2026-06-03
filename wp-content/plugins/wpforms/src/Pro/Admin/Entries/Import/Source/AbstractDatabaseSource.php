<?php

namespace WPForms\Pro\Admin\Entries\Import\Source;

use RuntimeException;

/**
 * Abstract database import source.
 *
 * @since 1.10.1
 */
abstract class AbstractDatabaseSource extends AbstractSource {

	/**
	 * Source form ID in the originating plugin's database.
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	protected $source_form_id;

	/**
	 * Current entry-ID cursor.
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	protected $cursor = 0;

	/**
	 * Return the human-readable plugin name for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Return the plugin slug used to identify this source in the registry.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	abstract public function get_plugin_slug(): string;

	/**
	 * Get a list of plugin forms available for import.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	abstract public function get_forms(): array;

	/**
	 * Determine whether this source is available on the current site.
	 *
	 * Checks that every table listed in $required_tables exists in the database.
	 * Concrete classes may override this for plugin-specific checks.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		return true;
	}

	/**
	 * Return the total entry count across all forms for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total_entry_count(): int {

		$forms = $this->get_forms();

		return array_sum( array_column( $forms, 'entry_count' ) );
	}

	/**
	 * Checks if the required database tables exist.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	protected function required_table_exist(): bool {

		if ( empty( static::$required_tables ) ) {
			return false;
		}

		global $wpdb;

		$tables = array_map(
			static function ( $table ) use ( $wpdb ) {

				return $wpdb->prefix . $table;
			},
			static::$required_tables
		);

		$tables_sql = wpforms_wpdb_prepare_in( $tables );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = (int) $wpdb->get_var(
			'SELECT COUNT(*) FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME IN ( ' . $tables_sql . ')' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return $found === count( static::$required_tables );
	}

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param int $source_form_id Source plugin form ID.
	 */
	public function __construct( int $source_form_id = 0 ) {

		$this->source_form_id = $source_form_id;
	}

	/**
	 * Validate the database source.
	 *
	 * @since 1.10.1
	 *
	 * @throws RuntimeException When the source is unavailable.
	 */
	public function validate(): void {

		if ( ! $this->is_available() ) {
			throw new RuntimeException(
				esc_html__( 'The required database tables for this import source are not available.', 'wpforms' )
			);
		}

		if ( empty( $this->get_fields() ) ) {
			throw new RuntimeException(
				esc_html__( 'The source form has no fields or could not be found.', 'wpforms' )
			);
		}
	}

	/**
	 * Return the current entry-ID cursor.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_cursor(): int {

		return $this->cursor;
	}

	/**
	 * Retrieve the arguments for the method.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_args(): array {

		return [ $this->source_form_id ];
	}

	/**
	 * Build WPForms entry meta from raw source entry data.
	 *
	 * Override in concrete sources to map source-specific columns
	 * to WPForms entry column names and entry-meta table rows.
	 *
	 * Flat keys (date, ip_address, user_agent, user_id, viewed, starred) map
	 * directly to wpforms_entries table columns.
	 * The 'entry_meta' sub-array maps type => data for rows in wpforms_entry_meta.
	 *
	 * @since 1.10.1
	 *
	 * @param mixed $source_entry Raw entry data returned by the source query.
	 *
	 * @return array
	 */
	protected function build_entry_meta( $source_entry ): array {

		return [];
	}
}
