<?php

namespace WPForms\Pro\Forms\Fields\DateTime;

use WPForms\Forms\Fields\DateTime\Field as FieldLite;

/**
 * Date / Time field.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	/**
	 * Hooks.
	 *
	 * @since 1.9.4
	 */
	protected function hooks(): void {

		parent::hooks();

		// Define additional field properties.
		add_filter( "wpforms_field_properties_{$this->type}", [ $this, 'field_properties' ], 5, 3 );
		add_filter( 'wpforms_field_display_sublabel_skip_for', [ $this, 'skip_sublabel_for_attribute' ], 10, 3 );
		add_filter( 'wpforms_smart_tags_formatted_field_value', [ $this, 'smart_tags_formatted_field_value' ], 7, 5 );
	}

	/**
	 * Get field data for the field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Current field.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array
	 *
	 * @override Intentionally returns unmodified field data to prevent unwanted data transformation from the base field class.
	 */
	public function field_data( $field, $form_data ): array {

		$field = (array) $field;

		// If the field type is not the same as the current field, return the result of the base class method.
		if ( ! isset( $field['type'] ) || $field['type'] !== $this->type ) {
			return (array) parent::field_data( $field, $form_data );
		}

		return $field;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$properties = (array) $properties;

		/**
		 * Whether the date/time limits are available.
		 *
		 * @since 1.6.3.1
		 *
		 * @param bool $limits_available Whether to apply date/time limits.
		 */
		$limits_available = (bool) apply_filters( 'wpforms_datetime_limits_available', true ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Define data.
		$form_id        = absint( $form_data['id'] );
		$field_id       = wpforms_validate_field_id( $field['id'] );
		$field_format   = ! empty( $field['format'] ) ? $field['format'] : self::DEFAULTS['format'];
		$field_required = ! empty( $field['required'] ) ? 'required' : '';
		$field_size_cls = 'wpforms-field-' . ( ! empty( $field['size'] ) ? $field['size'] : 'medium' );

		$date_format      = ! empty( $field['date_format'] ) ? $field['date_format'] : $this->default_settings['date_format'];
		$date_placeholder = ! empty( $field['date_placeholder'] ) ? $field['date_placeholder'] : $this->default_settings['date_placeholder'];
		$date_type        = ! empty( $field['date_type'] ) ? $field['date_type'] : $this->default_settings['date_type'];

		$time_placeholder = ! empty( $field['time_placeholder'] ) ? $field['time_placeholder'] : $this->default_settings['time_placeholder'];
		$time_format      = ! empty( $field['time_format'] ) ? $field['time_format'] : $this->default_settings['time_format'];
		$time_interval    = ! empty( $field['time_interval'] ) ? $field['time_interval'] : $this->default_settings['time_interval'];

		// Backwards compatibility with old datepicker format.
		if ( $date_format === 'mm/dd/yyyy' ) {
			$date_format = self::DEFAULTS['date_format'];
		} elseif ( $date_format === 'dd/mm/yyyy' ) {
			$date_format = self::ALT_DATE_FORMAT;
		} elseif ( $date_format === 'mmmm d, yyyy' ) {
			$date_format = 'F j, Y';
		}

		$default_date = [
			'container' => [
				'attr'  => [],
				'class' => [
					'wpforms-field-row-block',
					"wpforms-date-type-{$date_type}",
				],
				'data'  => [],
				'id'    => '',
			],
			'attr'      => [
				'name'        => "wpforms[fields][{$field_id}][date]",
				'value'       => '',
				'placeholder' => $date_placeholder,
			],
			'sublabel'  => [
				'hidden' => ! empty( $field['sublabel_hide'] ),
				'value'  => esc_html__( 'Date', 'wpforms' ),
			],
			'class'     => [
				'wpforms-field-date-time-date',
				'wpforms-datepicker',
				! empty( $field_required ) ? 'wpforms-field-required' : '',
				! empty( wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['date'] ) ? 'wpforms-error' : '',
			],
			'data'      => [
				'date-format' => $date_format,
			],
			'id'        => "wpforms-{$form_id}-field_{$field_id}",
			'required'  => $field_required,
		];

		// Limit Days.
		if ( $limits_available && ! empty( $field['date_limit_days'] ) && $date_type === 'datepicker' ) {
			$days       = [ 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ];
			$limit_days = [];

			foreach ( $days as $day ) {
				if ( ! empty( $field[ 'date_limit_days_' . $day ] ) ) {
					$limit_days[] = $day;
				}
			}
			$default_date['data']['limit-days'] = implode( ',', $limit_days );
		}

		if ( $limits_available && $date_type === 'datepicker' ) {
			$limit_past_days                            = ! empty( $field['date_disable_past_dates'] ) ? '1' : '0';
			$default_date['data']['disable-past-dates'] = $limit_past_days;

			if ( $limit_past_days ) {
				$default_date['data']['disable-todays-date'] = ! empty( $field['date_disable_todays_date'] ) ? '1' : '0';
			}
		}

		$default_time = [
			'container' => [
				'attr'  => [],
				'class' => [
					'wpforms-field-row-block',
				],
				'data'  => [],
				'id'    => '',
			],
			'attr'      => [
				'name'        => "wpforms[fields][{$field_id}][time]",
				'value'       => '',
				'placeholder' => $time_placeholder,
			],
			'sublabel'  => [
				'hidden' => ! empty( $field['sublabel_hide'] ),
				'value'  => esc_html__( 'Time', 'wpforms' ),
			],
			'class'     => [
				'wpforms-field-date-time-time',
				'wpforms-timepicker',
				! empty( $field_required ) ? 'wpforms-field-required' : '',
				! empty( wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['time'] ) ? 'wpforms-error' : '',
			],
			'data'      => [
				'time-format' => $time_format,
				'step'        => $time_interval,
			],
			'id'        => "wpforms-{$form_id}-field_{$field_id}-time",
			'required'  => $field_required,
		];

		// Determine time format validation rule only for default (embedded) time formats.
		if ( in_array( $time_format, [ 'H:i', 'H:i A' ], true ) ) {
			$default_time['data']['rule-time24h'] = 'true';
		} elseif ( $time_format === self::DEFAULTS['time_format'] ) {
			$default_time['data']['rule-time12h'] = 'true';
		}

		if ( ! empty( $field['time_limit_hours'] ) && $limits_available ) {
			$default_time['data']['min-time']  = ! empty( $field['time_limit_hours_start_hour'] ) ? $field['time_limit_hours_start_hour'] : $this->default_settings['time_limit_hours_start_hour'];
			$default_time['data']['min-time'] .= ':';
			$default_time['data']['min-time'] .= ! empty( $field['time_limit_hours_start_min'] ) ? $field['time_limit_hours_start_min'] : $this->default_settings['time_limit_hours_start_min'];

			$default_time['data']['max-time']  = ! empty( $field['time_limit_hours_end_hour'] ) ? $field['time_limit_hours_end_hour'] : $this->default_settings['time_limit_hours_end_hour'];
			$default_time['data']['max-time'] .= ':';
			$default_time['data']['max-time'] .= ! empty( $field['time_limit_hours_end_min'] ) ? $field['time_limit_hours_end_min'] : $this->default_settings['time_limit_hours_end_min'];

			// If the format contains `g` or `h`, then this is 12-hour format.
			if ( preg_match( '/[gh]/', $time_format ) ) {
				$default_time['data']['min-time'] .= ! empty( $field['time_limit_hours_start_ampm'] ) ? $field['time_limit_hours_start_ampm'] : $this->default_settings['time_limit_hours_start_ampm'];
				$default_time['data']['max-time'] .= ! empty( $field['time_limit_hours_end_ampm'] ) ? $field['time_limit_hours_end_ampm'] : $this->default_settings['time_limit_hours_end_ampm'];
			}

			// Limit Hours validation should apply only for defaulted (embedded) time formats.
			if ( in_array( $time_format, [ self::DEFAULTS['time_format'], 'H:i' ], true ) ) {
				$default_time['data']['rule-time-limit'] = 'true';
			}
		}

		switch ( $field_format ) {
			case 'date-time':
				$properties['input_container'] = [
					'id'    => '',
					'class' => [
						'wpforms-field-row',
						$field_size_cls,
					],
					'data'  => [],
					'attr'  => [],
				];

				$properties['inputs']['date'] = $default_date;
				$properties['inputs']['time'] = $default_time;
				break;

			case 'date':
				$properties['inputs']['date']            = $default_date;
				$properties['inputs']['date']['class'][] = $field_size_cls;
				break;

			case 'time':
				$properties['inputs']['time']            = $default_time;
				$properties['inputs']['time']['class'][] = $field_size_cls;
				$properties['label']['attr']['for']     .= '-time';
				break;
		}

		if ( $date_type === 'dropdown' ) {
			$properties['inputs']['date']['dropdown_wrap'] = [
				'attr'  => [],
				'class' => [
					'wpforms-field-date-dropdown-wrap',
					$field_size_cls,
				],
				'data'  => [],
				'id'    => '',
			];
		}

		// Remove reference to an input element ...
		if (
			// ... as there is no single id for it.
			( $date_type === 'dropdown' && $field_format !== 'time' ) ||
			// ... to prevent duplication.
			( $date_type === 'datepicker' && $field_format === self::DEFAULTS['format'] && empty( $field['sublabel_hide'] ) )
		) {
			unset( $properties['label']['attr']['for'] );
		}

		return $properties;
	}

	/**
	 * Get the value, that is used to prefill via dynamic or fallback population.
	 * Based on field data and current properties.
	 *
	 * @since 1.5.1
	 *
	 * @param string $raw_value  Value from a GET param, always a string.
	 * @param string $input      Represent a subfield inside the field. Can be empty.
	 * @param array  $properties Field properties.
	 * @param array  $field      Current field specific data.
	 *
	 * @return array Modified field properties.
	 */
	protected function get_field_populated_single_property_value( $raw_value, $input, $properties, $field ): array {

		$properties   = parent::get_field_populated_single_property_value( $raw_value, $input, $properties, $field );
		$date_type    = ! empty( $field['date_type'] ) ? $field['date_type'] : 'datepicker';
		$field_format = ! empty( $field['format'] ) ? $field['format'] : self::DEFAULTS['format'];

		// Ordinary date/time fields, without a dropdown, were already processed by this time.
		if ( $field_format === 'time' || $date_type !== 'dropdown' ) {
			return $properties;
		}

		$subinput = explode( '_', $input );

		// Only date subfield supports this extra logic.
		if (
			empty( $subinput ) ||
			$subinput[0] !== 'date' ||
			empty( $subinput[1] )
		) {
			return $properties;
		}

		$properties['inputs']['date']['default'][ sanitize_key( $subinput[1] ) ] = (int) $raw_value;

		return $properties;
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated array of field attributes.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$form_id    = $form_data['id'];
		$properties = $field['properties'];
		$container  = $properties['input_container'] ?? [];
		$date_prop  = $field['properties']['inputs']['date'] ?? [];
		$time_prop  = $field['properties']['inputs']['time'] ?? [];

		$date_prop['data']                = $date_prop['data'] ?? [];
		$date_prop['data']['date-format'] = $date_prop['data']['date-format'] ?? $this->default_settings['date_format'];

		/**
		 * Filter the date format for the DateTime field.
		 *
		 * @since 1.5.9
		 *
		 * @param string $date_format Date format.
		 * @param array  $form_data   Form data.
		 * @param array  $field       Field data.
		 */
		$date_prop['data']['date-format'] = apply_filters( 'wpforms_datetime_date_format', $date_prop['data']['date-format'], $form_data, $field ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		$date_prop['data']['input']       = 'true';

		$time_prop['data']         = $time_prop['data'] ?? [];
		$time_prop['data']['step'] = $time_prop['data']['step'] ?? $this->default_settings['time_interval'];

		/**
		 * Filter the time interval for the DateTime field.
		 *
		 * @since 1.5.9
		 *
		 * @param string $time_interval Time interval.
		 * @param array  $form_data     Form data.
		 * @param array  $field         Field data.
		 */
		$time_prop['data']['step']        = apply_filters( 'wpforms_datetime_time_interval', $time_prop['data']['step'], $form_data, $field ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		$time_prop['data']['time-format'] = $time_prop['data']['time-format'] ?? $this->default_settings['time_format'];

		/**
		 * Filter the time format for the DateTime field.
		 *
		 * @since 1.5.9
		 *
		 * @param string $time_format Time format.
		 * @param array  $form_data   Form data.
		 * @param array  $field       Field data.
		 */
		$time_prop['data']['time-format'] = apply_filters( 'wpforms_datetime_time_format', $time_prop['data']['time-format'], $form_data, $field ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		$time_prop['attr']['value']       = ! empty( $time_prop['attr']['value'] ) ? date( $time_prop['data']['time-format'], strtotime( $time_prop['attr']['value'] ) ) : ''; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$field_required = ! empty( $field['required'] ) ? ' required' : '';
		$field_format   = ! empty( $field['format'] ) ? $field['format'] : self::DEFAULTS['format'];

		$date_format = ! empty( $field['date_format'] ) ? $field['date_format'] : self::DEFAULTS['date_format'];
		$date_type   = ! empty( $field['date_type'] ) ? esc_attr( $field['date_type'] ) : 'datepicker';

		switch ( $field_format ) {
			case 'date-time':
				printf(
					'<div %s>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
				);

				printf(
					'<div %s>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $date_prop['container']['id'], $date_prop['container']['class'], $date_prop['container']['data'], $date_prop['container']['attr'] )
				);

				$this->field_display_sublabel( 'date', 'before', $field );
				$this->field_display_date_inputs( $date_type, $date_format, $field, $field_required, $form_id, $date_prop );
				$this->field_display_error( 'date', $field );
				$this->field_display_sublabel( 'date', 'after', $field );

				echo '</div>';

				printf(
					'<div %s>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $time_prop['container']['id'], $time_prop['container']['class'], $time_prop['container']['data'], $time_prop['container']['attr'] )
				);

				$this->field_display_sublabel( 'time', 'before', $field );

				printf(
					'<input type="text" %s %s>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $time_prop['id'], $time_prop['class'], $time_prop['data'], $time_prop['attr'] ),
					! empty( $time_prop['required'] ) ? 'required' : ''
				);

				$this->field_display_error( 'time', $field );
				$this->field_display_sublabel( 'time', 'after', $field );

				echo '</div>';

				echo '</div>';
				break;

			case 'date':
				$this->field_display_date_inputs( $date_type, $date_format, $field, $field_required, $form_id, $date_prop );
				break;

			case 'time':
			default:
				printf(
					'<input type="text" %s %s>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $time_prop['id'], $time_prop['class'], $time_prop['data'], $time_prop['attr'] ),
					! empty( $time_prop['required'] ) ? 'required' : ''
				);
				$this->field_display_error( 'time', $field );
				break;
		}
	}

	/**
	 * Display the date inputs.
	 *
	 * @since 1.9.4
	 *
	 * @param string $date_type      Date type: `datepicker` or `dropdown`.
	 * @param string $date_format    Date format.
	 * @param array  $field          Field data and settings.
	 * @param string $field_required Whether this field required or not, has an HTML attribute or empty.
	 * @param int    $form_id        Form ID.
	 * @param array  $date_prop      Date properties.
	 *
	 * @return void
	 * @noinspection HtmlUnknownAttribute
	 */
	private function field_display_date_inputs( string $date_type, $date_format, array $field, string $field_required, $form_id, $date_prop ): void {

		if ( $date_type === 'dropdown' ) {

			$this->field_display_date_dropdowns( $date_format, $field, $field_required, $form_id );

			return;
		}

		printf(
			'<div class="wpforms-datepicker-wrap"><input type="text" %1$s %2$s><a title="%3$s" data-clear role="button" tabindex="0" class="wpforms-datepicker-clear" aria-label="%3$s" style="display:%4$s;"></a></div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $date_prop['id'], $date_prop['class'], $date_prop['data'], $date_prop['attr'] ),
			esc_attr( $date_prop['required'] ),
			esc_attr__( 'Clear Date', 'wpforms' ),
			empty( $date_prop['attr']['value'] ) ? 'none' : 'block'
		);
	}

	/**
	 * Do not add the `for` attribute to certain sublabels.
	 *
	 * @since 1.9.4
	 *
	 * @param bool|mixed $skip  Whether to skip the `for` attribute.
	 * @param string     $key   Input key.
	 * @param array      $field Field data and settings.
	 *
	 * @return bool
	 */
	public function skip_sublabel_for_attribute( $skip, $key, $field ): bool {

		$skip = (bool) $skip;

		if ( $field['type'] !== $this->type ) {
			return $skip;
		}

		$date_type = $field['date_type'] ?? $this->default_settings['date_type'];

		if ( $key === 'date' && $date_type === 'dropdown' ) {
			return true;
		}

		return $skip;
	}

	/**
	 * Display the date field using dropdowns.
	 *
	 * @since 1.9.4
	 *
	 * @param string $format         Field format.
	 * @param array  $field          Field data and settings.
	 * @param string $field_required Whether this field required or not, has an HTML attribute or empty.
	 * @param int    $form_id        Form ID.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display_date_dropdowns( $format, $field, $field_required, $form_id ): void {

		$format = ! empty( $format ) ? esc_attr( $format ) : self::DEFAULTS['date_format'];

		// Backwards compatibility with old datepicker format.
		if ( $format === 'mm/dd/yyyy' ) {
			$format = self::DEFAULTS['date_format'];
		} elseif ( $format === 'dd/mm/yyyy' ) {
			$format = self::ALT_DATE_FORMAT;
		} elseif ( $format === 'mmmm d, yyyy' ) {
			$format = 'F j, Y';
		}

		// phpcs:disable WPForms.Comments.ParamTagHooks.InvalidAlign

		/**
		 * Filter DateTime field Date dropdowns ranges data.
		 *
		 * @since 1.4.4
		 *
		 * @param array $ranges {
		 *      Date dropdowns ranges data.
		 *
		 *      @type array  $months       Months.
		 *      @type array  $days         Days.
		 *      @type array  $years        Years.
		 *      @type string $months_label Months label.
		 *      @type string $days_label   Days label.
		 *      @type string $years_label  Years label.
		 * }
		 * @param integer $form_id Form ID.
		 * @param array   $field   Field data.
		 */
		$ranges = apply_filters( // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			'wpforms_datetime_date_dropdowns',
			[
				'months'       => range( 1, 12 ),
				'days'         => range( 1, 31 ),
				'years'        => range( date( 'Y' ) + 1, 1920 ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'months_label' => esc_html__( 'MM', 'wpforms' ),
				'days_label'   => esc_html__( 'DD', 'wpforms' ),
				'years_label'  => esc_html__( 'YYYY', 'wpforms' ),
			],
			$form_id,
			$field
		);
		// phpcs:enable WPForms.Comments.ParamTagHooks.InvalidAlign

		$properties = $field['properties'];
		$wrap       = $properties['inputs']['date']['dropdown_wrap'] ?? [];

		printf(
			'<div %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $wrap['id'], $wrap['class'], $wrap['data'], $wrap['attr'] )
		);

		if ( $format === self::DEFAULTS['date_format'] ) {
			$this->field_display_date_dropdown_element( 'month', $ranges['months_label'], $ranges['months'], $field, $field_required, $form_id );
			$this->field_display_date_dropdown_element( 'day', $ranges['days_label'], $ranges['days'], $field, $field_required, $form_id );
		} else {
			$this->field_display_date_dropdown_element( 'day', $ranges['days_label'], $ranges['days'], $field, $field_required, $form_id );
			$this->field_display_date_dropdown_element( 'month', $ranges['months_label'], $ranges['months'], $field, $field_required, $form_id );
		}

		$this->field_display_date_dropdown_element( 'year', $ranges['years_label'], $ranges['years'], $field, $field_required, $form_id );

		echo '</div>';
	}

	/**
	 * Display the Date Dropdown element.
	 *
	 * @since 1.9.4
	 *
	 * @param string $element        Date element: `day`, `month` or `year`.
	 * @param string $label          Field label.
	 * @param array  $numbers        Numbers range.
	 * @param array  $field          Field data and settings.
	 * @param string $field_required Whether this field required or not, has HTML attribute or empty.
	 * @param int    $form_id        Form ID.
	 */
	private function field_display_date_dropdown_element( $element, $label, $numbers, $field, $field_required, $form_id ): void {

		$defaults   = ! empty( $field['properties']['inputs']['date']['default'] ) && is_array( $field['properties']['inputs']['date']['default'] ) ? $field['properties']['inputs']['date']['default'] : [];
		$short      = $element[0];
		$current    = ! empty( $defaults[ $short ] ) ? (int) $defaults[ $short ] : 0;
		$properties = $field['properties']['inputs']['date'][ $short ] ?? [];

		$atts = $this->get_date_dropdown_element_atts( $element, $form_id, $properties, $field_required, $field );

		$this->frontend_obj->display_date_dropdown_element( $label, $short, $numbers, $current, $atts, $field_required, $field );
	}

	/**
	 * Get the Date Dropdown element attributes.
	 *
	 * @since 1.9.4
	 *
	 * @param string $element        Date element: `day`, `month` or `year`.
	 * @param int    $form_id        Form ID.
	 * @param array  $properties     Field element properties.
	 * @param string $field_required Whether this field required or not, has an HTML attribute or empty.
	 * @param array  $field          Field data and settings.
	 *
	 * @return array
	 */
	private function get_date_dropdown_element_atts( $element, $form_id, $properties, $field_required, $field ): array {

		$atts = [];

		$atts['id'] = "wpforms-{$form_id}-field_{$field['id']}-{$element}";

		$atts['class']   = $properties['class'] ?? [];
		$atts['class'][] = 'wpforms-field-date-time-date-' . $element;
		$atts['class'][] = ! empty( $field_required ) ? 'wpforms-field-required' : '';
		$atts['class'][] = ! empty( wpforms()->obj( 'process' )->errors[ $form_id ][ $field['id'] ]['date'] ) ? 'wpforms-error' : '';

		$atts['data'] = $properties['data'] ?? [];
		$atts['attr'] = $properties['attr'] ?? [];

		return $atts;
	}

	/**
	 * Validate field on form submission.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$this->validate_time_limit( $field_id, $field_submit, $form_data );

		if ( empty( $form_data['fields'][ $field_id ]['required'] ) ) {
			return;
		}

		// Extended validation needed for the different address fields.
		$form_id  = $form_data['id'];
		$format   = $form_data['fields'][ $field_id ]['format'];
		$required = wpforms_get_required_label();

		$is_date_format = $format === 'date' || $format === self::DEFAULTS['format'];
		$is_time_format = $format === 'time' || $format === self::DEFAULTS['format'];

		if (
			! empty( $form_data['fields'][ $field_id ]['date_type'] ) &&
			$form_data['fields'][ $field_id ]['date_type'] === 'dropdown'
		) {
			if (
				$is_date_format &&
				( empty( $field_submit['date']['m'] ) || empty( $field_submit['date']['d'] ) || empty( $field_submit['date']['y'] ) )
			) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['date'] = $required;
			}
		} elseif (
			$is_date_format &&
			empty( $field_submit['date'] )
		) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['date'] = $required;
		}

		if (
			$is_time_format &&
			empty( $field_submit['time'] )
		) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['time'] = $required;
		}
	}

	/**
	 * Validate time limit (Limit Hours).
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	private function validate_time_limit( $field_id, $field_submit, $form_data ): void {

		if ( empty( $form_data['fields'][ $field_id ] ) ) {
			return;
		}

		$field = $form_data['fields'][ $field_id ];

		if ( empty( $field['time_limit_hours'] ) || empty( $field_submit['time'] ) ) {
			return;
		}

		// Limit Hours validation should apply only for defaulted (embedded) time formats.
		if (
			empty( $field['time_format'] ) ||
			! in_array( $field['time_format'], [ self::DEFAULTS['time_format'], 'H:i' ], true )
		) {
			return;
		}

		$min_time = $field['time_limit_hours_start_hour'] . ':' . $field['time_limit_hours_start_min'];
		$max_time = $field['time_limit_hours_end_hour'] . ':' . $field['time_limit_hours_end_min'];

		if ( $field['time_format'] === self::DEFAULTS['time_format'] ) {
			if ( $field['time_limit_hours_start_hour'] === '00' ) {
				$min_time = '12:' . $field['time_limit_hours_start_min'];
			}

			if ( $field['time_limit_hours_end_hour'] === '00' ) {
				$max_time = '12:' . $field['time_limit_hours_end_min'];
			}

			$min_time .= ' ' . strtoupper( $field['time_limit_hours_start_ampm'] );
			$max_time .= ' ' . strtoupper( $field['time_limit_hours_end_ampm'] );
		}

		$min_timestamp    = strtotime( $min_time );
		$max_timestamp    = strtotime( $max_time );
		$submit_timestamp = strtotime( $field_submit['time'] );

		if ( $max_timestamp > $min_timestamp ) {
			$is_valid = ( $submit_timestamp >= $min_timestamp ) && ( $submit_timestamp <= $max_timestamp );
		} else {
			$is_valid = ( ( $submit_timestamp >= $min_timestamp ) && ( $submit_timestamp >= $max_timestamp ) ) ||
						( ( $submit_timestamp <= $min_timestamp ) && ( $submit_timestamp <= $max_timestamp ) );
		}

		if ( ! $is_valid ) {
			$error = wpforms_setting( 'validation-time-limit', esc_html__( 'Please enter time between {minTime} and {maxTime}.', 'wpforms' ) );
			$error = str_replace( [ '{minTime}', '{maxTime}' ], [ $min_time, $max_time ], $error );

			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ]['time'] = $error;
		}
	}

	/**
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.MaxExceeded

		if ( empty( $form_data['fields'][ $field_id ] ) ) {
			return;
		}

		$field = $form_data['fields'][ $field_id ];

		$name        = ! empty( $field['label'] ) ? $field['label'] : '';
		$format      = $field['format'] ?? self::DEFAULTS['format'];
		$date_format = $field['date_format'] ?? self::DEFAULTS['date_format'];
		$time_format = $field['time_format'] ?? self::DEFAULTS['time_format'];
		$value       = '';
		$date        = '';
		$time        = '';
		$unix        = '';

		if ( ! empty( $field_submit['date'] ) ) {
			if ( is_array( $field_submit['date'] ) ) {

				if (
					! empty( $field_submit['date']['m'] ) &&
					! empty( $field_submit['date']['d'] ) &&
					! empty( $field_submit['date']['y'] )
				) {
					if (
						$date_format === 'dd/mm/yyyy' ||
						$date_format === self::ALT_DATE_FORMAT
					) {
						$date = $field_submit['date']['d'] . '/' . $field_submit['date']['m'] . '/' . $field_submit['date']['y'];
					} else {
						$date = $field_submit['date']['m'] . '/' . $field_submit['date']['d'] . '/' . $field_submit['date']['y'];
					}
				} else {
					// So we are missing some values.
					// We can't process date further, as we won't be able to retrieve its unix time.
					wpforms()->obj( 'process' )->fields[ $field_id ] = [
						'name'  => sanitize_text_field( $name ),
						'value' => sanitize_text_field( $value ),
						'id'    => wpforms_validate_field_id( $field_id ),
						'type'  => $this->type,
						'date'  => '',
						'time'  => '',
						'unix'  => false,
					];

					return;
				}
			} else {
				$date = $field_submit['date'];
			}
		}

		if ( ! empty( $field_submit['time'] ) ) {
			$time = $field_submit['time'];
		}

		if ( $format === self::DEFAULTS['format'] && ! empty( $field_submit ) ) {
			$value = trim( "$date $time" );
		} elseif ( $format === 'date' ) {
			$value = $date;
		} elseif ( $format === 'time' ) {
			$value = $time;
		}

		// Always store the raw time in 12H format.
		if ( ( $time_format === 'H:i A' || $time_format === 'H:i' ) && ! empty( $time ) ) {
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$time = date( self::DEFAULTS['time_format'], strtotime( $time ) );
		}

		// Always store the date in m/d/Y format so it is strtotime() compatible.
		if (
			( $date_format === 'dd/mm/yyyy' || $date_format === self::ALT_DATE_FORMAT ) &&
			! empty( $date )
		) {
			[ $d, $m, $y ] = explode( '/', $date );

			$date = "$m/$d/$y";
		}

		// Calculate unix time if we have a date.
		if ( ! empty( $date ) ) {
			$unix = strtotime( trim( "$date $time" ) );
		}

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => sanitize_text_field( $name ),
			'value' => sanitize_text_field( $value ),
			'id'    => wpforms_validate_field_id( $field_id ),
			'type'  => $this->type,
			'date'  => sanitize_text_field( $date ),
			'time'  => sanitize_text_field( $time ),
			'unix'  => $unix,
		];
	}

	/**
	 * Format the smart tag value.
	 *
	 * @since 1.9.5
	 *
	 * @param string|mixed $value     Field value.
	 * @param int          $field_id  Field ID.
	 * @param array        $fields    List of fields.
	 * @param string       $field_key Field key to get value from.
	 * @param array        $form_data Form data.
	 *
	 * @return string
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function smart_tags_formatted_field_value( $value, $field_id, $fields, $field_key, $form_data ): string {

		$value = (string) $value;

		// Check if the field is a DateTime field.
		if (
			empty( $fields[ $field_id ] ) ||
			empty( $fields[ $field_id ]['type'] ) ||
			$fields[ $field_id ]['type'] !== $this->type
		) {
			return $value;
		}

		// Continue only for the non-combined value (date and time subfields).
		if (
			empty( $form_data['fields'][ $field_id ] ) ||
			! in_array( $field_key, [ 'date', 'time' ], true )
		) {
			return $value;
		}

		$field     = $form_data['fields'][ $field_id ];
		$saved_ts  = $fields[ $field_id ]['unix'] ?? '';
		$dt_string = $fields[ $field_id ][ $field_key ] ?? '';
		$parsed_ts = strtotime( $dt_string . ' ' . wp_timezone_string() );
		$timestamp = $parsed_ts === false ? $saved_ts : $parsed_ts;

		// Get the format.
		if ( $field_key === 'time' ) {
			$format = empty( $field['time_format'] ) ? self::DEFAULTS['time_format'] : $field['time_format'];
		} else {
			$format = empty( $field['date_format'] ) ? self::DEFAULTS['date_format'] : $field['date_format'];
		}

		return wp_date( $format, (int) $timestamp, wp_timezone() );
	}
}
