<?php
/**
 * Checkbox Filter Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields\Choice;

use Search_Filter\Fields\Choice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Choice` class and add overrides to Generate a radio group
 */
class Radio extends Choice {

	/**
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		return 'choice';
	}

	/**
	 * List of icons the input type supports.
	 *
	 * @var array
	 */
	public $icons = array(
		'radio',
		'radio-checked',
	);
	/**
	 * List of styles the input type supports.
	 *
	 * @var array
	 */
	public static $styles = array(
		'fieldMargin'                => true,
		// 'fieldPadding'               => true,
		'inputMargin'                => true,
		'labelBorderStyle'           => true,
		'labelBorderRadius'          => true,
		'descriptionBorderStyle'     => true,
		'descriptionBorderRadius'    => true,

		'inputScale'                 => true,
		'inputLabelColor'            => true,
		'inputActiveIconColor'       => true,
		'inputInactiveIconColor'     => true,
		'inputOptionPadding'         => true,
		'inputOptionGap'             => true,
		'inputOptionIndentDepth'     => true,
		'inputIconSize'              => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						// Add extra padding to the left and right.
						'input-icon-size' => array(
							'value' => 'calc(1.25 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
				),
			),
		),

		'labelColor'                 => true,
		'labelBackgroundColor'       => true,
		'labelPadding'               => true,
		'labelMargin'                => true,
		'labelScale'                 => true,

		'descriptionColor'           => true,
		'descriptionBackgroundColor' => true,
		'descriptionPadding'         => true,
		'descriptionMargin'          => true,
		'descriptionScale'           => true,
	);

	/**
	 * The processed (cached) styles.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_styles    The processed styles, null if not processed yet.
	 */
	protected static $processed_styles = null;

	/**
	 * The input type name.
	 *
	 * @var string
	 */
	public static $input_type = 'radio';

	/**
	 * The type of field.
	 *
	 * @var string
	 */
	public static $type = 'choice';

	/**
	 * Supported settings.
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'                       => true,
		'width'                          => true,
		'queryId'                        => true,
		'stylesId'                       => true,
		'type'                           => true,
		'label'                          => true,
		'showLabel'                      => true,
		'showDescription'                => true,
		'description'                    => true,
		'inputType'                      => true,
		'dataType'                       => array(
			'values' => array(
				'post_attribute' => true,
				'taxonomy'       => true,
			),
		),
		'dataTaxonomy'                   => true,
		'dataPostAttribute'              => array(
			'values' => array(
				'post_type'   => true,
				'post_status' => true,
			),
		),
		'dataPostTypes'                  => true,
		'dataPostStati'                  => true,
		'dataTotalNumberOfOptions'       => true,
		'dataTotalNumberOfOptionsNotice' => true,
		'taxonomyHierarchical'           => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderBy'                => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderDir'               => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTermsConditions'        => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTerms'                  => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputOptionsOrder'              => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
		'taxonomyNavigatesArchive'       => true,
		'inputOptionsAddDefault'         => true,
		'hideEmpty'                      => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputOptionsDefaultLabel'       => true,
		'showCount'                      => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'showCountPosition'              => true,
		'showCountBrackets'              => true,
		'hideFieldWhenEmpty'             => true,
	);

	/**
	 * The processed (cached) setting support.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_setting_support    The processed settings, null if not processed yet.
	 */
	protected static $processed_setting_support = null;

	/**
	 * Get the label for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Radio', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by using radio buttons.', 'search-filter' );
	}
	/**
	 * Override the init_render_data and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {

		// Setup extra render data for options.
		$options = $this->get_options();
		$values  = $this->get_values();
		$value   = isset( $values[0] ) ? $values[0] : '';

		$render_data = array(
			// Important: we don't escape this later so ensure its escaped via this function.
			'options' => $this->prep_render_options( $options ),
		);
		$this->set_render_data( $render_data );
		$esc_callbacks = array(
			// Important - the only reason we don't clean `options` is because we're
			// already doing it via the `prep_render_options` function.
			'options' => 'Search_Filter\Core\Sanitize::esc_pass_through',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );
	}

	/**
	 * Prep the render options.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options The options.
	 *
	 * @return array The updated options.
	 */
	public function prep_render_options( &$options ) {
		foreach ( $options as &$option ) {
			$this->update_option( $option );
		}
		return $options;
	}

	/**
	 * Update the option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $option The option.
	 */
	public function update_option( &$option ) {

		// Important: we also need to escape each option as we're bypassing
		// the render_escape_callback for `options`.
		// Process children first if we have any.
		$option['options'] = isset( $option['options'] ) ? $this->prep_render_options( $option['options'] ) : array();

		$has_children = false;
		if ( count( $option['options'] ) > 0 ) {
			$has_children = true;
		}

		$values = $this->get_values();
		$value  = isset( $values[0] ) ? $values[0] : '';

		$checked_state = 'false';
		if ( $value === $option['value'] ) {
			$checked_state = 'true';
		}

		$svg_modifier = '';
		if ( $checked_state === 'true' ) {
			$svg_modifier = '-checked';
		}

		$type         = 'radio';
		$svg_link     = "#sf-svg-{$type}{$svg_modifier}";
		$is_active    = $checked_state === 'true'; // || $checked_state === 'mixed';
		$class_name   = 'search-filter-input-' . $type;
		$active_class = $is_active ? ' ' . $class_name . '--is-active' : '';

		// Important - due to the the fact we `pass_through` the `options` property when we set use set_render_escape_callbacks
		// then we must escape them here.
		$option['value']        = esc_attr( $option['value'] );
		$option['label']        = esc_html( $option['label'] );
		$option['checkedState'] = esc_attr( $checked_state ); // "true", "mixed", "false".
		$option['activeClass']  = esc_attr( $active_class );
		$option['svgLink']      = esc_attr( $svg_link );
		$option['isActive']     = boolval( $is_active );
		$option['uid']          = (int) self::get_instance_id( 'checkable' );
		// TODO: Unfortunately we need add a `hasChildren` prop to the option for our jsx template vars templates.
		// Remove once the jsx template vars supports using `count` in a a conditionally rendered statement.
		$option['hasChildren'] = (bool) $has_children;
	}
}
