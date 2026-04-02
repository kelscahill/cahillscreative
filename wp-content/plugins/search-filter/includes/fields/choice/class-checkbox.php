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
 * Extends `Choice` class and add overrides to Generate a Checkox Group
 */
class Checkbox extends Choice {

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
	 * List of supported styles for this field.
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
		'inputCheckboxTristate'      => true,
		'inputScale'                 => true,
		'inputLabelColor'            => true,
		'inputActiveIconColor'       => true,
		'inputInactiveIconColor'     => true,
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
		'inputOptionPadding'         => true,
		'inputOptionGap'             => true,
		'inputOptionIndentDepth'     => true,

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
	 * List of components this field relies on.
	 *
	 * @var array
	 */
	public $components = array(
		'checkbox',
	);

	/**
	 * List of icons the input type supports.
	 *
	 * @var array
	 */
	public $icons = array(
		'checkbox',
		'checkbox-checked',
		'checkbox-mixed',
	);

	/**
	 * The input type for this field.
	 *
	 * @var string
	 */
	public static $input_type = 'checkbox';

	/**
	 * The type of field.
	 *
	 * @var string
	 */
	public static $type = 'choice';

	/**
	 * List of setting support for this field.
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
		'multipleMatchMethod'            => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
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
		'hideEmpty'                      => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
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
		return __( 'Checkbox', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by choosing from checkboxes.', 'search-filter' );
	}

	/**
	 * Override the init_render_data and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {

		$options = $this->get_options();
		$values  = $this->get_values();
		// TODO - this should be moved upto checkable class and IDs need to use
		// a global ID generation function.

		$parent      = array();
		$render_data = array(
			// Important: we don't escape this later so ensure its escaped via this function.
			'options' => $this->prep_render_options( $options, $parent ),
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
	 * Update an individual option with checkbox-specific properties.
	 *
	 * @param array $option The option to update.
	 * @param array $parent_item The parent option.
	 */
	public function update_option( &$option, &$parent_item ) {

		// Important: we also need to escape each option as we're bypassing
		// the render_escape_callback for `options`.
		// Process children first if we have any.
		$option['options'] = isset( $option['options'] ) ? $this->prep_render_options( $option['options'], $option ) : array();

		$has_children = false;
		if ( isset( $option['options'] ) && count( $option['options'] ) > 0 ) {
			$has_children = true;
		}

		$values = $this->get_values();

		$checked_state = 'false';
		if ( ! $has_children ) {
			if ( in_array( $option['value'], $values, true ) ) {
				$checked_state = 'true';
			}
		} else {
			// Init child state tracking.
			if ( ! isset( $option['childStates'] ) ) {
				$option['childStates'] = array(
					'true'  => 0,
					'false' => 0,
					'mixed' => 0,
				);
			}
			// Now check to see:
			// - if it has children and they are all checked, set it to checked.
			// - if it has children and some are checked, set it to mixed.
			$no_of_options = count( $option['options'] );
			if ( $option['childStates']['true'] === $no_of_options ) {
				$checked_state = 'true';
			} elseif ( $option['childStates']['false'] === $no_of_options ) {
				$checked_state = 'false';
			} else {
				$checked_state = 'mixed';
			}
		}

		// If there is a parent, we need to update the parent's childStates and tell
		// it what the state of the current option is (so we can calc the parent's
		// true/false/mixed state).
		if ( ! empty( $parent_item ) ) {
			// $parent_data = $parent->get();
			if ( ! isset( $parent_item['childStates'] ) ) {
				$parent_item['childStates'] = array(
					'true'  => 0,
					'false' => 0,
					'mixed' => 0,
				);
			}
			if ( ! isset( $parent_item['childStates'][ $checked_state ] ) ) {
				$parent_item['childStates'][ $checked_state ] = 0;
			}
			++$parent_item['childStates'][ $checked_state ];
		}

		$type         = 'checkbox';
		$is_active    = $checked_state === 'true' || $checked_state === 'mixed';
		$class_name   = 'search-filter-input-' . $type;
		$active_class = $is_active ? ' ' . $class_name . '--is-active' : '';

		$svg_modifier = '';
		if ( $checked_state === 'true' ) {
			$svg_modifier = '-checked';
		} elseif ( $checked_state === 'mixed' ) {
			$svg_modifier = '-mixed';
		}

		$svg_link = "#sf-svg-{$type}{$svg_modifier}";

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

	/**
	 * Prepare render options by updating each option.
	 *
	 * @param array $options The options to prepare.
	 * @param array $parent_item  The parent option.
	 * @return array The prepared options.
	 */
	public function prep_render_options( &$options, &$parent_item ) {
		foreach ( $options as &$option ) {
			$this->update_option( $option, $parent_item );
		}
		return $options;
	}

	/**
	 * Gets the components array.
	 *
	 * Checkboxes are unique in that they have use the checkbox component when
	 * tristate is enabled, otherwise they fallback to build in handling.
	 *
	 * @return array The components array.
	 */
	public function get_components() {

		if ( $this->get_attribute( 'inputCheckboxTristate' ) !== 'no' ) {
			$components = array( 'checkbox' );
		} else {
			$components = array();
		}

		return apply_filters( 'search-filter/fields/field/get_components', $components, $this );
	}
}
