<?php
/**
 * Select Filter Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields\Choice;

use Search_Filter\Fields\Choice;

/**
 * Extends `Choice` class and add overrides to Generate a Select field
 */
class Select extends Choice {

	/**
	 * List of styles the input type supports.
	 *
	 * @var array
	 */
	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputSelectedColor',
		'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
		'inputInteractiveColor',
		'inputInteractiveHoverColor',
		'inputClearColor',
		'inputClearHoverColor',

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
	 * List of icons the input type supports.
	 *
	 * @var array
	 */
	public $icons = array(
		'arrow-down',
		'clear',
	);

	/**
	 * The input type name.
	 *
	 * @var string
	 */
	public static $input_type = 'select';

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
		// Each entry is seperate, only one entry needs to be matched to show the
		// input type.
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

		/*
		'dataType'          => array(
			'values' => array(
				'post_attribute',
				'taxonomy',
			),
		),
		'dataPostAttribute' => array(
			'values' => array(
				'post_type',
				'post_status',
			),
		),
		*/
		'showLabel'               => true,
		'placeholder'             => true,
		'multiple'                => true,
		'multipleMatchMethod'     => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
				array(
					'option'  => 'multiple',
					'compare' => '=',
					'value'   => 'yes',
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
		'inputOptionsAddDefault'  => array(
			'conditions' => array(
				array(
					'option'  => 'multiple',
					'compare' => '!=',
					'value'   => 'yes',
				),
			),
		),
		'taxonomyFilterArchive'   => array(
			'conditions' => array(
				array(
					'option'  => 'multiple',
					'compare' => '!=',
					'value'   => 'yes',
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
		'inputShowIcon'           => true,
	);

	/**
	 * Get the conditions for the data type.
	 *
	 * @since 3.0.0
	 *
	 * @return array The conditions.
	 */
	public static function data_conditions() {
		return array(
			array(
				'option'  => 'multiple',
				'compare' => '=',
				'value'   => 'yes',
			),
		);
	}

	/**
	 * Get the label for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Select', 'search-filter' );
	}

	/**
	 * Override the default attributes
	 *
	 * @param array $defaults The default field attributes.
	 * @return array The new defaults
	 */
	public function get_default_attributes( $defaults = array() ) {
		$defaults                = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/choice' );
		$defaults['type']        = 'choice';
		$defaults['placeholder'] = '';
		$defaults['multiple']    = 'no';
		$defaults                = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );

		return wp_parse_args( $defaults, $defaults );
	}
	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {

		// Defaults.
		$render_data = array(
			'options'         => array(),
			'statusText'      => '',
			'selectionLabel'  => '',
			'multiple'        => false,
			'selection'       => array(),
			// 'inputScale'            => $this->attributes['inputScale'],
			'placeholderText' => $this->attributes['placeholder'],
		);

		// Init multiple.
		$multiple                = $this->attributes['multiple'] === 'yes' ? true : false;
		$render_data['multiple'] = $multiple;

		// Set options.
		// TODO - since we cant't display the dropdown until JS is loaded, is this even necessary?
		$options                = $this->get_options();
		$render_data['options'] = $options;

		$selected_options = array();
		if ( $multiple ) {
			foreach ( $options as $option ) {
				if ( in_array( $option['value'], $this->get_values(), true ) ) {
					$selected_options[] = $option;
				}
			}
			$render_data['selection'] = $selected_options;
			if ( count( $selected_options ) > 0 ) {
				$render_data['placeholderText'] = '';
			}
		} else {
			foreach ( $options as $option ) {
				if ( in_array( $option['value'], $this->get_values(), true ) ) {
					$render_data['selectionLabel'] = $option['label'];
				}
			}
			if ( $render_data['selectionLabel'] !== '' ) {
				$render_data['placeholderText'] = '';
			}
		}

		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'options'         => array(
				'label' => 'esc_html',
				'value' => 'esc_attr',
				'depth' => 'intval',
			),
			'placeholderText' => 'esc_attr',
			'statusText'      => 'esc_html',
			'selectionLabel'  => 'esc_html',
			'multiple'        => 'boolval',
			'selection'       => array(
				'label' => 'esc_html',
				'value' => 'esc_attr',
			),
			'inputScale'      => 'sanitize_key',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );
	}
}
