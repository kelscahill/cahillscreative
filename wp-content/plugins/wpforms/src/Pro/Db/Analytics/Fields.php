<?php

namespace WPForms\Pro\Db\Analytics;

use WPForms_DB;

/**
 * Custom-tables handler for the Pro analytics fields aggregate table.
 *
 * Owns the schema for wp_wpforms_analytics_fields and registers it with the
 * self-healing custom-tables registry. Read/write access lives in
 * WPForms\Pro\Db\Analytics\DB. The table uses a composite primary key
 * (form_id, field_id, subfield_key, period_date); the inherited scalar-PK CRUD is never used.
 *
 * @since 2.0.0
 */
class Fields extends WPForms_DB {

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->table_name  = self::get_table_name();
		$this->primary_key = 'form_id';
		$this->type        = 'analytics_fields';
	}

	/**
	 * Get the table name.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_table_name(): string {

		return DB::fields_table();
	}

	/**
	 * Create the table.
	 *
	 * @since 2.0.0
	 */
	public function create_table(): void {

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$query = "CREATE TABLE $this->table_name (
			form_id           BIGINT(20) UNSIGNED NOT NULL,
			field_id          INT(10)    UNSIGNED NOT NULL,
			subfield_key      VARCHAR(64)         NOT NULL DEFAULT '',
			period_date       DATE                NOT NULL,
			views             INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			focus_count       INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			click_count       INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			input_count       INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			abandonments      INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			errors            INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			total_duration_ms BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			duration_count    INT(10)    UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (form_id, field_id, subfield_key, period_date)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $query );
	}
}
