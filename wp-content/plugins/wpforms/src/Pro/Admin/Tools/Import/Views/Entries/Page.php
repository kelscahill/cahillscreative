<?php

namespace WPForms\Pro\Admin\Tools\Import\Views\Entries;

use WPForms\Admin\Tools\Import\Views\ImportViewsInterface;

/**
 * Pro Entries Import Page class.
 *
 * Wraps the existing Pro Entries Import functionality for display
 * within the Import orchestrator's tabbed interface.
 *
 * @since 1.10.1
 */
class Page implements ImportViewsInterface {

	/**
	 * Initialize class.
	 *
	 * @since 1.10.1
	 */
	public function init(): void {
	}

	/**
	 * Check if the current user has the capability to view the page.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function current_user_can(): bool {

		return wpforms_current_user_can( [ 'edit_forms', 'view_entries' ] );
	}

	/**
	 * Page content.
	 *
	 * @since 1.10.1
	 */
	public function display(): void {

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->error_unfiltered_html_import_message();

			return;
		}

		$entries_import = wpforms()->obj( 'entries_import' );

		// Check if user has no forms at all.
		if ( empty( $entries_import->get_accessible_forms() ) ) {
			$this->no_forms_notice();

			return;
		}

		// Check if user has forms but none with supported fields.
		if ( empty( $entries_import->get_forms_with_supported_fields() ) ) {
			$this->no_eligible_forms_notice();

			return;
		}

		$entries_import->display();
	}

	/**
	 * Display notice when no forms are available.
	 *
	 * @since 1.10.1
	 */
	private function no_forms_notice(): void {

		printf(
			'<div class="notice notice-info inline"><p>%s</p></div>',
			sprintf(
				wp_kses( /* translators: %1$s - Create a Form link URL, %2$s - Import a Form link URL. */
					__( '<strong>No forms found.</strong> To import entries, you\'ll need a form to import them into. <a href="%1$s">Create a Form</a> or <a href="%2$s">Import a Form</a>.', 'wpforms' ),
					[
						'strong' => [],
						'a'      => [
							'href' => [],
						],
					]
				),
				esc_url( admin_url( 'admin.php?page=wpforms-builder' ) ),
				esc_url( admin_url( 'admin.php?page=wpforms-tools&view=import&tab=forms' ) )
			)
		);
	}

	/**
	 * Display notice when forms exist but none have supported fields.
	 *
	 * @since 1.10.1
	 */
	private function no_eligible_forms_notice(): void {

		printf(
			'<div class="notice notice-info inline"><p>%s</p></div>',
			wp_kses(
				__( '<strong>No eligible forms found.</strong> Entry importing requires forms with at least one data field.', 'wpforms' ),
				[ 'strong' => [] ]
			)
		);
	}

	/**
	 * Error message for users with no `unfiltered_html` permission.
	 *
	 * @since 1.10.1
	 */
	private function error_unfiltered_html_import_message(): void {

		printf(
			'<div class="notice notice-error inline"><p>%s</p></div>',
			sprintf(
				wp_kses( /* translators: %s - WPForms contact page URL. */
					__( 'You can\'t import entries because you don\'t have unfiltered HTML permissions. Please contact your site administrator or <a href="%s" target="_blank" rel="noopener noreferrer">reach out to our support team</a>.', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				esc_url( wpforms_utm_link( 'https://wpforms.com/contact/', 'Tools - Import', 'Support Link - Import Entries No Permissions' ) )
			)
		);
	}

	/**
	 * Get the Tab label.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_tab_label(): string {

		return __( 'Import Entries', 'wpforms' );
	}
}
