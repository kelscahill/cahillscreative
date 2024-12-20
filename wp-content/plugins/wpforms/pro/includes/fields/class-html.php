<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection AutoloadingIssuesInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML block text field.
 *
 * @since 1.0.0
 */
class WPForms_Field_HTML extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'HTML', 'wpforms' );
		$this->keywords = esc_html__( 'code', 'wpforms' );
		$this->type     = 'html';
		$this->icon     = 'fa-code';
		$this->order    = 185;
		$this->group    = 'fancy';

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.7.1
	 */
	private function hooks() {

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_html', [ $this, 'field_properties' ], 5, 3 );
		add_filter( 'wpforms_field_new_default', [ $this, 'field_new_default' ] );
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );
		add_filter( 'wpforms_pro_admin_entries_print_preview_field_value_use_nl2br', [ $this, 'print_preview_use_nl2br' ], 10, 2 );
	}

	/**
	 * Define new field default.
	 *
	 * @since 1.5.7
	 *
	 * @param array|mixed $field Field settings.
	 *
	 * @return array Field settings.
	 */
	public function field_new_default( $field ): array {

		$field         = (array) $field;
		$field['name'] = '';

		return $field;
	}

	/**
	 * Do not use nl2br on html field's value.
	 *
	 * @since 1.9.2
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
	 * Define additional field properties.
	 *
	 * @since        1.3.7
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_properties( $properties, $field, $form_data ): array {

		$properties = (array) $properties;

		// Remove input attributes references.
		$properties['inputs']['primary']['attr'] = [];

		// Add code value.
		$properties['inputs']['primary']['code'] = ! empty( $field['code'] ) ? $field['code'] : '';

		return $properties;
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.5.1
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 *
	 * @return bool
 	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Whether the current field can be populated using a fallback.
	 *
	 * @since 1.5.1
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 *
	 * @return bool
 	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Extend from `parent::field_option()` to add `name` option.
	 *
	 * @since 1.5.7
	 *
	 * @param string $option Field option to render.
	 * @param array  $field  Field data and settings.
	 * @param array  $args   Field preview arguments.
	 * @param bool   $echo   Print or return the value. Print by default.
	 *
	 * @return string|null
	 */
	public function field_option( $option, $field, $args = [], $echo = true ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound

		if ( $option !== 'name' ) {
			return parent::field_option( $option, $field, $args, $echo );
		}

		$output  = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'name',
				'value'   => esc_html__( 'Label', 'wpforms' ),
				'tooltip' => esc_html__( 'Enter text for the form field label. It will help identify your HTML blocks inside the form builder, but will not be displayed in the form.', 'wpforms' ),
			],
			false
		);
		$output .= $this->field_element(
			'text',
			$field,
			[
				'slug'  => 'name',
				'value' => ! empty( $field['name'] ) ? esc_attr( $field['name'] ) : '',
			],
			false
		);
		$output  = $this->field_element(
			'row',
			$field,
			[
				'slug'    => 'name',
				'content' => $output,
			],
			false
		);

		if ( $echo ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return null;
		}

		return $output;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {
		/*
		 * Basic field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'basic-options', $field, $args );

		// Name (Label).
		$this->field_option( 'name', $field );

		// Code.
		$this->field_option( 'code', $field );

		// Set label to disabled.
		$args = [
			'type'  => 'hidden',
			'slug'  => 'label_disable',
			'value' => '1',
		];

		$this->field_element( 'text', $field, $args );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'basic-options', $field, $args );

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'advanced-options', $field, $args );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'advanced-options', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {

		$label = ! empty( $field['name'] ) ? $field['name'] : '';
		?>

		<label class="label-title">
			<div class="text"><?php echo esc_html( $label ); ?></div>
			<div class="grey"><i class="fa fa-code"></i> <?php esc_html_e( 'HTML / Code Block', 'wpforms' ); ?></div>
		</label>
		<div class="description"><?php esc_html_e( 'Contents of this field are not displayed in the form builder preview.', 'wpforms' ); ?></div>

		<?php
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<div %s>%s</div>',
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			do_shortcode( force_balance_tags( $primary['code'] ) )
		);
	}

	/**
	 * Format field.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
	}
}

new WPForms_Field_HTML();
