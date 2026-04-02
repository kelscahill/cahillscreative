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
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_builder_enqueues' ] );
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_page_navigation_script' ] );
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

		// Localize script.
		$strings = $this->get_allow_page_navigation_strings();

		wp_localize_script(
			'wpforms-builder-page-break-field',
			'wpforms_builder_page_break',
			[
				'allow_page_navigation_enabled'  => $strings['enabled'],
				'allow_page_navigation_disabled' => $strings['disabled'],
			]
		);
	}

	/**
	 * Enqueue page navigation script.
	 *
	 * @since 1.10.0
	 *
	 * @param array $forms Forms data.
	 */
	public function enqueue_page_navigation_script( $forms ): void {

		$forms = (array) $forms;

		if ( empty( $forms ) ) {
			return;
		}

		$should_enqueue = false;

		// Check if any form has pagebreak fields with allow_page_navigation enabled.
		foreach ( $forms as $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				continue;
			}

			foreach ( $form_data['fields'] as $field ) {
				if ( $field['type'] === 'pagebreak' && ! empty( $field['allow_page_navigation'] ) ) {
					$should_enqueue = true;

					break 2;
				}
			}
		}

		if ( ! $should_enqueue ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-page-navigation',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/wpforms-page-navigation{$min}.js",
			[ 'jquery', 'wpforms' ],
			WPFORMS_VERSION,
			true
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
	public function display_page_indicator( $form_data ): void {

		$top = ! empty( wpforms()->obj( 'frontend' )->pages['top'] ) ? (array) wpforms()->obj( 'frontend' )->pages['top'] : false;

		if ( empty( $top['indicator'] ) || $top['indicator'] === 'none' ) {
			return;
		}

		$pagebreak = $this->prepare_pagebreak_data( $top );

		$this->frontend_obj->open_page_indicator_container( $pagebreak );
		$this->render_indicator_by_type( $pagebreak, $top );

		/**
		 * Fires after the page indicator is displayed.
		 *
		 * @since 1.3.7
		 *
		 * @param array $pagebreak Pagebreak settings.
		 * @param array $form_data Form data and settings.
		 */
		do_action( 'wpforms_pagebreak_indicator', $pagebreak, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		echo '</div>'; // Close wpforms-page-indicator.
	}

	/**
	 * Prepare pagebreak data array.
	 *
	 * @since 1.10.0
	 *
	 * @param array $top Top pagebreak field data.
	 *
	 * @return array Prepared pagebreak data.
	 */
	private function prepare_pagebreak_data( array $top ): array {

		return [
			'indicator'             => sanitize_html_class( $top['indicator'] ),
			'color'                 => wpforms_sanitize_hex_color( $top['indicator_color'] ?? self::get_default_indicator_color() ),
			'pages'                 => array_merge( [ wpforms()->obj( 'frontend' )->pages['top'] ], wpforms()->obj( 'frontend' )->pages['pages'] ),
			'scroll'                => empty( $top['scroll_disabled'] ),
			'allow_page_navigation' => ! empty( $top['allow_page_navigation'] ) ? 1 : 0,
		];
	}

	/**
	 * Render indicator by type.
	 *
	 * @since 1.10.0
	 *
	 * @param array $pagebreak Pagebreak data.
	 * @param array $top       Top pagebreak field data.
	 */
	private function render_indicator_by_type( array $pagebreak, array $top ): void {

		switch ( $pagebreak['indicator'] ) {
			case 'circles':
				$this->render_circles_indicator( $pagebreak );
				break;

			case 'connector':
				$this->render_connector_indicator( $pagebreak );
				break;

			case 'progress':
				$this->render_progress_indicator( $pagebreak, $top );
				break;
		}
	}

	/**
	 * Render circles indicator.
	 *
	 * @since 1.10.0
	 *
	 * @param array $pagebreak Pagebreak data.
	 */
	private function render_circles_indicator( array $pagebreak ): void {

		$page_num = 1;

		$allow_page_navigation = $pagebreak['allow_page_navigation'] ?? false;

		foreach ( $pagebreak['pages'] as $page ) {
			$this->render_circles_indicator_item( $page, $page_num, $pagebreak['color'], (bool) $allow_page_navigation );
			++$page_num;
		}
	}

	/**
	 * Render connector indicator.
	 *
	 * @since 1.10.0
	 *
	 * @param array $pagebreak Pagebreak data.
	 */
	private function render_connector_indicator( array $pagebreak ): void {

		$page_num = 1;
		$width    = 100 / ( count( $pagebreak['pages'] ) ) . '%';

		$allow_page_navigation = $pagebreak['allow_page_navigation'] ?? false;

		foreach ( $pagebreak['pages'] as $page ) {
			$this->render_connector_indicator_item( $page, $page_num, $pagebreak['color'], $width, (bool) $allow_page_navigation );
			++$page_num;
		}
	}

	/**
	 * Render progress indicator.
	 *
	 * @since 1.10.0
	 *
	 * @param array $pagebreak Pagebreak data.
	 * @param array $top       Top pagebreak field data.
	 */
	private function render_progress_indicator( array $pagebreak, array $top ): void {

		$p1               = ! empty( $pagebreak['pages'][0]['title'] ) ? (string) $pagebreak['pages'][0]['title'] : '';
		$width            = 100 / count( $pagebreak['pages'] ) . '%';
		$background_color = ! empty( $pagebreak['color'] ) ? $pagebreak['color'] : '';
		$pages            = (array) $pagebreak['pages'];

		// Render page title.
		$this->render_progress_page_title( $pages, $p1 );

		// Render progress text.
		$this->render_progress_text( $top, count( $pages ) );

		// Render progress bar.
		$this->render_progress_bar( $width, $background_color );
	}

	/**
	 * Render progress page title.
	 *
	 * @since 1.10.0
	 *
	 * @param array  $pages            Pages data.
	 * @param string $first_page_title First page title.
	 */
	private function render_progress_page_title( array $pages, string $first_page_title ): void {

		$names    = [];
		$page_num = 1;

		foreach ( $pages as $page ) {
			if ( ! empty( $page['title'] ) ) {
				$names[ sprintf( 'page-%d-title', $page_num ) ] = $page['title'];
			}

			++$page_num;
		}

		printf(
			'<span class="wpforms-page-indicator-page-title" %s>%s</span>',
			wpforms_html_attributes( '', [], $names ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $first_page_title )
		);

		printf(
			'<span class="wpforms-page-indicator-page-title-sep" %s> - </span>',
			empty( $first_page_title ) ? 'style="display:none;"' : ''
		);
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
