<?php

namespace WPForms\Pro\Access;

use WPForms\Helpers\File as FileHelper;
use WPForms\Pro\Forms\Fields\FileUpload\Field as FileUploadField;
use WPForms\Pro\Db\Files\ProtectedFiles;

/**
 * File class.
 *
 * @since 1.9.4
 */
class File {

	/**
	 * Protection hash.
	 *
	 * @since 1.9.5
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * Check if the current page is a file page.
	 *
	 * @since 1.9.4
	 *
	 * @return bool True if the current page is a file page, false otherwise.
	 */
	private function is_file_page(): bool {

		return ! empty( $_GET['wpforms_uploaded_file'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Initialize.
	 *
	 * @since 1.9.4
	 */
	public function init(): void {

		if ( ! $this->is_file_page() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.4
	 */
	private function hooks(): void {

		add_action( 'parse_request', [ $this, 'validate_hash' ], 1 );
		add_action( 'template_redirect', [ $this, 'download_template' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'body_class', [ $this, 'body_class' ] );

		add_filter( 'qm/dispatch/html', '__return_false' );
		add_filter( 'show_admin_bar', '__return_false' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.9.4
	 */
	public function enqueue_scripts(): void {

		$min = wpforms_get_min_suffix();

		$styles = wp_styles();

		// Dequeue all styles.
		foreach ( $styles->queue as $style ) {
			wp_dequeue_style( $style );
		}

		wp_enqueue_style(
			'wpforms-download-page',
			WPFORMS_PLUGIN_URL . "assets/pro/css/frontend/file-download/wpforms-protection-page{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Validate the hash.
	 *
	 * @since 1.9.5
	 */
	public function validate_hash(): void {

		$this->hash = $this->get_protection_hash();

		if ( empty( $this->hash ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Download template.
	 *
	 * @since 1.9.4
	 */
	public function download_template(): void {

		$protected_file = $this->get_protected_file();

		// If the hash is not found, redirect to the home page.
		if ( ! $protected_file ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$restriction_id = $protected_file->restriction_id ?? 0;

		$restriction = wpforms()->obj( 'file_restrictions' )->get( $restriction_id );

		if ( ! $this->is_access_granted( $restriction ) ) {
			$this->render_access_denied();
		}

		// Get the password page.
		$password_page = $this->get_password_page( $restriction );

		if ( ! empty( $password_page ) ) {
			echo $password_page; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		/**
		 * Fires before the download page is rendered.
		 *
		 * @since 1.9.4
		 *
		 * @param ProtectedFiles $protected_file Protected file object.
		 */
		do_action( 'wpforms_pro_access_file_download_template_before_download', $protected_file );

		// If all checks passed, render the download page.
		$this->render_download_page( $protected_file );
	}

	/**
	 * Check if the current user has access to the file.
	 *
	 * @since 1.9.4
	 *
	 * @param object $restriction File restriction object.
	 *
	 * @return bool True if the user has access to the file, false otherwise.
	 */
	private function is_access_granted( $restriction ): bool {

		$restriction_rules = ! empty( $restriction->rules ) ? (array) maybe_unserialize( $restriction->rules ) : [];
		$user_restrictions = $restriction_rules['user_restrictions'] ?? 'none';
		$only_logged_in    = $user_restrictions === 'logged';

		// If the file is protected and only logged-in users can access it, check if the user is logged in.
		if ( $only_logged_in && ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();

		// Check if the current user has access to the file by user role.
		$allow_by_role = $this->allow_by_role( $current_user, $restriction_rules );

		if ( $allow_by_role ) {
			return true;
		}

		// Check if the current user has access to the file by user ID.
		return $this->allow_by_name( $current_user, $restriction_rules );
	}

	/**
	 * Check if the current user has access to the file by user name.
	 *
	 * @since 1.9.4
	 *
	 * @param object $current_user      Current user object.
	 * @param array  $restriction_rules Restriction rules.
	 *
	 * @return bool True if the user has access to the file, false otherwise.
	 */
	private function allow_by_name( $current_user, array $restriction_rules ): bool {

		$user_ids = json_decode( $restriction_rules['user_names_restrictions'] ?? '', true ) ?? [];

		if ( empty( $user_ids ) ) {
			return false;
		}

		$user_ids = array_map( 'intval', $user_ids );

		// Check if the current user has access to the file by user ID.
		if ( in_array( $current_user->ID, $user_ids, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the current user has access to the file by user role.
	 *
	 * @since 1.9.4
	 *
	 * @param object $current_user      Current user object.
	 * @param array  $restriction_rules Restriction rules.
	 *
	 * @return bool True if the user has access to the file, false otherwise.
	 */
	private function allow_by_role( $current_user, array $restriction_rules ): bool {

		$current_user_roles = $current_user->roles ?? [];

		$user_roles = json_decode( $restriction_rules['user_roles_restrictions'] ?? '', true ) ?? [];
		$has_role   = array_intersect( $current_user_roles, $user_roles );

		// Check if the current user has access to the file by user role.
		if ( empty( $has_role ) && ! empty( $user_roles ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the access denied page.
	 *
	 * @since 1.9.4
	 */
	private function render_access_denied(): void {

		echo wpforms_render( 'frontend/file/file-protected' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Render the file not found page.
	 *
	 * @since 1.9.4
	 */
	private function render_file_not_found(): void {

		echo wpforms_render( 'frontend/file/file-not-found' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Get the password page.
	 *
	 * @since 1.9.4
	 *
	 * @param object $restriction File restriction object.
	 *
	 * @return string The password page.
	 */
	private function get_password_page( $restriction ): string {

		$protected_by_password = ! empty( $restriction->password );

		// Check if password protects the file.
		if ( $protected_by_password ) {
			$error_message = '';

			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( wp_verify_nonce( $nonce, 'wpforms-file-password' ) ) {
				$password = isset( $_POST['wpforms_file_password'] ) ? sanitize_text_field( wp_unslash( $_POST['wpforms_file_password'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( wp_check_password( $password, $restriction->password ) ) {
					return '';
				}

				$error_message = esc_html__( 'Sorry, the password you entered is incorrect.', 'wpforms' );
			}

			return $this->get_password_required_form( $error_message );
		}

		return '';
	}

	/**
	 * Render the password required page.
	 *
	 * @since 1.9.4
	 *
	 * @param string $error_message Error message.
	 */
	private function get_password_required_form( string $error_message = '' ): string {

		return wpforms_render( 'frontend/file/file-password-required', [ 'error' => $error_message ], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the download page.
	 *
	 * @since 1.9.4
	 *
	 * @param object $protected_file Protected file object.
	 */
	private function render_download_page( $protected_file ): void {

		$file_path = $this->get_file_path( $protected_file );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			$this->render_file_not_found();
		}

		wpforms()->obj( 'protected_files' )->update_last_usage( $protected_file->id );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		echo FileHelper::get_contents( $file_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Get the protection hash from the URL.
	 *
	 * @since 1.9.4
	 *
	 * @return string The sanitized protection hash.
	 */
	private function get_protection_hash(): string {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$hash = sanitize_key( $_GET['wpforms_uploaded_file'] );

		// The MD5 (Message-digest algorithm) Hash is typically expressed in text format as a 32 digit hexadecimal number.
		// The following regex checks if the hash contains only letters, digits (a-f, 0-9),
		// and length is 32 characters.
		if ( ! preg_match( '/^[a-f0-9]{32}$/', $hash ) ) {
			return '';
		}

		return $hash;
	}

	/**
	 * Get the protected file.
	 *
	 * @since 1.9.5
	 *
	 * @return object|false The protected file object if found, false otherwise.
	 */
	private function get_protected_file() {

		if ( empty( $this->hash ) ) {
			return false;
		}

		return wpforms()->obj( 'protected_files' )->get_by_hash( $this->hash );
	}

	/**
	 * Get the file path.
	 *
	 * @since 1.9.4
	 *
	 * @param object $protected_file Protected file object.
	 *
	 * @return string The file path.
	 */
	private function get_file_path( $protected_file ): string {

		$form_id   = $protected_file->form_id;
		$file_name = $protected_file->file;

		$form_file_path = FileUploadField::get_form_files_path( $form_id );

		return FileUploadField::get_file_path( 0, $file_name, $form_file_path );
	}

	/**
	 * Add custom body class.
	 *
	 * @since 1.9.4
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public function body_class( $classes ): array {

		$classes   = (array) $classes;
		$classes[] = 'wpforms-file-download';

		return $classes;
	}
}
