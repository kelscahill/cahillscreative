<?php

namespace WPForms\Pro\Admin\Education\Builder;

use WPForms\Admin\Education;
use WPForms\Admin\Education\Helpers;
use WPForms\Helpers\Form;

/**
 * Builder/Fields Education for Pro.
 *
 * @since 1.6.6
 */
class Fields extends Education\Builder\Fields {

	/**
	 * License data.
	 *
	 * @since 1.9.9
	 *
	 * @var array|null
	 */
	private $license_data;

	/**
	 * Hooks.
	 *
	 * @since 1.6.6
	 */
	public function hooks() {

		add_filter( 'wpforms_builder_fields_buttons', [ $this, 'add_fields' ], 500 );
		add_filter( 'wpforms_builder_field_button_attributes', [ $this, 'fields_attributes' ], 100, 2 );
		add_action( 'wpforms_builder_panel_fields_panel_content_title_after', [ $this, 'form_preview_notice' ] );

		if ( ! $this->is_valid_license() ) {
			add_filter( 'wpforms_builder_fields_buttons', [ $this, 'no_license_fields' ], 501 );
			add_filter( 'wpforms_builder_field_button_attributes', [ $this, 'no_license_fields_attributes' ], 101, 2 );
		}
	}

	/**
	 * Determine if the license is valid.
	 *
	 * @since 1.7.6
	 *
	 * @return bool
	 */
	private function is_valid_license(): bool {

		// Avoid multiple calculations.
		static $is_valid = null;

		if ( $is_valid !== null ) {
			return $is_valid;
		}

		// License data.
		$license = (array) get_option( 'wpforms_license', [] );

		$is_valid = ! empty( wpforms_get_license_key() )
			&& ! empty( $license['type'] )
			&& empty( $license['is_expired'] )
			&& empty( $license['is_disabled'] )
			&& empty( $license['is_invalid'] );

		return $is_valid;
	}

	/**
	 * Add fields.
	 *
	 * @since 1.6.6
	 *
	 * @param array $fields Form fields.
	 *
	 * @return array
	 */
	public function add_fields( $fields ) {

		$nonce = (string) wp_create_nonce( 'wpforms-admin' );

		foreach ( $fields as $group => $group_data ) {
			$fields = $this->fields_add_group_fields( $fields, $group, $nonce );
		}

		return $fields;
	}

	/**
	 * Add education fields to the given fields group.
	 *
	 * @since 1.6.6
	 *
	 * @param array  $fields Fields.
	 * @param string $group  Fields group.
	 * @param string $nonce  Nonce.
	 *
	 * @return array
	 */
	private function fields_add_group_fields( array $fields, string $group, string $nonce ): array {

		$addons_slugs = array_column( $this->addons->get_available(), 'slug' );
		$group_fields = $fields[ $group ]['fields'];
		$edu_fields   = $this->fields->get_by_group( $group );
		$edu_fields   = $this->fields->set_values( $edu_fields, 'class', 'education-modal', 'empty' );

		foreach ( $edu_fields as $edu_field ) {
			// Skip if in the current group already exist field of this type.
			if ( ! empty( wp_list_filter( $group_fields, [ 'type' => $edu_field['type'] ] ) ) ) {
				continue;
			}

			// Also skip if the field is provided by addon, which is not available.
			if (
				! empty( $edu_field['addon'] ) &&
			    ! in_array( $edu_field['addon'], $addons_slugs, true )
			) {
				continue;
			}

			$addon = ! empty( $edu_field['addon'] ) ? $this->addons->get_addon( $edu_field['addon'] ) : [];

			if ( ! empty( $addon ) ) {
				$addon = wp_parse_args(
					$addon,
					[
						'slug'          => '',
						'title'         => '',
						'action'        => '',
						'url'           => '',
						'video'         => '',
						'license_level' => '',
						'plugin_allow'  => false,
						'message'       => '',
					]
				);

				$edu_field['plugin']      = sprintf( '%1$s/%1$s.php', $addon['slug'] );
				$edu_field['plugin_name'] = $addon['title'];
				$edu_field['action']      = $addon['action'];
				$edu_field['url']         = $edu_field['action'] === 'install' ? $addon['url'] : '';
				$edu_field['video']       = $addon['video'];
				$edu_field['license']     = $addon['license_level'];
				$edu_field['allowed']     = $addon['plugin_allow'];
				$edu_field['nonce']       = $nonce;
				$edu_field['message']     = $addon['message'];
			}

			$fields[ $group ]['fields'][] = $edu_field;
		}

		return $fields;
	}

