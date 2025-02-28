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
	 * Supported data types.
	 *
	 * @var array
	 */
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

	/**
	 * Supported settings.
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'showLabel'               => true,
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
		'taxonomyFilterArchive'   => true,
		'inputOptionsAddDefault'  => true,
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
		return __( 'Radio', 'search-filter' );
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
		if ( isset( $option['options'] ) && count( $option['options'] ) > 0 ) {
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
		$is_active    = $checked_state === 'true' || $checked_state === 'mixed';
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
		$option['uid']          = esc_attr( self::get_instance_id( 'checkable' ) );
		// TODO: Unfortunately we need add a `hasChildren` prop to the option for our jsx template vars templates.
		// Remove once the jsx template vars supports using `count` in a a conditionally rendered statement.
		$option['hasChildren'] = esc_attr( $has_children );
	}
}
