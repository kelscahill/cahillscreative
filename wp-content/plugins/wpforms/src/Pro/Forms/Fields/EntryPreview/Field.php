<?php

namespace WPForms\Pro\Forms\Fields\EntryPreview;

use WPForms\Forms\Fields\EntryPreview\Field as FieldLite;
use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;
use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;
use WPForms\Pro\Forms\Fields\Helpers as FieldsHelpers;
use WPForms_Builder_Panel_Settings;

/**
 * Entry preview field.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	/**
	 * Hooks.
	 *
	 * @since 1.9.4
	 */
	protected function hooks() {

		parent::hooks();

		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_styles' ] );
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_scripts' ] );
		add_action( 'wpforms_frontend_confirmation', [ $this, 'enqueue_styles' ] );
		add_action( 'wpforms_frontend_confirmation', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_wpforms_get_entry_preview', [ $this, 'ajax_get_entry_preview' ] );
		add_action( 'wp_ajax_nopriv_wpforms_get_entry_preview', [ $this, 'ajax_get_entry_preview' ] );
		add_action( 'wpforms_form_settings_confirmations_single_after', [ $this, 'add_confirmation_fields' ], 10, 2 );
		add_action( 'wpforms_frontend_confirmation_message_after', [ $this, 'entry_preview_confirmation' ], 10, 4 );
		add_filter( 'wpforms_frontend_form_data', [ $this, 'ignore_fields' ] );
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the page.
	 */
	public function enqueue_styles( $forms ): void {

		if ( (int) wpforms_setting( 'disable-css', '1' ) === 3 ) {
			return;
		}

		$forms = ! empty( $forms ) && is_array( $forms ) ? $forms : [];

		if ( ! $this->is_page_has_entry_preview( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-entry-preview',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/entry-preview{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the page.
	 */
	public function enqueue_scripts( $forms ): void {

		$forms = ! empty( $forms ) && is_array( $forms ) ? $forms : [];

		if ( ! $this->is_page_has_entry_preview( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-entry-preview',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/entry-preview{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION,
			$this->load_script_in_footer()
		);

		// Enqueue `wpforms-iframe` script.
		FieldsHelpers::enqueue_iframe_script();
	}

	/**
	 * The current page has entry preview confirmation or field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the page.
	 *
	 * @return bool
	 */
	private function is_page_has_entry_preview( $forms ): bool {

		if ( ! empty( wpforms()->obj( 'process' )->form_data ) && $this->is_form_has_entry_preview_confirmation( wpforms()->obj( 'process' )->form_data ) ) {
			return true;
		}

		foreach ( $forms as $form_data ) {
			if (
				$this->is_form_has_entry_preview_confirmation( $form_data )
				|| $this->is_form_has_entry_preview_field( $form_data )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The form has an entry preview confirmation.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool
	 */
	private function is_form_has_entry_preview_confirmation( $form_data ): bool {

		if ( empty( $form_data['settings']['confirmations'] ) ) {
			return false;
		}

		foreach ( $form_data['settings']['confirmations'] as $confirmation ) {
			if ( ! empty( $confirmation['message_entry_preview'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The form has an entry preview field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool
	 */
	private function is_form_has_entry_preview_field( $form_data ): bool {

		if ( empty( $form_data['fields'] ) ) {
			return false;
		}

		foreach ( $form_data['fields'] as $field ) {
			if ( ! empty( $field['type'] ) && $field['type'] === $this->type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ajax callback for getting entry preview.
	 *
	 * @since 1.9.4
	 */
	public function ajax_get_entry_preview(): void {

		$form_id = isset( $_POST['wpforms']['id'] ) ? absint( $_POST['wpforms']['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $form_id ) ) {
			wp_send_json_error();
		}

		if ( ! wpforms()->obj( 'form' ) ) {
			wp_send_json_error();
		}

		if (
			is_user_logged_in() &&
			(
				! isset( $_POST['wpforms']['nonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wpforms']['nonce'] ) ), 'wpforms::form_' . $form_id )
			)
		) {
			wp_send_json_error();
		}

		$submitted_fields = stripslashes_deep( $_POST['wpforms'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/**
		 * Allow modifying the form data before the entry preview is generated.
		 *
		 * @since 1.8.8
		 * @since 1.8.9 Added the `$fields` parameter.
		 *
		 * @param array $form_data Form data and settings.
		 * @param array $fields    Submitted fields.
		 *
		 * @return array
		 */
		$form_data = apply_filters( 'wpforms_entry_preview_form_data', wpforms()->obj( 'form' )->get( $form_id, [ 'content_only' => true ] ), $submitted_fields ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		if ( ! $form_data ) {
			wp_send_json_error();
		}

		$form_data['created']     = ! empty( $form_data['created'] ) ? $form_data['created'] : time();
		$current_entry_preview_id = ! empty( $_POST['current_entry_preview_id'] ) ? absint( $_POST['current_entry_preview_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$fields                   = $this->get_entry_preview_fields( $form_data, $submitted_fields, $current_entry_preview_id );

		if ( empty( $fields ) ) {
			wp_send_json_success();
		}

		$type = ! empty( $form_data['fields'][ $current_entry_preview_id ]['style'] ) ? $form_data['fields'][ $current_entry_preview_id ]['style'] : 'basic';

		ob_start();
		$this->print_ajax_entry_preview( $type, $fields, $form_data );
		wp_send_json_success( ob_get_clean() );
	}

	/**
	 * Get ID of the start position for search.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data              Form data and settings.
	 * @param int   $end_with_page_break_id Last page break field ID.
	 *
	 * @return int
	 */
	private function get_start_page_break_id( $form_data, $end_with_page_break_id ): int {

		$is_current_range   = false;
		$is_next_page_break = false;
		$first_field        = reset( $form_data['fields'] );
		$first_field_id     = wpforms_validate_field_id( $first_field['id'] );

		/**
		 * Force showing all fields from the beginning of the form instead of
		 * the fields between current and previous Entry Preview fields.
		 *
		 * @since 1.8.1
		 *
		 * @param bool  $force_all_fields       Whether to force all fields instead of a range between current and previous Entry Preview fields.
		 * @param array $form_data              Form data and settings.
		 * @param int   $end_with_page_break_id Last Page Break field ID.
		 */
		if ( apply_filters( 'wpforms_entry_preview_get_start_page_break_id_force_first', false, $form_data, $end_with_page_break_id ) ) { // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			return $first_field_id;
		}

		foreach ( array_reverse( (array) $form_data['fields'] ) as $field_properties ) {
			$field_id   = wpforms_validate_field_id( $field_properties['id'] );
			$field_type = $field_properties['type'];

			if ( $end_with_page_break_id === $field_id ) {
				$is_current_range = true;

				continue;
			}

			if ( $is_current_range && $field_type === $this->type ) {
				$is_next_page_break = true;

				continue;
			}

			if ( $is_current_range && $is_next_page_break && $field_type === 'pagebreak' ) {
				return $field_id;
			}
		}

		return $first_field_id;
	}

	/**
	 * Get ID of the end position for search.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data                Form data and settings.
	 * @param int   $current_entry_preview_id Current entry preview ID.
	 *
	 * @return int Field ID. -1 if not found.
	 */
	private function get_end_page_break_id( $form_data, $current_entry_preview_id ): int {

		$is_current_page = false;

		foreach ( array_reverse( (array) $form_data['fields'] ) as $field_properties ) {
			$field_id = wpforms_validate_field_id( $field_properties['id'] );

			if ( $current_entry_preview_id === $field_id ) {
				$is_current_page = true;

				continue;
			}

			if ( $is_current_page && $field_properties['type'] === 'pagebreak' ) {
				return $field_id;
			}
		}

		// Return -1 as the field ID can be 0 or any positive number.
		return -1;
	}

	/**
	 * Get fields that related to the current entry preview.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data                Form data and settings.
	 * @param array $submitted_fields         Submitted fields.
	 * @param int   $current_entry_preview_id Current entry preview ID.
	 *                                        `0` means return all the fields.
	 *
	 * @return array
	 */
	private function get_entry_preview_fields( $form_data, $submitted_fields, $current_entry_preview_id ): array {

		$end_with_page_break_id             = $this->get_end_page_break_id( $form_data, $current_entry_preview_id );
		$start_with_page_break_id           = $this->get_start_page_break_id( $form_data, $end_with_page_break_id );
		$is_current_range                   = $current_entry_preview_id === 0;
		$entry_preview_fields               = [];
		wpforms()->obj( 'process' )->fields = [];

		foreach ( (array) $form_data['fields'] as $field_properties ) {
			$field_id    = wpforms_validate_field_id( $field_properties['id'] );
			$field_type  = $field_properties['type'];
			$field_value = $submitted_fields['fields'][ $field_id ] ?? '';

			// We should process all submitted fields for correct Conditional Logic work.
			$this->process_field( $field_value, $field_properties, $form_data );

			if ( $field_id === $end_with_page_break_id ) {
				$is_current_range = false;
			}

			if ( $is_current_range && ! empty( wpforms()->obj( 'process' )->fields[ $field_id ] ) ) {
				$entry_preview_fields[ $field_id ] = wpforms()->obj( 'process' )->fields[ $field_id ];
			}

			if ( $field_type === 'pagebreak' && $field_id === $start_with_page_break_id ) {
				$is_current_range = true;
			}
		}

		$entry_preview_fields = $this->filter_conditional_logic( $entry_preview_fields, $form_data );

		/** This filter is documented in wpforms/includes/class-process.php */
		return apply_filters( 'wpforms_process_filter', $entry_preview_fields, $submitted_fields, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Process field for entry preview.
	 *
	 * @since 1.9.4
	 *
	 * @param string $field_value      Submitted field value.
	 * @param array  $field_properties Field properties.
	 * @param array  $form_data        Form data and settings.
	 */
	private function process_field( $field_value, $field_properties, $form_data ): void {

		$field_id   = wpforms_validate_field_id( $field_properties['id'] );
		$field_type = $field_properties['type'];

		if ( $this->is_field_support_preview( $field_value, $field_properties, $form_data ) ) {
			/**
			 * Apply things for format and sanitize, see WPForms_Field::format().
			 *
			 * @since 1.4.0
			 *
			 * @param int    $field       Field ID.
			 * @param string $field_value Submitted field value.
			 * @param array  $form_data   Form data and settings.
			 */
			do_action( "wpforms_process_format_{$field_type}", $field_id, $field_value, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

			return;
		}

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '',
			'value' => '',
			'id'    => $field_id,
			'type'  => $field_type,
		];
	}

	/**
	 * Mark fields hidden by CL as invisible.
	 *
	 * @since 1.9.4
	 *
	 * @param array $entry_preview_fields List of entry preview fields.
	 * @param array $form_data            Form data and settings.
	 *
	 * @return array
	 */
	private function filter_conditional_logic( $entry_preview_fields, $form_data ): array {

		foreach ( $entry_preview_fields as $field_id => $field ) {
			if ( wpforms_conditional_logic_fields()->field_is_hidden( $form_data, $field_id ) ) {
				$entry_preview_fields[ $field_id ]['visible'] = false;
			}
		}

		return $entry_preview_fields;
	}

	/**
	 * Show entry preview on the confirmation.
	 *
	 * @since 1.9.4
	 *
	 * @param array $confirmation Current confirmation data.
	 * @param array $form_data    Form data and settings.
	 * @param array $fields       Sanitized field data.
	 * @param int   $entry_id     Entry id.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function entry_preview_confirmation( $confirmation, $form_data, $fields, $entry_id ): void {

		if ( empty( $confirmation['message_entry_preview'] ) ) {
			return;
		}

		$type = ! empty( $confirmation['message_entry_preview_style'] ) ? $confirmation['message_entry_preview_style'] : 'basic';

		if ( empty( $fields ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_fields = ! empty( $_POST['wpforms'] ) ? stripslashes_deep( $_POST['wpforms'] ) : [];

		$entry_fields = $this->get_entry_preview_fields( $form_data, $submitted_fields, 0 );

		$this->print_entry_preview( $type, $entry_fields, $form_data );
	}

	/**
	 * Print entry preview.
	 *
	 * @since 1.9.4
	 *
	 * @param string $type         Entry preview type.
	 * @param array  $entry_fields Entry fields.
	 * @param array  $form_data    Form data and settings.
	 */
	private function print_entry_preview( string $type, array $entry_fields, array $form_data ): void {

		/**
		 * Modify the fields before the entry preview is printed.
		 *
		 * @since 1.8.9
		 *
		 * @param array $entry_fields Entry preview fields.
		 * @param array $form_data    Form data and settings.
		 *
		 * @return array
		 */
		$entry_fields = apply_filters( 'wpforms_entry_preview_fields', $entry_fields, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		$fields_html = '';

		foreach ( $entry_fields as $field ) {
			$fields_html .= $this->get_field( $field, $form_data );
		}

		if ( empty( $fields_html ) ) {
			return;
		}

		printf(
			'<div class="wpforms-entry-preview wpforms-entry-preview-%s">%s</div>',
			esc_attr( $type ),
			wp_kses_post( $fields_html )
		);
	}

	/**
	 * Print AJAX entry preview.
	 *
	 * @since 1.8.9
	 *
	 * @param string $type         Entry preview type.
	 * @param array  $entry_fields Entry fields.
	 * @param array  $form_data    Form data and settings.
	 */
	private function print_ajax_entry_preview( string $type, array $entry_fields, array $form_data ): void {

		/**
		 * Modify the fields before the entry preview is printed.
		 *
		 * @since 1.8.9
		 *
		 * @param array $entry_fields Entry preview fields.
		 * @param array $form_data    Form data and settings.
		 *
		 * @return array
		 */
		$entry_fields = apply_filters( 'wpforms_entry_preview_fields', $entry_fields, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		$fields_html = '';

		foreach ( $entry_fields as $field ) {
			$fields_html .= $this->get_field( $field, $form_data );
		}

		if ( empty( $fields_html ) ) {
			return;
		}

		printf(
			'<div class="wpforms-entry-preview wpforms-entry-preview-%s">%s</div>',
			esc_attr( $type ),
			wp_kses_post( $fields_html )
		);
	}

	/**
	 * Get field HTML.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data and settings.
	 *
	 * @return string
	 */
	private function get_field( array $field, array $form_data ): string {

		if ( $this->skip_field( $field, $form_data ) ) {
			return '';
		}

		if ( $field['type'] === 'repeater' ) {
			return $this->get_repeater_field( $field, $form_data );
		}

		if ( $field['type'] === 'layout' ) {
			return $this->get_layout_field( $field, $form_data );
		}

		$value = $this->get_field_value( $field, $form_data );

		if ( wpforms_is_empty_string( $value ) ) {
			return '';
		}

		/**
		 * Hide the field.
		 *
		 * @since 1.7.0
		 *
		 * @param bool  $hide      Hide the field.
		 * @param array $field     Field data.
		 * @param array $form_data Form data.
		 *
		 * @return bool
		 */
		if ( apply_filters( 'wpforms_pro_fields_entry_preview_print_entry_preview_exclude_field', false, $field, $form_data ) ) { // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			return '';
		}

		return sprintf(
			'<div class="wpforms-entry-preview-label">%1$s</div><div class="wpforms-entry-preview-value">%2$s</div>',
			esc_html( $this->get_field_label( $field, $form_data ) ),
			wp_kses_post( $value )
		);
	}

	/**
	 * Check if the field rendering should be skipped.
	 *
	 * @since 1.9.3
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	private function skip_field( array $field, array $form_data ): bool {

		$ignored_fields = self::get_ignored_fields();

		if ( in_array( $field['type'], $ignored_fields, true ) ) {
			return true;
		}

		if ( $this->is_hidden( $field, $form_data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the field is hidden.
	 *
	 * @since 1.9.3
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	private function is_hidden( array $field, array $form_data ): bool {

		$field_id = $field['id'] ?? null;

		if ( empty( $field_id ) ) {
			return false;
		}

		// Hidden by conditional logic.
		if ( isset( $field['visible'] ) && ! $field['visible'] ) {
			return true;
		}

		// Hidden by the field format.
		$field_data = $form_data['fields'][ $field_id ] ?? [];
		$format     = $field_data['format'] ?? '';

		return $format === 'hidden';
	}

	/**
	 * Display repeater field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return string
	 */
	private function get_repeater_field( array $field, array $form_data ): string {

		$form_data_reduced = RepeaterHelpers::get_previewable_form_data( $form_data, $field );
		$blocks            = RepeaterHelpers::get_blocks( $field, $form_data_reduced );

		ob_start();

		$display = $field['display'] ?? 'rows';

		?>
		<div class="wpforms-entry-preview-repeater wpforms-entry-preview-repeater-display-<?php echo esc_attr( $display ); ?>">
			<?php if ( $display === 'rows' ) : ?>
				<?php echo $this->get_repeater_divider( $field, $form_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php foreach ( $blocks as $key => $rows ) : ?>
				<?php
				if ( $display === 'blocks' ) {
					$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';

					echo $this->get_repeater_divider( $field, $form_data, $block_number ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<div class="wpforms-entry-preview-rows <?php echo $key === 0 ? 'wpforms-first-row' : ''; ?>">
					<?php foreach ( $rows as $row_data ) : ?>
						<div class="wpforms-entry-preview-row">
							<?php foreach ( $row_data as $column ) : ?>
								<div class="wpforms-entry-preview-column wpforms-entry-preview-column-<?php echo esc_attr( $column['width_preset'] ); ?>"><?php echo ! empty( $column['field'] ) ? $this->get_field( $column['field'], $form_data ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Prepare repeater divider HTML markup.
	 *
	 * @since 1.9.4
	 *
	 * @param array  $field        Field data.
	 * @param array  $form_data    Form data and settings.
	 * @param string $block_number Block number.
	 *
	 * @return string
	 */
	private function get_repeater_divider( array $field, array $form_data, string $block_number = '' ): string {

		$label = isset( $field['label'] ) ? $field['label'] . $block_number : '';

		if ( empty( $label ) || $this->is_field_label_hidden( $field, $form_data ) ) {
			return '';
		}

		return sprintf(
			'<div class="wpforms-entry-preview-label wpforms-entry-preview-label-repeater">%1$s</div>',
			esc_html( $label )
		);
	}

	/**
	 * Display layout field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return string
	 */
	private function get_layout_field( array $field, array $form_data ): string {

		$fields_content = isset( $form_data['fields'][ $field['id'] ]['display'] ) && $form_data['fields'][ $field['id'] ]['display'] === 'columns'
			? $this->get_layout_subfields_columns( $field, $form_data )
			: $this->get_layout_subfields_rows( $field, $form_data );

		if ( ! $fields_content ) {
			return '';
		}

		$divider = '';

		if ( ! $this->is_field_label_hidden( $field, $form_data ) ) {
			$label = wp_strip_all_tags( $field['label'] );

			$divider = sprintf(
				'<div class="wpforms-entry-preview-label wpforms-entry-preview-label-layout">%1$s</div>',
				esc_html( $label )
			);
		}

		return $divider . $fields_content;
	}

	/**
	 * Display column style layout subfields.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return string
	 */
	private function get_layout_subfields_columns( array $field, array $form_data ): string {

		if ( ! isset( $field['columns'] ) ) {
			return '';
		}

		ob_start();

		?>
		<div class="wpforms-entry-preview-layout  wpforms-entry-preview-layout-display-columns">
			<div class="wpforms-entry-preview-rows">
				<div class="wpforms-entry-preview-row">
					<?php foreach ( $field['columns'] as $column ) : ?>
						<div class="wpforms-entry-preview-column wpforms-entry-preview-column-<?php echo esc_attr( $column['width_preset'] ); ?>">
							<?php
							if ( ! empty( $column['fields'] ) ) {
								foreach ( $column['fields'] as $child_field ) {
									echo $this->get_field( $child_field, $form_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Display rows style layout subfields.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data.
	 *
	 * @return string
	 */
	private function get_layout_subfields_rows( array $field, array $form_data ): string {

		$rows = LayoutHelpers::get_row_data( $field );

		if ( empty( $rows ) ) {
			return '';
		}

		ob_start();

		?>
		<div class="wpforms-entry-preview-layout wpforms-entry-preview-layout-display-rows">
			<div class="wpforms-entry-preview-rows">
				<?php foreach ( $rows as $row ) : ?>
					<div class="wpforms-entry-preview-row">
						<?php foreach ( $row as $column ) : ?>
							<div class="wpforms-entry-preview-column wpforms-entry-preview-column-<?php echo esc_attr( $column['width_preset'] ); ?>">
								<?php
								if ( ! empty( $column['field'] ) ) {
									echo $this->get_field( $column['field'], $form_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get a list of ignored fields for the entry preview field.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	private static function get_ignored_fields(): array {

		$ignored_fields = [ 'hidden', 'captcha', 'pagebreak', 'entry-preview', 'divider', 'html' ];

		/**
		 * List of ignored fields for the entry preview field.
		 *
		 * @since 1.6.9
		 *
		 * @param array $fields List of ignored fields.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'wpforms_pro_fields_entry_preview_get_ignored_fields', $ignored_fields ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Get field label.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data and settings.
	 *
	 * @return string
	 */
	private function get_field_label( $field, $form_data ): string {

		if ( $this->is_field_label_hidden( $field, $form_data ) ) {
			return '';
		}

		$label = ! empty( $field['name'] )
			? wp_strip_all_tags( $field['name'] )
			: sprintf( /* translators: %d - field ID. */
				esc_html__( 'Field ID #%d', 'wpforms' ),
				wpforms_validate_field_id( $field['id'] )
			);

		/**
		 * Modify the field label inside the entry preview field.
		 *
		 * @since 1.6.9
		 *
		 * @param string $label     Label.
		 * @param array  $field     Field data.
		 * @param array  $form_data Form data.
		 *
		 * @return string
		 */
		return (string) apply_filters( 'wpforms_pro_fields_entry_preview_get_field_label', $label, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Get field value.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data and settings.
	 *
	 * @return string
	 */
	private function get_field_value( $field, $form_data ): string {

		$value = $field['value'] ?? '';
		$type  = $field['type'];

		/** This filter is documented in src/SmartTags/SmartTag/FieldHtmlId.php. */
		$value = (string) apply_filters( 'wpforms_html_field_value', wp_strip_all_tags( $value ), $field, $form_data, 'entry-preview' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		/**
		 * The field value inside for exact field type the entry preview field.
		 *
		 * @since 1.6.9
		 *
		 * @param string $value     Value.
		 * @param array  $field     Field data.
		 * @param array  $form_data Form data.
		 *
		 * @return string
		 */
		$value = (string) apply_filters( "wpforms_pro_fields_entry_preview_get_field_value_{$type}_field", $value, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		/**
		 * The field value inside the entry preview field.
		 *
		 * @since 1.6.9
		 *
		 * @param string $value     Value.
		 * @param array  $field     Field data.
		 * @param array  $form_data Form data.
		 *
		 * @return string
		 */
		$value = (string) apply_filters( 'wpforms_pro_fields_entry_preview_get_field_value', $value, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		if ( ! $this->is_field_support_preview( $value, $field, $form_data ) ) {
			/**
			 * Show fields that do not have an available preview.
			 *
			 * @since 1.7.0
			 *
			 * @param bool  $show      Show the field.
			 * @param array $field     Field data.
			 * @param array $form_data Form data.
			 *
			 * @return bool
			 */
			$show = (bool) apply_filters( 'wpforms_pro_fields_entry_preview_get_field_value_show_preview_not_available', true, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

			return $show ? sprintf( '<em>%s</em>', esc_html__( 'Preview not available', 'wpforms' ) ) : '';
		}

		if ( wpforms_is_empty_string( $value ) ) {
			/**
			 * Show fields with the empty value.
			 *
			 * @since 1.7.0
			 *
			 * @param bool  $show      Show the field.
			 * @param array $field     Field data.
			 * @param array $form_data Form data.
			 *
			 * @return bool
			 */
			$show = (bool) apply_filters( 'wpforms_pro_fields_entry_preview_get_field_value_show_empty', true, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

			return $show ? sprintf( '<em>%s</em>', esc_html__( 'Empty', 'wpforms' ) ) : '';
		}

		/**
		 * The field value inside the entry preview for an exact field type after all checks.
		 *
		 * @since 1.7.0
		 *
		 * @param string $value     Value.
		 * @param array  $field     Field data.
		 * @param array  $form_data Form data.
		 *
		 * @return string
		 */
		return (string) apply_filters( "wpforms_pro_fields_entry_preview_get_field_value_{$type}_field_after", $value, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Determine whether the field is available to show inside the entry preview field.
	 *
	 * @since 1.9.4
	 *
	 * @param string $value     Value.
	 * @param array  $field     Processed field data.
	 * @param array  $form_data Form data.
	 *
	 * @return bool
	 */
	private function is_field_support_preview( $value, $field, $form_data ): bool {

		$field_type = $field['type'];

		// Compatibility with Authorize.Net and Stripe addons.
		if ( wpforms_is_empty_string( $value ) && in_array( $field_type, [ 'stripe-credit-card', 'authorize_net' ], true ) ) {
			return false;
		}

		/**
		 * The field availability inside the entry preview field.
		 *
		 * @since 1.6.9
		 *
		 * @param bool   $is_supported The field availability.
		 * @param string $value        Value.
		 * @param array  $field        Field data.
		 * @param array  $form_data    Form data.
		 *
		 * @return bool
		 */
		$is_supported = (bool) apply_filters( // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			"wpforms_pro_fields_entry_preview_is_field_support_preview_{$field_type}_field",
			true,
			$value,
			$field,
			$form_data
		);

		/**
		 * Fields availability inside the entry preview field.
		 * Actually, it can control availability for all field types.
		 *
		 * @since 1.6.9
		 *
		 * @param bool   $is_supported Fields availability.
		 * @param string $value        Value.
		 * @param array  $field        Field data.
		 * @param array  $form_data    Form data.
		 *
		 * @return bool
		 */
		return (bool) apply_filters( 'wpforms_pro_fields_entry_preview_is_field_support_preview', $is_supported, $value, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Whether the current field can be populated using a fallback.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
	}

	/**
	 * Display the field input elements on the frontend.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Field attributes.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		echo '<div class="wpforms-entry-preview-updating-message">' . esc_html__( 'Updating preview…', 'wpforms' ) . '</div>';

		if ( ! empty( $field['preview-notice-enable'] ) ) {
			$notice = ! empty( $field['preview-notice'] ) ? $field['preview-notice'] : self::get_default_notice();

			printf(
				'<div class="wpforms-entry-preview-notice" style="display: none;">%1$s</div>',
				wp_kses_post( nl2br( $notice ) )
			);
		}

		echo '<div class="wpforms-entry-preview-wrapper" style="display: none;"></div>';
	}

	/**
	 * Add a custom JS i18n strings for the builder.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $strings List of strings.
	 * @param array       $form    Current form.
	 *
	 * @return array
	 */
	public function add_builder_strings( $strings, $form ): array {

		$strings = (array) $strings;

		$strings['entry_preview_require_page_break']      = esc_html__( 'Page breaks are required for entry previews to work. If you\'d like to remove page breaks, you\'ll have to first remove the entry preview field.', 'wpforms' );
		$strings['entry_preview_default_notice']          = self::get_default_notice();
		$strings['entry_preview_require_previous_button'] = esc_html__( 'You can\'t hide the previous button because it is required for the entry preview field on this page.', 'wpforms' );

		return $strings;
	}

	/**
	 * Add fields to the confirmation settings.
	 *
	 * @since 1.9.4
	 *
	 * @param WPForms_Builder_Panel_Settings $settings Settings.
	 * @param int                            $field_id Field ID.
	 */
	public function add_confirmation_fields( $settings, $field_id ): void {

		wpforms_panel_field(
			'toggle',
			'confirmations',
			'message_entry_preview',
			$settings->form_data,
			esc_html__( 'Show entry preview after confirmation message', 'wpforms' ),
			[
				'input_id'    => 'wpforms-panel-field-confirmations-message_entry_preview-' . $field_id,
				'input_class' => 'wpforms-panel-field-confirmations-message_entry_preview',
				'parent'      => 'settings',
				'subsection'  => $field_id,
			]
		);

		wpforms_panel_field(
			'select',
			'confirmations',
			'message_entry_preview_style',
			$settings->form_data,
			esc_html__( 'Preview Style', 'wpforms' ),
			[
				'input_id'    => 'wpforms-panel-field-confirmations-message_entry_preview_style-' . $field_id,
				'input_class' => 'wpforms-panel-field-confirmations-message_entry_preview_style',
				'parent'      => 'settings',
				'subsection'  => $field_id,
				'default'     => 'basic',
				'options'     => self::get_styles(),
			]
		);
	}

	/**
	 * Ignore entry preview fields for some forms.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $form_data Form data and settings.
	 *
	 * @return array
	 */
	public function ignore_fields( $form_data ): array {

		$form_data = (array) $form_data;

		if ( ! $this->is_fields_ignored( $form_data ) ) {
			return $form_data;
		}

		if ( empty( $form_data['fields'] ) ) {
			return $form_data;
		}

		foreach ( $form_data['fields'] as $key => $field ) {
			if ( $field['type'] === $this->type ) {
				unset( $form_data['fields'][ $key ] );
			}
		}

		return $form_data;
	}

	/**
	 * Allow ignoring entry preview fields for some forms.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool
	 */
	private function is_fields_ignored( $form_data ): bool {

		/**
		 * Allow ignoring entry preview fields for some forms.
		 *
		 * @since 1.6.9
		 *
		 * @param bool  $is_ignore Ignore the entry preview fields.
		 * @param array $form_data Form data and settings.
		 *
		 * @return bool
		 */
		return (bool) apply_filters( 'wpforms_pro_fields_entry_preview_is_fields_ignored', false, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Determine whether the field label is hidden.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	private function is_field_label_hidden( $field, $form_data ): bool {

		return ! empty( $form_data['fields'][ $field['id'] ]['label_hide'] );
	}
}
