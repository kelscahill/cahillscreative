<?php

namespace WPForms\Pro\Db\Analytics;

use WPForms_DB;

/**
 * Custom-tables handler for the Pro analytics snapshot-fields table.
 *
 * Owns the schema for wp_wpforms_analytics_snapshot_fields and registers it
 * with the self-healing custom-tables registry (WPForms_Pro::CUSTOM_TABLES).
 * Read/write access lives in WPForms\Pro\Db\Analytics\DB.
 *
 * @since 2.0.0
 */
class SnapshotFields extends WPForms_DB {

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->table_name  = self::get_table_name();
		$this->primary_key = 'id';
		$this->type        = 'analytics_snapshot_fields';
	}

	/**
	 * Get the table name.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_table_name(): string {

		return DB::snapshot_fields_table();
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
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			snapshot_id   BIGINT(20) UNSIGNED NOT NULL,
			field_id      INT(10)    UNSIGNED NOT NULL,
			subfield_key  VARCHAR(64)         NOT NULL DEFAULT '',
			was_displayed TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			focus_count   SMALLINT   UNSIGNED NOT NULL DEFAULT 0,
			click_count   SMALLINT   UNSIGNED NOT NULL DEFAULT 0,
			input_count   SMALLINT   UNSIGNED NOT NULL DEFAULT 0,
			errors        SMALLINT   UNSIGNED NOT NULL DEFAULT 0,
			duration_ms   INT(10)    UNSIGNED NULL     DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_snapshot (snapshot_id),
			KEY idx_snapshot_field (snapshot_id, field_id, subfield_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $query );
	}
}
