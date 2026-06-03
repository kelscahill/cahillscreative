<?php

namespace WPForms\Pro\Admin\Entries\Import;

use WPForms\Helpers\Form;
use WPForms\Pro\Admin\Entries\Import\Source\Database\Plugins;
use WPForms\Pro\Admin\Tools\Import\Views\Entries\Page as ProEntriesPage;
use WPForms\Pro\Tasks\Actions\ImportCleanupTask;

/**
 * Entries Import class.
 *
 * @since 1.10.1
 */
class Import {

	/**
	 * Initialise the import feature and register hooks.
	 *
	 * @since 1.10.1
	 */
	public function init(): void { //phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		add_filter( 'wpforms_tasks_get_tasks', [ $this, 'register_cleanup_task' ] );

		if ( ! is_admin() || ! wpforms_current_user_can( [ 'edit_forms', 'view_entries' ] ) ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Add the import cleanup task to the task list.
	 *
	 * @since 1.10.1
	 *
	 * @param array $tasks Registered task class names.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function register_cleanup_task( $tasks ): array {

		$tasks = (array) $tasks;

		$tasks[] = ImportCleanupTask::class;

		return $tasks;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.10.1
	 */
	private function hooks(): void {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'wpforms_admin_tools_views_import_get_sub_views', [ $this, 'register_pro_entries_view' ], 20 );
	}

	/**
	 * Register Pro Entries import view to replace Education placeholder.
	 *
	 * @since 1.10.1
	 *
	 * @param array $views Import views.
	 *
	 * @return array
	 */
	public function register_pro_entries_view( array $views ): array {

		$entry = wpforms()->obj( 'entry' );

		if ( $entry !== null ) {
			$views['entries'] = new ProEntriesPage();
		}

		return $views;
	}

	/**
	 * Register assets for the Entries Import view.
	 *
	 * @since 1.10.1
	 */
	public function enqueue_assets(): void {

		$min = wpforms_get_min_suffix();

		wp_register_style(
			'wpforms-admin-entries-import',
			WPFORMS_PLUGIN_URL . "assets/pro/css/admin/admin-entries-import{$min}.css",
			[ 'wpforms-admin' ],
			WPFORMS_VERSION
		);

		wp_register_script(
			'wpforms-admin-entries-import',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/entries/tools-entries-import{$min}.js",
			[ 'jquery', 'wpforms-admin', 'jquery-confirm', 'wp-i18n' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Get a link to the import view.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	private function get_link(): string {

		return admin_url( 'admin.php?page=wpforms-tools&view=import' );
	}

	/**
	 * Display the import UI.
	 *
	 * Called from the Tools Import view.
	 *
	 * @since 1.10.1
	 */
	public function display(): void {

		wp_enqueue_style( 'wpforms-admin-entries-import' );
		wp_enqueue_script( 'wpforms-admin-entries-import' );
		wp_set_script_translations( 'wpforms-admin-entries-import', 'wpforms' );

		wp_localize_script(
			'wpforms-admin-entries-import',
			'wpforms_entries_import',
			[
				'nonce'                   => wp_create_nonce( 'wpforms-entry-import' ),
				'destination_field_forms' => Plugins::get_by_slug( 'wpforms' )->get_forms(),
				'supported_plugins'       => Plugins::get_all(),
				'entries_url'             => admin_url( 'admin.php?page=wpforms-entries&view=list&form_id=' ),
				'view_entry_url'          => admin_url( 'admin.php?page=wpforms-entries&view=details&entry_id=' ),
				'builder_url'             => admin_url( 'admin.php?page=wpforms-builder&view=fields&form_id=' ),
			]
		);

		$this->display_entry_import_block();
	}

	/**
	 * Display step 1: Source selection (Plugin or Export File).
	 *
	 * @since 1.10.1
	 */
	private function display_entry_import_block(): void {

		$forms             = $this->get_forms_with_supported_fields();
		$supported_plugins = Plugins::get_all();
		$form_action_url   = $this->get_link();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render(
			'admin/entries/import/entry-import-block',
			[
				'forms'             => $forms,
				'supported_plugins' => $supported_plugins,
				'form_action_url'   => $form_action_url,
			],
			true
		);
	}

	/**
	 * Get forms that the current user can edit entries for.
	 *
	 * @since 1.10.1
	 *
	 * @return array Array of form objects.
	 */
	public function get_accessible_forms(): array {

		$forms  = Form::get_all();
		$access = wpforms()->obj( 'access' );

		if ( $access ) {
			$forms = $access->filter_forms_by_current_user_capability( $forms, 'edit_entries_form_single' );
		}

		return $forms;
	}

	/**
	 * Get forms with supported fields for import.
	 *
	 * Returns forms that the current user can edit entries for and that have
	 * at least one field type supported by the entry importer.
	 *
	 * @since 1.10.1
	 *
	 * @return array Array of form objects with supported fields.
	 */
	public function get_forms_with_supported_fields(): array {

		return $this->filter_forms_with_supported_fields( $this->get_accessible_forms() );
	}

	/**
	 * Filter forms to only include those with supported fields for import.
	 *
	 * @since 1.10.1
	 *
	 * @param array $forms Array of form objects.
	 *
	 * @return array Filtered array of forms with supported fields.
	 */
	private function filter_forms_with_supported_fields( array $forms ): array {

		$supported_types = EntryImporter::get_supported_destination_fields();

		return array_filter(
			$forms,
			function ( $form ) use ( $supported_types ) {
				return $this->form_has_supported_fields( $form, $supported_types );
			}
		);
	}

	/**
	 * Check if a form has at least one supported field for import.
	 *
	 * @since 1.10.1
	 *
	 * @param object $form            Form object.
	 * @param array  $supported_types Array of supported field type slugs.
	 *
	 * @return bool True if form has supported fields.
	 */
	private function form_has_supported_fields( $form, array $supported_types ): bool {

		$form_data = wpforms_decode( $form->post_content );

		if ( empty( $form_data['fields'] ) ) {
			return false;
		}

		foreach ( $form_data['fields'] as $field ) {
			if ( ! empty( $field['type'] ) && in_array( $field['type'], $supported_types, true ) ) {
				return true;
			}
		}

		return false;
	}
}
