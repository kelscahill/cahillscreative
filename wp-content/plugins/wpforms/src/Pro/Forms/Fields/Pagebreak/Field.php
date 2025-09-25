<?php

namespace WPForms\Pro\Forms\Fields\Pagebreak;

use WPForms\Forms\Fields\Pagebreak\Field as FieldLite;

/**
 * Pagebreak field.
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

		add_filter( 'wpforms_field_new_class', [ $this, 'preview_field_class' ], 10, 2 );
		add_filter( 'wpforms_frontend_form_data', [ $this, 'maybe_sort_fields' ], PHP_INT_MAX );
		add_action( 'wpforms_frontend_output', [ $this, 'display_page_indicator' ], 9, 5 );
		add_action( 'wpforms_display_fields_before', [ $this, 'display_fields_before' ], 20, 2 );
		add_action( 'wpforms_display_fields_after', [ $this, 'display_fields_after' ], 5, 2 );
		add_action( 'wpforms_display_field_after', [ $this, 'display_field_after' ], 20, 2 );
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );

		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_builder_enqueues' ] );
	}

	/**
	 * Enqueue script for the admin form builder.
	 *
	 * @since 1.9.7
	 */
	public function admin_builder_enqueues(): void {

		$min = wpforms_get_min_suffix();

		// JavaScript.
		wp_enqueue_script(
			'wpforms-builder-page-break-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/page-break{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Sort fields to make sure that bottom page break elements are in their place.
	 * Need to correctly display existing forms with wrong page-break bottom element positioning.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $form_data Form data.
	 *
	 * @return array Form data.
	 */
	public function maybe_sort_fields( $form_data ): array {

		$form_data = (array) $form_data;

		if ( empty( $form_data['fields'] ) ) {
			return $form_data;
		}

		$bottom = [];
		$fields = $form_data['fields'];

		foreach ( $fields as $id => $field ) {
			// Process only pagebreak fields.
			if ( $field['type'] !== 'pagebreak' ) {
				continue;
			}
			if ( empty( $field['position'] ) ) {
				continue;
			}

			if ( $field['position'] === 'bottom' ) {
				$bottom = $field;

				unset( $fields[ $id ] );
			}
		}

		if ( ! empty( $bottom ) ) {
			$form_data['fields'] = $fields + [ $bottom['id'] => $bottom ];
		}

		return $form_data;
	}

	/**
	 * This displays if the form contains pagebreaks and is configured to show
	 * a page indicator in the top pagebreak settings.
	 *
	 * This function was moved from class-frontend.php in v1.3.7.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function display_page_indicator( $form_data ): void { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$top = ! empty( wpforms()->obj( 'frontend' )->pages['top'] ) ? wpforms()->obj( 'frontend' )->pages['top'] : false;

		if ( empty( $top['indicator'] ) ) {
			return;
		}

		$pagebreak = [
			'indicator' => sanitize_html_class( $top['indicator'] ),
			'color'     => wpforms_sanitize_hex_color( $top['indicator_color'] ?? self::get_default_indicator_color() ),
			'pages'     => array_merge( [ wpforms()->obj( 'frontend' )->pages['top'] ], wpforms()->obj( 'frontend' )->pages['pages'] ),
			'scroll'    => empty( $top['scroll_disabled'] ),
		];
		$p         = 1;

		$this->frontend_obj->open_page_indicator_container( $pagebreak );

		if ( $pagebreak['indicator'] === 'circles' ) {

			// Circles theme.
			foreach ( $pagebreak['pages'] as $page ) {
				$is_first         = $p === 1;
				$class            = $is_first ? 'active' : '';
				$background_color = ! empty( $pagebreak['color'] ) ? $pagebreak['color'] : '';

				printf(
					'<div class="wpforms-page-indicator-page %s wpforms-page-indicator-page-%d">',
					sanitize_html_class( $class ),
					absint( $p )
				);
				printf(
					'<span class="wpforms-page-indicator-page-number"%s>%d</span>',
					$is_first && ! empty( $background_color ) ? ' style="background-color:' . sanitize_hex_color( $background_color ) . '"' : '',
					absint( $p )
				);
				if ( ! empty( $page['title'] ) ) {
					printf( '<span class="wpforms-page-indicator-page-title">%s</span>', esc_html( $page['title'] ) );
				}
				echo '</div>';

				++$p;
			}
		} elseif ( $pagebreak['indicator'] === 'connector' ) {

			// Connector theme.
			foreach ( $pagebreak['pages'] as $page ) {
				$is_first = $p === 1;
				$class    = $is_first ? 'active ' : '';
				$color    = ! empty( $pagebreak['color'] ) ? $pagebreak['color'] : '';
				$width    = 100 / ( count( $pagebreak['pages'] ) ) . '%';

				printf(
					'<div class="wpforms-page-indicator-page %s wpforms-page-indicator-page-%d" style="width:%s;">',
					sanitize_html_class( $class ),
					absint( $p ),
					esc_attr( $width )
				);
				printf(
					'<span class="wpforms-page-indicator-page-number"%s>%d',
					$is_first && ! empty( $color ) ? ' style="background-color:' . sanitize_hex_color( $color ) . '"' : '',
					absint( $p )
				);
				printf(
					'<span class="wpforms-page-indicator-page-triangle"%s></span></span>',
					$is_first && ! empty( $color ) ? ' style="border-top-color:' . sanitize_hex_color( $color ) . '"' : ''
				);
				if ( ! empty( $page['title'] ) ) {
					printf( '<span class="wpforms-page-indicator-page-title">%s</span>', esc_html( $page['title'] ) );
				}
				echo '</div>';

				++$p;
			}
		} elseif ( $pagebreak['indicator'] === 'progress' ) {

			// Progress theme.
			$p1               = ! empty( $pagebreak['pages'][0]['title'] ) ? $pagebreak['pages'][0]['title'] : '';
			$width            = 100 / count( $pagebreak['pages'] ) . '%';
			$names            = [];
			$background_color = ! empty( $pagebreak['color'] ) ? $pagebreak['color'] : '';

			foreach ( $pagebreak['pages'] as $page ) {
				if ( ! empty( $page['title'] ) ) {
					$names[ sprintf( 'page-%d-title', $p ) ] = $page['title'];
				}

				++$p;
			}
			printf(
				'<span class="wpforms-page-indicator-page-title" %s>%s</span>',
				wpforms_html_attributes( '', [], $names ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $p1 )
			);
			printf(
				'<span class="wpforms-page-indicator-page-title-sep" %s> - </span>',
				empty( $p1 ) ? 'style="display:none;"' : ''
			);

			$progress_text = ! empty( $top['progress_text'] ) ?
				str_replace( [ '{current_page}', '{last_page}' ], [ '%1$s', '%2$s' ], str_replace( '%', '%%', $top['progress_text'] ) ) :
				/* translators: %1$s - current step in multipage form, %2$d - total number of pages. */
				esc_html__( 'Step %1$s of %2$d', 'wpforms' );

			printf(
				'<span class="wpforms-page-indicator-steps">' . esc_html( $progress_text ) . '</span>',
				'<span class="wpforms-page-indicator-steps-current">1</span>',
				count( $pagebreak['pages'] )
			);

			printf(
				'<div class="wpforms-page-indicator-page-progress-wrap"><div class="wpforms-page-indicator-page-progress" style="width:%s;%s"></div></div>',
				esc_attr( $width ),
				! empty( $background_color ) ? 'background-color:' . sanitize_hex_color( $background_color ) : ''
			);
		}

		/**
		 * Fires after the page indicator is displayed.
		 *
		 * @since 1.3.7
		 *
		 * @param array $pagebreak Pagebreak settings.
		 * @param array $form_data Form data and settings.
		 */
		do_action( 'wpforms_pagebreak_indicator', $pagebreak, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		echo '</div>';
	}

	/**
	 * Display frontend markup for the beginning of the first pagebreak.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 */
	public function display_fields_before( $form_data ): void {

		// Check if we have an opening pagebreak, if not then bail.
		$field = ! empty( wpforms()->obj( 'frontend' )->pages['top'] ) ? wpforms()->obj( 'frontend' )->pages['top'] : false;

		if ( ! $field ) {
			return;
		}

		$css = ! empty( $field['css'] ) ? $field['css'] : '';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="wpforms-page wpforms-page-1 ' . wpforms_sanitize_classes( $css ) . '" data-page="1">';

		/**
		 * Fires before all fields on the page.
		 *
		 * @since 1.7.8
		 *
		 * @param array $field     Field data and settings.
		 * @param array $form_data Form data and settings.
		 */
		do_action( 'wpforms_field_page_break_page_fields_before', $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Display frontend markup for the end of the last pagebreak.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data and settings.
	 */
	public function display_fields_after( $form_data ): void {

		if ( empty( wpforms()->obj( 'frontend' )->pages ) ) {
			return;
		}

		// If we don't have a bottom pagebreak, the form is pre-v1.2.1, and this is for backwards compatibility.
		$bottom = ! empty( wpforms()->obj( 'frontend' )->pages['bottom'] ) ? wpforms()->obj( 'frontend' )->pages['top'] : false;

		if ( ! $bottom ) {

			$prev = ! empty( $form_data['settings']['pagebreak_prev'] ) ? $form_data['settings']['pagebreak_prev'] : esc_html__( 'Previous', 'wpforms' );

			echo '<div class="wpforms-field wpforms-field-pagebreak">';
			printf(
				'<button class="wpforms-page-button wpforms-page-prev" data-action="prev" data-page="%d" data-formid="%d">%s</button>',
				absint( wpforms()->obj( 'frontend' )->pages['current'] + 1 ),
				absint( $form_data['id'] ),
				esc_html( $prev )
			);
			echo '</div>';
		}

		$field = ! empty( wpforms()->obj( 'frontend' )->pages['bottom'] ) ? wpforms()->obj( 'frontend' )->pages['bottom'] : $bottom;

		/**
		 * Fires after all fields on the page.
		 *
		 * @since 1.7.8
		 *
		 * @param array $field     Field data and settings.
		 * @param array $form_data Form data and settings.
		 */
		do_action( 'wpforms_field_page_break_page_fields_after', $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		echo '</div>';
	}

	/**
	 * Display frontend markup to end the current page and begin the next.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field     Field data and settings.
	 * @param array $form_data Form data and settings.
	 */
	public function display_field_after( $field, $form_data ): void {

		if ( $field['type'] !== 'pagebreak' ) {
			return;
		}

		$total   = wpforms()->obj( 'frontend' )->pages['total'];
		$current = wpforms()->obj( 'frontend' )->pages['current'];

		if ( ( empty( $field['position'] ) || $field['position'] !== 'top' ) && $current !== $total ) {

			$next = $current + 1;
			$last = $next === $total ? 'last' : '';
			$css  = ! empty( $field['css'] ) ? $field['css'] : '';

			/**
			 * Fires after all fields on the page.
			 *
			 * @since 1.7.8
			 *
			 * @param array $field     Field data and settings.
			 * @param array $form_data Form data and settings.
			 */
			do_action( 'wpforms_field_page_break_page_fields_after', $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

			printf(
				'</div><div class="wpforms-page wpforms-page-%1$d %2$s %3$s" data-page="%1$d" style="display:none;">',
				absint( $next ),
				esc_html( $last ),
				wpforms_sanitize_classes( $css ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);

			/**
			 * Fires before all fields on the page.
			 *
			 * @since 1.7.8
			 *
			 * @param array $field     Field data and settings.
			 * @param array $form_data Form data and settings.
			 */
			do_action( 'wpforms_field_page_break_page_fields_before', $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

			// Increase count for next page.
			wpforms()->obj( 'frontend' )->pages['current']++;
		}
	}

	/**
	 * Disallow dynamic population.
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
	 * Whether the current field can be populated dynamically.
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
	 * Field display on the form front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Field attributes.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Top pagebreaks don't display.
		if ( ! empty( $field['position'] ) && $field['position'] === 'top' ) {
			return;
		}

		// Setup and sanitize the necessary data.

		/**
		 * Allow modifying page divider field before display.
		 *
		 * @since 1.0.0
		 *
		 * @param array $field      Field data and settings.
		 * @param array $deprecated Field attributes.
		 * @param array $form_data  Form data and settings.
		 */
		$filtered_field = apply_filters( 'wpforms_pagedivider_field_display', $field, $deprecated, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		$field          = wpforms_list_intersect_key( (array) $filtered_field, $field );

		$total   = wpforms()->obj( 'frontend' )->pages['total'];
		$current = wpforms()->obj( 'frontend' )->pages['current'];
		$top     = wpforms()->obj( 'frontend' )->pages['top'];
		$next    = ! empty( $field['next'] ) ? $field['next'] : '';
		$prev    = ! empty( $field['prev'] ) ? $field['prev'] : '';
		$align   = 'wpforms-pagebreak-center';

		if ( ! empty( $top['nav_align'] ) ) {
			$align = 'wpforms-pagebreak-' . $top['nav_align'];
		}

		echo '<div class="wpforms-clear ' . sanitize_html_class( $align ) . '">';

		if ( $current > 1 && ! empty( $prev ) ) {
			printf(
				'<button class="wpforms-page-button wpforms-page-prev" data-action="prev" data-page="%d" data-formid="%d" disabled>%s</button>',
				(int) $current,
				(int) $form_data['id'],
				esc_html( $prev )
			);
		}

		if ( $current < $total && ! empty( $next ) ) {
			printf(
				'<button class="wpforms-page-button wpforms-page-next" data-action="next" data-page="%d" data-formid="%d" disabled>%s</button>',
				(int) $current,
				(int) $form_data['id'],
				esc_html( $next )
			);

			/** This action is documented in includes/class-frontend.php. */
			do_action( 'wpforms_display_submit_after', $form_data, 'next' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		}
		echo '</div>';
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
	public function format( $field_id, $field_submit, $form_data ) {
	}
}
