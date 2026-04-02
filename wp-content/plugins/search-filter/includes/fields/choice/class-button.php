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
	 * The input type for this field.
	 *
	 * @var string
	 */
	public static $input_type = 'button';

	/**
	 * The type of field.
	 *
	 * @var string
	 */
	public static $type = 'choice';

	/**
	 * List of supported styles for this field.
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'                  => true,
		// 'fieldPadding'               => true,
		'inputMargin'                  => true,
		'labelBorderStyle'             => true,
		'labelBorderRadius'            => true,
		'descriptionBorderStyle'       => true,
		'descriptionBorderRadius'      => true,
		'inputClearPadding'            => true,
		'inputBorderRadius'            => true,

		'inputScale'                   => true,
		'inputColor'                   => true,
		'inputBackgroundColor'         => true,
		'inputSelectedColor'           => true,
		'inputSelectedBackgroundColor' => true,
		'inputBorder'                  => true,
		'inputBorderHoverColor'        => true,
		'inputBorderFocusColor'        => true,
		'inputInteractiveColor'        => true,
		'inputInteractiveHoverColor'   => true,
		'inputShadow'                  => true,
		'inputPadding'                 => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						// Add extra padding to the left and right.
						'input-padding-right' => array(
							'value' => 'calc(0.6 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-padding-left'  => array(
							'value' => 'calc(0.6 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
		),
		// Add spacing between the buttons.
		'inputGap'                     => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						'input-gap' => array(
							'value' => 'calc(0.45 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
		),
		'labelColor'                   => true,
		'labelBackgroundColor'         => true,
		'labelPadding'                 => true,
		'labelMargin'                  => true,
		'labelScale'                   => true,

		'descriptionColor'             => true,
		'descriptionBackgroundColor'   => true,
		'descriptionPadding'           => true,
		'descriptionMargin'            => true,
		'descriptionScale'             => true,
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
		'multiple'                       => true,
		'multipleMatchMethod'            => array(
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
		'inputOptionsAddDefault'         => array(
			'conditions' => array(
				array(
					'option'  => 'multiple',
					'compare' => '!=',
					'value'   => 'yes',
				),
			),
		),
		'inputOptionsDefaultLabel'       => true,
		'taxonomyNavigatesArchive'       => array(
			'conditions' => array(
				array(
					'option'  => 'multiple',
					'compare' => '!=',
					'value'   => 'yes',
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
		'showCountPosition'              => false,
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
	 * Get the label for this field type.
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Button', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by choosing options from a button group.', 'search-filter' );
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
