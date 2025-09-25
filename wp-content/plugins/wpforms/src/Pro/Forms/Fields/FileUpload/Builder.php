<?php

namespace WPForms\Pro\Forms\Fields\FileUpload;

/**
 * File Upload field builder class.
 *
 * @since 1.9.4
 */
class Builder {

	/**
	 * User restriction rules.
	 *
	 * @since 1.9.4
	 */
	private const USER_RESTRICTION_RULES = [
		'user_restrictions',
		'user_roles_restrictions',
		'user_names_restrictions',
	];

	/**
	 * Password protection rules.
	 *
	 * @since 1.9.4
	 */
	private const PASSWORD_PROTECTION_RULES = [
		'protection_password',
		'protection_password_confirm',
	];

	/**
	 * Files passwords.
	 *
	 * @since 1.9.4
	 *
	 * @var array
	 */
	private $files_passwords = [];

	/**
	 * Constructor.
	 *
	 * @since 1.9.4
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.9.4
	 */
	private function hooks(): void {

		add_action( 'wpforms_builder_enqueues', [ $this, 'builder_enqueues' ] );

		add_filter( 'wpforms_save_form_args', [ $this, 'prepare_restrictions' ], 10, 3 );
		add_action( 'wpforms_save_form', [ $this, 'update_fields_restrictions' ], 10, 2 );
	}

