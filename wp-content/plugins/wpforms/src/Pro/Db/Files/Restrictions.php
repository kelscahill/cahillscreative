<?php

namespace WPForms\Pro\Db\Files;

use WPForms_DB;

/**
 * Class Restrictions.
 *
 * @since 1.9.4
 */
class Restrictions extends WPForms_DB {

	/**
	 * Constructor.
	 *
	 * @since 1.9.4
	 */
	public function __construct() {

		parent::__construct();

		$this->table_name  = self::get_table_name();
		$this->primary_key = 'id';
		$this->type        = 'restrictions';
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

		return $wpdb->prefix . 'wpforms_file_restrictions';
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
			'id'       => '%d',
			'form_id'  => '%d',
			'field_id' => '%d',
			'password' => '%s',
			'rules'    => '%s',
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
			'id'       => 0,
			'form_id'  => 0,
			'field_id' => 0,
			'password' => '',
			'rules'    => '',
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
			form_id bigint(20) NOT NULL,
			field_id bigint(20) NOT NULL,
			password varchar(255) NOT NULL,
			rules longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY field_id (field_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Add a restriction.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $form_id  Form ID.
	 * @param int    $field_id Field ID.
	 * @param array  $rules    Restriction rules.
	 * @param string $password Restriction password.
	 */
	public function add_restriction( $form_id, $field_id, $rules, $password = '' ): void {

		$data = [
			'form_id'  => $form_id,
			'field_id' => $field_id,
			'password' => ! empty( $password ) ? wp_hash_password( $password ) : '',
			'rules'    => ! empty( $rules ) ? maybe_serialize( $rules ) : '',
		];

		$this->add( $data );
	}

	/**
	 * Get a restriction.
	 *
	 * @since 1.9.4
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 *
	 * @return array
	 */
	public function get_restriction( $form_id, $field_id ): array {

		global $wpdb;

		$restriction = $this->get_results(
			$wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE form_id = %d AND field_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$form_id,
				$field_id
			),
			ARRAY_A
		);

		return ! empty( $restriction ) ? $restriction[0] : [];
	}

	/**
	 * Update restriction rules.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $restriction_id Restriction ID.
	 * @param array $rules          Restriction rules.
	 */
	public function update_restriction_rules( $restriction_id, $rules ): void {

		$data = [
			'rules' => maybe_serialize( $rules ),
		];

		$this->update( $restriction_id, $data );
	}

	/**
	 * Update restriction password.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $restriction_id Restriction ID.
	 * @param string $password       Restriction password.
	 */
	public function update_restriction_password( $restriction_id, $password ): void {

		$data = [
			'password' => ! empty( $password ) ? wp_hash_password( $password ) : '',
		];

		$this->update( $restriction_id, $data );
	}
}
