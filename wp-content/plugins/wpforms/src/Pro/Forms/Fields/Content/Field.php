<?php

namespace WPForms\Pro\Forms\Fields\Content;

use WPForms\Forms\Fields\Content\Field as FieldLite;

/**
 * The Content Field Class.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	/**
	 * Register WP hooks.
	 *
	 * @since 1.9.4
	 */
	protected function hooks() {

		add_filter( 'wpforms_entries_table_fields_disallow', [ $this, 'hide_column_in_entries_table' ] );
		add_filter( 'wpforms_pro_admin_entries_print_preview_field_value', [ $this, 'print_preview_field_value' ], 10, 2 );
		add_filter( 'wpforms_pro_admin_entries_print_preview_field_value_use_nl2br', [ $this, 'print_preview_use_nl2br' ], 10, 2 );
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );
		add_action( 'wpforms_frontend_css', [ $this, 'frontend_css' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_css' ] );
		add_action( 'wpforms_builder_enqueues', [ $this, 'builder_enqueues_pro' ] );
	}

	/**
	 * Display field on the front end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data.
	 * @param array $deprecated Field attributes.
	 * @param array $form_data  Form data.
	 *
	 * @return void
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		$this->content_input_display( $field );
	}

	/**
	 * Format field.
	 *
	 * Hides field on form submit preview.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
	}

	/**
	 * Hide column from the entry list table.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $disallowed Table columns.
	 *
	 * @return array
	 */
	public function hide_column_in_entries_table( $disallowed ): array {

		$disallowed   = (array) $disallowed;
		$disallowed[] = $this->type;

		return $disallowed;
	}

	/**
	 * Do caption shortcode for entry print preview and add clearing div.
	 *
	 * @since 1.9.4
	 *
	 * @param string|mixed $value Field value.
	 * @param array        $field Field data.
	 *
	 * @return string
	 */
	public function print_preview_field_value( $value, $field ): string {

		$value = (string) $value;

		if ( $field['type'] !== $this->type ) {
			return $value;
		}

		return wp_kses(
			sprintf(
				'%s<div class="wpforms-field-content-preview-end"></div>',
				$this->do_caption_shortcode( $value )
			),
			$this->get_allowed_html_tags()
		);
	}

	/**
	 * Do not use nl2br on content field's value.
	 *
	 * @since 1.9.4
	 *
	 * @param bool|mixed $use_nl2br Boolean value flagging if field should use the 'nl2br' function.
	 * @param array      $field     Field data.
	 *
	 * @return bool
	 */
	public function print_preview_use_nl2br( $use_nl2br, $field ): bool {

		$use_nl2br = (bool) $use_nl2br;

		return $field['type'] === $this->type ? false : $use_nl2br;
	}

	/**
	 * Conditionally enqueue frontend field CSS.
	 *
	 * Hook it into action wpforms_frontend_css if the field should be displayed and styled in the front end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the current page.
	 *
	 * @noinspection NotOptimalIfConditionsInspection
	 * @noinspection NullPointerExceptionInspection
	 */
	public function frontend_css( $forms ): void {
		/*
		 * If it is NOT set to enqueue CSS globally
		 * and form does not have a content field or for is not set to enqueue CSS,
		 * then bail out.
		 */
		if (
			! wpforms()->obj( 'frontend' )->assets_global()
			&& ( ! wpforms_has_field_type( $this->type, $forms, true ) || (int) wpforms_setting( 'disable-css', '1' ) !== 1 )
		) {
			return;
		}

		$this->enqueue_css();
	}

	/**
	 * Enqueue frontend field CSS.
	 *
	 * @since 1.9.4
	 */
	public function enqueue_css(): void {

		$min = wpforms_get_min_suffix();

		// Field styles based on the Form Styling setting.
		wp_enqueue_style(
			'wpforms-content-frontend',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/content/frontend{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Enqueue wpforms-content-field script.
	 *
	 * @since 1.9.5
	 *
	 * @param string $view Current view.
	 *
	 * @noinspection PhpUnusedParameterInspection, PhpUnnecessaryCurlyVarSyntaxInspection
	 */
	public function builder_enqueues_pro( string $view ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-content-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/content{$min}.js",
			[ 'wpforms-builder', 'editor', 'quicktags' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return false
	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return false
	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Show field display on the front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field Field data.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	private function content_input_display( $field ): void {

		if ( ! isset( $field['content'] ) ) {
			return;
		}

		$content = wp_kses( $this->do_caption_shortcode( wpautop( $field['content'] ) ), $this->get_allowed_html_tags() );

		// Disallow links to be clickable if form is displayed in Gutenberg block in edit context.
		if ( isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$content = str_replace( '<a ', '<a onclick="event.preventDefault()" ', $content );
		}

		// Define data.
		$primary                 = $field['properties']['inputs']['primary'];
		$primary['class'][]      = 'wpforms-field-row';
		$primary['attr']['name'] = '';

		printf(
			'<div %s>%s<div class="wpforms-field-content-display-frontend-clear"></div></div>',
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
