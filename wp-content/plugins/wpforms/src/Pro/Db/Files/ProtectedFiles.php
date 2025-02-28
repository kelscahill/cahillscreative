<?php

namespace WPForms\Pro\Db\Files;

use WPForms_DB;

/**
 * Class ProtectedFiles.
 *
 * @since 1.9.4
 */
class ProtectedFiles extends WPForms_DB {

	/**
	 * Constructor.
	 *
	 * @since 1.9.4
	 */
	public function __construct() {

		parent::__construct();

		$this->table_name  = self::get_table_name();
		$this->primary_key = 'hash';
		$this->type        = 'protected_files';
	}

	/**
	 * Get the table name.
	 *
	 * @since 1.9.4
	 *
	 * @return string
	 */
	public static function get_table_name(): string {

		global $wpdb;

		return $wpdb->prefix . 'wpforms_protected_files';
	}

	/**
	 * Get the table columns.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	public function get_columns(): array {

		return [
			'id'             => '%d',
			'entry_id'       => '%d',
			'form_id'        => '%d',
			'restriction_id' => '%d',
			'hash'           => '%s',
			'file'           => '%s',
			'last_usage_at'  => '%s',
		];
	}

	/**
	 * Get the default column values.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	public function get_column_defaults(): array {

		return [
			'id'             => 0,
			'entry_id'       => 0,
			'form_id'        => 0,
			'restriction_id' => 0,
			'hash'           => '',
			'file'           => '',
			'last_usage_at'  => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		];
	}

	/**
	 * Create the table.
	 *
	 * @since 1.9.4
	 */
	public function create_table(): void {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) NOT NULL,
			form_id bigint(20) NOT NULL,
			restriction_id bigint(20) NOT NULL,
			hash varchar(64) NOT NULL,
			file longtext NOT NULL,
			last_usage_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY entry_id (entry_id),
			KEY form_id (form_id),
			KEY restriction_id (restriction_id),
			KEY hash (hash(32))
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Get by hash.
	 *
	 * @since 1.9.4
	 *
	 * @param string $hash Hash.
	 *
	 * @return object|false
	 */
	public function get_by_hash( string $hash ) {

		return $this->get_by( 'hash', $hash );
	}

	/**
	 * Create protection.
	 *
	 * @since 1.9.4
	 *
	 * @param array $args Protection arguments.
	 */
	public function create_protection( array $args ): void {

		$this->add( $args );
	}

	/**
	 * Delete protection by hash.
	 *
	 * @since 1.9.4
	 *
	 * @param string $hash Hash.
	 */
	public function delete_protection( $hash ): void {

		$this->delete_where_in( 'hash', $hash );
	}

	/**
	 * Update last usage.
	 *
	 * @since 1.9.4
	 *
	 * @param int $file_id File ID.
	 */
	public function update_last_usage( $file_id ): void {

		$this->update( $file_id, [ 'last_usage_at' => date( 'Y-m-d H:i:s' ) ], 'id' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}
}
