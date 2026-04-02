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
	 * List of styles the input type supports.
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'                  => true,
		// 'fieldPadding'                 => true,
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
		'inputPlaceholderColor'        => true,
		'inputSelectedColor'           => true,
		'inputSelectedBackgroundColor' => true,
		'inputBorder'                  => true,
		'inputBorderHoverColor'        => true,
		'inputBorderFocusColor'        => true,
		'inputBorderAccentColor'       => true,
		'inputBorderDivider'           => true,
		'inputIconColor'               => true,
		'inputInteractiveColor'        => true,
		'inputInteractiveHoverColor'   => true,
		'inputClearColor'              => true,
		'inputClearHoverColor'         => true,
		'inputShadow'                  => true,
		'inputPadding'                 => true,
		'inputSelectionGap'            => true,
		'inputTogglePadding'           => true,
		'inputToggleSize'              => true,
		'inputGap'                     => true,
		'inputClearSize'               => array(
			'conditions' => array(),
			'variation'  => array(
				'style' => array(
					'variables' => array(
						'input-clear-size' => array(
							'value' => 'var(--search-filter-scale-base-size)',
							'type'  => 'unit',
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

		'dropdownGap'                  => true,
		'dropdownAttachment'           => true,
		'dropdownScale'                => true,
		'dropdownMargin'               => true,
		'dropdownBorder'               => true,
		'dropdownBorderRadius'         => true,
		'dropdownOptionPadding'        => true,
		'dropdownOptionIndentDepth'    => true,
		'dropdownShadow'               => true,
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
		'combobox',
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
		'inputType'                      => true,
		'placeholder'                    => true,
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
		'showCountPosition'              => true,
		'showCountBrackets'              => true,
		'inputNoResultsText'             => true,
		'inputEnableSearch'              => true,
		'inputSingularResultsCountText'  => true,
		'inputPluralResultsCountText'    => true,
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
		return __( 'Select', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter from a dropdown list of options.', 'search-filter' );
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
			'placeholderText' => $this->get_attribute( 'placeholder' ),
		);

		// Init multiple.
		$multiple                = $this->get_attribute( 'multiple' ) === 'yes' ? true : false;
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