	/**
	 * Adjust attributes on field buttons.
	 *
	 * @since 1.6.6
	 *
	 * @param array $atts  Button attributes.
	 * @param array $field Button properties.
	 *
	 * @return array Attributes array.
	 */
	public function fields_attributes( $atts, $field ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( empty( $field['name_en'] ) && ! empty( $field['type'] ) ) {
			$edu_field        = $this->fields->get_field( $field['type'] );
			$field['name_en'] = $edu_field['name_en'] ?? '';
		}

		$atts['data']['utm-content'] = ! empty( $field['name_en'] ) ? $field['name_en'] : '';

		if ( empty( $field['action'] ) ) {
			return $atts;
		}

		$atts['data']['field-name'] = sprintf( /* translators: %s - field name. */
			esc_html__( '%s field', 'wpforms' ),
			$field['name']
		);

		$atts['data']['action'] = $field['action'];
		$atts['data']['nonce']  = wp_create_nonce( 'wpforms-admin' );

		if ( ! empty( $field['plugin_name'] ) ) {
			$atts['data']['name'] = ! preg_match( '/addon$/i', $field['plugin_name'] ) ?
				sprintf( /* translators: %s - addon name. */
					esc_html__( '%s addon', 'wpforms' ),
					$field['plugin_name']
				) :
				$field['plugin_name'];
		}

		if ( ! empty( $field['plugin'] ) ) {
			$atts['data']['path'] = $field['plugin'];
		}

		if ( ! empty( $field['url'] ) ) {
			$atts['data']['url'] = $field['url'];
		}

		if ( ! empty( $field['message'] ) ) {
			$atts['data']['message'] = $field['message'];
		}

		if ( ! empty( $field['video'] ) ) {
			$atts['data']['video'] = $field['video'];
		}

		if ( ! empty( $field['license'] ) ) {
			$atts['data']['license'] = $field['license'];
		}

		return $atts;
	}

	/**
	 * Update fields when the license type is empty.
	 *
	 * @since 1.7.6
	 *
	 * @param array $fields Form fields.
	 *
	 * @return array
	 */
	public function no_license_fields( $fields ) {

		foreach ( $fields as $group => $group_data ) {
			if ( $group === 'standard' ) {
				continue;
			}

			foreach ( $group_data['fields'] as $key => $field ) {
				$fields[ $group ]['fields'][ $key ]['action'] = 'license';
			}
		}

		return $fields;
	}

	/**
	 * Adjust attributes on field buttons when the license type is empty.
	 *
	 * @since 1.7.6
	 *
	 * @param array|mixed $atts  Button attributes.
	 * @param array       $field Button properties.
	 *
	 * @return array Attributes array.
	 */
	public function no_license_fields_attributes( $atts, array $field ): array {

		$atts = (array) $atts;

		if ( empty( $field['action'] ) ) {
			return $atts;
		}

		$atts['data']['action'] = $field['action'];
		$atts['class'][]        = 'education-modal';

		return $atts;
	}

	/**
	 * Get license data.
	 *
	 * @since 1.9.9
	 *
	 * @return array
	 */
	private function get_license_data(): array {

		if ( $this->license_data ) {
			return $this->license_data;
		}

		$license = (array) get_option( 'wpforms_license', [] );

		$this->license_data = [
			$license['key'] ?? '',
			$license['type'] ?? '',
			$this->is_valid_license(),
		];

		return $this->license_data;
	}

	/**
	 * The 'form preview' addon fields notice.
	 *
	 * @since 1.9.4
	 *
	 * @param array $form_data Form data.
	 */
	public function form_preview_notice( array $form_data ): void {

		if ( $this->maybe_print_quiz_notice( $form_data ) ) {
			return;
		}

		$this->print_addon_fields_notice( $form_data );
	}

