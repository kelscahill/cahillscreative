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

	public static $styles = array(
		'inputColor',
		'inputActiveIconColor',
		'inputInactiveIconColor',

		'labelColor',
		'labelBackgroundColor',
		'labelPadding',
		'labelMargin',
		'labelScale',

		'descriptionColor',
		'descriptionBackgroundColor',
		'descriptionPadding',
		'descriptionMargin',
		'descriptionScale',
	);

	public $icons             = array(
		'checkbox',
		'checkbox-checked',
		'checkbox-mixed',
	);
	public static $input_type = 'checkbox';
	public static $type       = 'choice';

	public static $data_support = array(
		// Each entry is a group of settings that need to have certain conditions.
		array(
			'dataType'          => 'post_attribute',
			'dataPostAttribute' => array( 'post_type', 'post_status', 'post_author' ),
		),
		array(
			'dataType' => 'taxonomy',
		),
	);

	public static $setting_support = array(
		'showLabel'               => true,
		'multipleMatchMethod'     => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
		'dataLimitOptionsCount'   => true,
		'taxonomyHierarchical'    => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderBy'         => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderDir'        => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTermsConditions' => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTerms'           => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputOptionsOrder'       => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
		'hideEmpty'               => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'showCount'               => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'showCountPosition'       => true,
	);

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
	public function update_option( &$option, &$parent ) {

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
			// - if it has children and they are all checked, set it to checked
			// - if it has children and some are checked, set it to mixed
			// $checked_state = $is_checked ? 'true' : 'false';
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
		if ( ! empty( $parent ) ) {
			// $parent_data = $parent->get();
			if ( ! isset( $parent['childStates'] ) ) {
				$parent['childStates'] = array(
					'true'  => 0,
					'false' => 0,
					'mixed' => 0,
				);
			}
			if ( ! isset( $parent['childStates'][ $checked_state ] ) ) {
				$parent['childStates'][ $checked_state ] = 0;
			}
			++$parent['childStates'][ $checked_state ];
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
		$option['uid']          = esc_attr( self::get_instance_id( 'checkable' ) );
		// TODO: Unfortunately we need add a `hasChildren` prop to the option for our jsx template vars templates.
		// Remove once the jsx template vars supports using `count` in a a conditionally rendered statement.
		$option['hasChildren'] = esc_attr( $has_children );
	}
	public function prep_render_options( &$options, &$parent ) {
		foreach ( $options as &$option ) {
			$this->update_option( $option, $parent );
		}
		return $options;
	}
}
