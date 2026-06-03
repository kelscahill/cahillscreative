<?php

namespace WPForms\Pro\Admin\Tools\Export\Views\Entries;

use WPForms\Admin\Tools\Export\Views\ExportViewsInterface;
use WPForms\Helpers\Form;

/**
 * Pro Entries Export Page class.
 *
 * Wraps the existing Pro entries export functionality
 * for the new tabbed Export interface.
 *
 * @since 1.10.1
 */
class Page implements ExportViewsInterface {

	/**
	 * Initialize class.
	 *
	 * @since 1.10.1
	 */
	public function init(): void {
	}

	/**
	 * Get the Tab label.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_tab_label(): string {

		return __( 'Export Entries', 'wpforms' );
	}

	/**
	 * Check if the current user has the capability to view the page.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function current_user_can(): bool {

		return wpforms_current_user_can( 'view_entries' );
	}

	/**
	 * Page content.
	 *
	 * @since 1.10.1
	 */
	public function display(): void {

		$forms = Form::get_all();

		if ( empty( $forms ) ) {
			echo wpforms_render( 'admin/empty-states/no-forms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return;
		}

		$export = wpforms()->obj( 'entries_export' );

		if ( $export && isset( $export->admin ) ) {
			$export->admin->display();
		}
	}
}