	/**
	 * Enqueue script for the admin form builder.
	 *
	 * @since 1.9.4
	 */
	public function builder_enqueues(): void {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-builder-file-upload-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/file-upload{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION,
			false
		);

		wp_enqueue_script(
			'wpforms-builder-camera',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/camera{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Update restrictions for the file upload field.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $form_id Form ID.
	 * @param array $form    Form data.
	 */
	public function update_fields_restrictions( int $form_id, array $form ): void {

		$form_data = json_decode( stripslashes( $form['post_content'] ), true );
		$fields    = $form_data['fields'] ?? [];

		foreach ( $fields as $field_id => $field ) {
			$this->process_field_restriction( $form_id, $field_id, $field );
		}

		// Remove all files passwords.
		$this->files_passwords = [];
	}

	/**
	 * Process field restriction.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $form_id  Form ID.
	 * @param int   $field_id Field ID.
	 * @param array $field    Field data.
	 */
	private function process_field_restriction( int $form_id, int $field_id, array $field ): void {

		$type = $field['type'] ?? '';

		if ( $type !== 'file-upload' || empty( $field['is_restricted'] ) ) {
			return;
		}

		$file_restrictions = wpforms()->obj( 'file_restrictions' );

		if ( ! $file_restrictions ) {
			return;
		}

		$rules = $this->prepare_rules( $field );

		$restriction = $file_restrictions->get_restriction( $form_id, $field_id );
		$password    = $this->files_passwords[ $field_id ] ?? '';

		// If no restriction exists, add a new one.
		if ( empty( $restriction ) ) {
			$file_restrictions->add_restriction( $form_id, $field_id, $rules, $password );

			return;
		}

		$this->update_restrictions( $restriction['id'], $rules, $password, $restriction );
	}

	/**
	 * Prepare restrictions.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form Post data.
	 * @param array $data Form data.
	 * @param array $args Arguments.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function prepare_restrictions( $form, $data, $args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$form      = (array) $form;
		$form_data = json_decode( stripslashes( $form['post_content'] ), true );
		$fields    = $form_data['fields'] ?? [];

		foreach ( $fields as $key => $field ) {
			if ( $this->is_file_upload_field( $field ) ) {
				$this->process_field_restrictions( $form_data, $key, $field );
			}
		}

		$form['post_content'] = wpforms_encode( $form_data );

		return $form;
	}

	/**
	 * Check if the field is a file upload field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field Field data.
	 *
	 * @return bool
	 */
	private function is_file_upload_field( array $field ): bool {

		return ! empty( $field['type'] ) && $field['type'] === 'file-upload';
	}

	/**
	 * Process field restrictions.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data.
	 * @param int   $key       Field key.
	 * @param array $field     Field data.
	 */
	private function process_field_restrictions( array &$form_data, int $key, array $field ): void {

		if ( empty( $field['is_restricted'] ) ) {
			$this->remove_all_restrictions( $form_data, $key );

			return;
		}

		$this->handle_restrictions( $form_data, $key, $field );
	}

	/**
	 * Remove all restrictions.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data.
	 * @param int   $key       Field key.
	 */
	private function remove_all_restrictions( array &$form_data, int $key ): void {

		foreach ( self::USER_RESTRICTION_RULES as $rule ) {
			unset( $form_data['fields'][ $key ][ $rule ] );
		}

		foreach ( self::PASSWORD_PROTECTION_RULES as $rule ) {
			unset( $form_data['fields'][ $key ][ $rule ] );
		}

		unset( $form_data['fields'][ $key ]['is_protected'] );
	}

	/**
	 * Handle restrictions.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data.
	 * @param int   $key       Field key.
	 * @param array $field     Field data.
	 */
	private function handle_restrictions( array &$form_data, int $key, array $field ): void {

		if ( isset( $field['user_restrictions'] ) && $field['user_restrictions'] === 'none' ) {
			foreach ( self::USER_RESTRICTION_RULES as $rule ) {
				unset( $form_data['fields'][ $key ][ $rule ] );
			}
		}

		if ( empty( $field['is_protected'] ) ) {
			foreach ( self::PASSWORD_PROTECTION_RULES as $rule ) {
				unset( $form_data['fields'][ $key ][ $rule ] );
			}

			return;
		}

		$this->process_password_protection( $form_data, $key, $field );
	}

	/**
	 * Process password protection.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data.
	 * @param int   $key       Field key.
	 * @param array $field     Field data.
	 */
	private function process_password_protection( array &$form_data, int $key, array $field ): void {

		$file_password = $field['protection_password'] ?? '';

		if ( ! empty( $file_password ) ) {
			$this->files_passwords[ $key ] = $file_password;
		}

		$password = str_repeat( '*', strlen( $file_password ) );

		foreach ( self::PASSWORD_PROTECTION_RULES as $rule ) {
			if ( isset( $form_data['fields'][ $key ][ $rule ] ) ) {
				$form_data['fields'][ $key ][ $rule ] = $password;
			}
		}
	}

	/**
	 * Prepare restriction rules.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	private function prepare_rules( array $field ): array {

		$rules = [];

		foreach ( self::USER_RESTRICTION_RULES as $rule ) {
			if ( ! empty( $field[ $rule ] ) ) {
				$rules[ $rule ] = wp_unslash( $field[ $rule ] );
			}
		}

		return $rules;
	}

	/**
	 * Update restrictions.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $restriction_id Restriction ID.
	 * @param array  $rules          Restriction rules.
	 * @param string $password       Restriction password.
	 * @param array  $restriction    Restriction data.
	 */
	private function update_restrictions( int $restriction_id, array $rules, string $password, array $restriction ): void {

		$file_restrictions = wpforms()->obj( 'file_restrictions' );

		$restriction_rules = $restriction['rules'] ?? '';

		// If restriction rules have changed, update them.
		if ( maybe_serialize( $rules ) !== $restriction_rules ) {
			$file_restrictions->update_restriction_rules( $restriction_id, $rules );
		}

		// If the password consists only of asterisks, it means that the password has not been changed.
		if ( preg_match( '/^\*+$/', $password ) ) {
			return;
		}

		$restriction_password = $restriction['password'] ?? '';

		// If the restriction password has changed, update it.
		if ( empty( $password ) || ! wp_check_password( $password, $restriction_password ) ) {
			$file_restrictions->update_restriction_password( $restriction_id, $password );
		}
	}
}