	/**
	 * Print addon fields notice.
	 *
	 * @since 1.9.9
	 *
	 * @param array $form_data Form data.
	 *
	 * @noinspection HtmlUnknownTarget
	 */
	private function print_addon_fields_notice( array $form_data ): void {

		$dismissed  = get_user_meta( get_current_user_id(), 'wpforms_dismissed', true );
		$edu_addons = Form::get_form_addons_edu_data( $form_data );

		if ( ! empty( $dismissed['edu-addon-fields-form-preview-notice'] ) || empty( $edu_addons ) ) {
			return;
		}

		$actions = wp_list_pluck( $edu_addons, 'action' );

		[ $license_key, $license_type, $license_is_valid ] = $this->get_license_data();

		if ( $this->should_print_upgrade_notice( $license_is_valid, $license_type ) ) {
			$content = sprintf(
				wp_kses( /* translators: %s - WPForms.com announcement page URL. */
					__( 'They will not be present in the published form. <a href="%1$s" target="_blank" rel="noopener noreferrer">Upgrade now</a> to unlock these features.', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				add_query_arg(
					[ 'license_key' => sanitize_text_field( $license_key ) ],
					wpforms_admin_upgrade_link( 'Builder - Settings', 'AI Form - Inactive Addon Field notice' )
				)
			);
		} else {
			$content = esc_html__( 'They will still be visible in the form preview, but will not be present in the published form.', 'wpforms' );
		}

		$args = [
			'class'           => 'wpforms-alert-warning',
			'title'           => esc_html__( 'Your Form Contains Fields From Inactive Addons', 'wpforms' ),
			'content'         => $content,
			'dismiss_section' => 'addon-fields-form-preview-notice',
		];

		if ( in_array( 'incompatible', $actions, true ) ) {
			$args['class'] = 'wpforms-alert-error';
			$args['title'] = esc_html__( 'Your Form Contains Fields From Incompatible Addons', 'wpforms' );
		}

		$this->print_form_preview_notice( $args );
	}

	/**
	 * Print the Quiz addon notice.
	 *
	 * @since 1.9.9
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 * @noinspection HtmlUnknownTarget
	 */
	private function maybe_print_quiz_notice( array $form_data ): bool {

		if ( empty( $form_data['settings']['quiz']['enabled'] ) ) {
			return false;
		}

		$dismissed     = get_user_meta( get_current_user_id(), 'wpforms_dismissed', true );
		$quiz_edu_data = Helpers::get_edu_addons()['wpforms-quiz'] ?? [];

		if ( ! empty( $dismissed['edu-quiz-form-preview-notice'] ) || empty( $quiz_edu_data ) ) {
			return false;
		}

		[ $license_key, $license_type, $license_is_valid ] = $this->get_license_data();

		if ( $this->should_print_upgrade_notice( $license_is_valid, $license_type ) ) {
			$content = sprintf(
				wp_kses( /* translators: %s - Upgrade to Pro page URL. */
					__( 'Quiz functionality will not be present in the published form. <a href="%1$s" target="_blank" rel="noopener noreferrer">Upgrade now</a> to unlock the Quiz Addon.', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				add_query_arg(
					[ 'license_key' => sanitize_text_field( $license_key ) ],
					wpforms_admin_upgrade_link( 'Builder - Settings', 'AI Form - Quiz Addon in Pro notice' )
				)
			);
		} else {
			$content = esc_html__( 'Quiz functionality is not available until the Quiz addon activated.', 'wpforms' );
		}

		$args = [
			'class'           => 'wpforms-alert-warning',
			'title'           => esc_html__( 'Your Form Uses the Quiz Addon', 'wpforms' ),
			'content'         => $content,
			'dismiss_section' => 'quiz-form-preview-notice',
		];

		$this->print_form_preview_notice( $args );

		return true;
	}

	/**
	 * Whether the Upgrade notice should be displayed.
	 *
	 * @since 1.9.9
	 *
	 * @param bool|null $license_is_valid Whether the license is valid.
	 * @param string    $license_type     The license type.
	 *
	 * @return bool
	 */
	private function should_print_upgrade_notice( ?bool $license_is_valid, string $license_type ): bool {

		return ! $license_is_valid || in_array( $license_type, [ 'basic', 'plus' ], true );
	}
}
