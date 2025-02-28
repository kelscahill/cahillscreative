<?php
/**
 * Button Filter Class
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
 * Extends `Choice` class and add overrides to Generate a Button Group
 */
class Button extends Choice {

	public static $input_type = 'button';
	public static $type       = 'choice';

	public static $styles       = array(
		'inputColor',
		'inputBackgroundColor',
		'inputSelectedColor',
		'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputInteractiveColor',
		'inputInteractiveHoverColor',

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
		'showCountPosition'       => false,
	);

	public static function get_label() {
		return __( 'Button', 'search-filter' );
	}
	/**
	 * Override the init_render_data and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {
		// Setup extra render data for options.
		$options        = $this->get_options();
		$values         = $this->get_values();
		$render_options = array();

		// TOOD - this should be moved upto checkable class and IDs need to use
		// a global ID generation function.
		foreach ( $options as $option ) {
			$is_pressed = false;
			if ( in_array( $option['value'], $values, true ) ) {
				$is_pressed = true;
			}
			$render_options[] = array(
				'label'     => $option['label'],
				'isPressed' => $is_pressed,
			);
		}

		$render_data = array(
			'options' => $render_options,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'options' => array(
				'label'     => 'esc_html',
				'value'     => 'esc_attr',
				'isPressed' => 'boolval',
			),
		);
		$this->set_render_escape_callbacks( $esc_callbacks );
	}
}
