<?php
/**
 * Field settings.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found in fields
 */
class Settings_Data {

	/**
	 * Returns all the settings.
	 *
	 * @return array
	 */
	public static function get() {
		$settings_data = array();
		// Order is important (currently this is the display order).
		$settings_data = array_merge( $settings_data, self::get_basic_settings() );
		$settings_data = array_merge( $settings_data, self::get_text_settings() );
		$settings_data = array_merge( $settings_data, self::get_post_type_choice_settings() );
		$settings_data = array_merge( $settings_data, self::get_post_status_choice_settings() );
		$settings_data = array_merge( $settings_data, self::get_choice_settings() );
		$settings_data = array_merge( $settings_data, self::get_taxonomy_filter_settings() );
		$settings_data = array_merge( $settings_data, self::get_range_settings() );
		$settings_data = array_merge( $settings_data, self::get_shared_settings() );
		$settings_data = array_merge( $settings_data, self::get_design_settings() );
		$settings_data = array_merge( $settings_data, self::get_layout_settings() );

		return $settings_data;
	}
	public static function get_groups() {
		$groups_data = array(
			array(
				'name'  => 'general',
				'label' => __( 'General', 'search-filter' ),
			),
			array(
				'name'  => 'query',
				'label' => __( 'Query integration', 'search-filter' ),
			),
			array(
				'name'  => 'styles',
				'label' => __( 'Styles', 'search-filter' ),
			),
			array(
				'name'  => 'data',
				'label' => __( 'Data', 'search-filter' ),
			),
			array(
				'name'  => 'control-type',
				'label' => __( 'Type', 'search-filter' ),
			),
			array(
				'name'  => 'input',
				'label' => __( 'Input', 'search-filter' ),
			),
			array(
				'name'  => 'field-dimensions',
				'label' => __( 'Field Dimensions', 'search-filter' ),
				'type'  => 'tools-panel',
			),
			array(
				'name'  => 'input-dimensions',
				'label' => __( 'Input Dimensions', 'search-filter' ),
				'type'  => 'tools-panel',
			),
			array(
				'name'  => 'input-colors',
				'label' => __( 'Input Colors', 'search-filter' ),
				'type'  => 'color-panel',
			),
			array(
				'name'  => 'options',
				'label' => __( 'Options', 'search-filter' ),
			),
			array(
				'name'  => 'label',
				'label' => __( 'Label', 'search-filter' ),
			),
			array(
				'name'  => 'label-dimensions',
				'label' => __( 'Label Dimensions', 'search-filter' ),
				'type'  => 'tools-panel',
			),
			array(
				'name'  => 'label-color',
				'label' => __( 'Label Color', 'search-filter' ),
				'type'  => 'color-panel',
			),
			array(
				'name'  => 'description',
				'label' => __( 'Description', 'search-filter' ),
			),
			array(
				'name'  => 'description-dimensions',
				'label' => __( 'Description Dimensions', 'search-filter' ),
				'type'  => 'tools-panel',
			),
			array(
				'name'  => 'description-color',
				'label' => __( 'Description Color', 'search-filter' ),
				'type'  => 'color-panel',
			),
			array(
				'name'  => 'dimensions',
				'label' => __( 'Dimensions', 'search-filter' ),
			),
			array(
				'name'  => 'behaviour',
				'label' => __( 'Behaviour', 'search-filter' ),
			),
			array(
				'name'  => 'advanced',
				'label' => __( 'Advanced', 'search-filter' ),
			),
		);
		return $groups_data;
	}
	/**
	 * Get the basic settings for all fields.
	 */
	public static function get_basic_settings() {

		$settings = array(

			/*
				Query integration
			*/

			array(
				'name'         => 'queryId',
				'label'        => __( 'Query', 'search-filter' ),
				'group'        => 'query',
				'tab'          => 'settings',
				'default'      => '',
				'help'         => __( 'Connect with a query. The query settings may affect available options.', 'search-filter' ),
				'type'         => 'string',
				'inputType'    => 'QuerySelect',
				'placeholder'  => __( 'Choose a saved query', 'search-filter' ),
				'context'      => array( 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dataProvider' => array(
					'route'   => '/settings/options/queries',
					'preload' => true,
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
			/*
			array(
				'name'        => 'name',
				'label'       => __( 'Name', 'search-filter' ),
				'help'        => __( 'The name is not displayed on the frontend, it is only used for referencing.', 'search-filter' ),
				'group'       => 'general',
				'default'     => __( 'New field', 'search-filter' ),
				'type'        => 'string',
				'inputType'   => 'Text',
				// 'restrict' => '^\S+\w\S',
				'placeholder' => __( 'Enter a field name', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/search', 'admin/field/filter', 'admin/field/control' ),
			),*/
			array(
				'name'        => 'type',
				'isReserved'  => true,
				'label'       => __( 'Field Type', 'search-filter' ),
				'group'       => 'general',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Hidden',
				'default'     => 'choice',
				'placeholder' => __( 'Select a Field Type', 'search-filter' ),
				'help'        => __( 'Changing this option will reset your data and input attributes.', 'search-filter' ),

				'context'     => array( 'admin/field', 'admin/field/search', 'admin/field/choice', 'admin/field/range', 'admin/field/advanced', 'admin/field/control' ),
				'options'     => array(
					array(
						'label' => __( 'Search', 'search-filter' ),
						'value' => 'search',
						'icon'  => 'search',
					),
					array(
						'label' => __( 'Choice', 'search-filter' ),
						'value' => 'choice',
						'icon'  => 'choice',
					),
					array(
						'label' => __( 'Range', 'search-filter' ),
						'value' => 'range',
						'icon'  => 'range',
					),
					array(
						'label' => __( 'Advanced', 'search-filter' ),
						'value' => 'advanced',
						'icon'  => 'advanced',
					),
					array(
						'label' => __( 'Control', 'search-filter' ),
						'value' => 'control',
						'icon'  => 'control',
					),
				),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'        => 'label',
				'isReserved'  => true,
				'label'       => __( 'Label', 'search-filter' ),
				'group'       => 'label',
				'help'        => __( 'If the label is not shown it will still be available to screen readers', 'search-filter' ),
				'tab'         => 'settings',
				'default'     => __( 'New field', 'search-filter' ),
				'type'        => 'string',
				'inputType'   => 'Text',
				'placeholder' => __( 'Enter a Label', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),

			),
			array(
				'name'      => 'showLabel',
				'label'     => __( 'Show label', 'search-filter' ),
				'group'     => 'label',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
			),
			array(
				'name'             => 'labelScale',
				'label'            => __( 'Scale', 'search-filter' ),
				'group'            => 'label-dimensions',
				'tab'              => 'styles',
				'stylesDefault'    => 2,
				'allowEmpty'       => true,
				'type'             => 'number',
				'inputType'        => 'Range',
				'context'          => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder'      => __( 'Choose a scale', 'search-filter' ),
				'isShownByDefault' => false,
				'min'              => 1,
				'max'              => 10,
				'step'             => 1,
				'dependsOn'        => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'          => 'labelColor',
				'label'         => __( 'Color', 'search-filter' ),
				'group'         => 'label-color',
				'tab'           => 'styles',
				'stylesDefault' => '#3c434a',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'          => 'labelBackgroundColor',
				'label'         => __( 'Background Color', 'search-filter' ),
				'group'         => 'label-color',
				'tab'           => 'styles',
				'stylesDefault' => '',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),

			array(
				'name'       => 'labelPadding',
				'label'      => __( 'Padding', 'search-filter' ),
				'group'      => 'label-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'  => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'          => 'labelMargin',
				'label'         => __( 'Margin', 'search-filter' ),
				'group'         => 'label-dimensions',
				'tab'           => 'styles',
				'stylesDefault' => array(
					'top'    => '0',
					'right'  => '0',
					'bottom' => '8px',
					'left'   => '0',
				),
				'allowEmpty'    => true,
				'type'          => 'object',
				'inputType'     => 'Dimension',
				'sides'         => array( 'top', 'bottom' ),
				'allowReset'    => false,
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			// Description
			array(
				'name'       => 'showDescription',
				'isReserved' => true,
				'label'      => __( 'Show description', 'search-filter' ),
				'group'      => 'description',
				'tab'        => 'settings',
				'default'    => 'no',
				'type'       => 'string',
				'inputType'  => 'Toggle',
				'context'    => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'options'    => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
			),
			array(
				'name'        => 'description',
				'isReserved'  => true,
				'label'       => __( 'Description', 'search-filter' ),
				'group'       => 'description',
				'tab'         => 'settings',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Text',
				'placeholder' => __( 'Enter a description', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),

			array(
				'name'             => 'descriptionScale',
				'label'            => __( 'Scale', 'search-filter' ),
				'group'            => 'description-dimensions',
				'tab'              => 'styles',
				'stylesDefault'    => 2,
				'allowEmpty'       => true,
				'type'             => 'number',
				'inputType'        => 'Range',
				'context'          => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder'      => __( 'Choose a scale', 'search-filter' ),
				'min'              => 1,
				'isShownByDefault' => false,

				'max'              => 10,
				'step'             => 1,
				'dependsOn'        => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'       => 'descriptionPadding',
				'label'      => __( 'Padding', 'search-filter' ),
				'group'      => 'description-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'  => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'          => 'descriptionMargin',
				'label'         => __( 'Margin', 'search-filter' ),
				'group'         => 'description-dimensions',
				'tab'           => 'styles',
				'stylesDefault' => array(
					'top'    => '0',
					'right'  => '0',
					'bottom' => '8px',
					'left'   => '0',
				),
				'allowEmpty'    => true,
				'type'          => 'object',
				'inputType'     => 'Dimension',
				'sides'         => array( 'top', 'bottom' ),
				'allowReset'    => false,
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),

			array(
				'name'          => 'descriptionColor',
				'label'         => __( 'Color', 'search-filter' ),
				'group'         => 'description-color',
				'tab'           => 'styles',
				'stylesDefault' => '#3c434a',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'          => 'descriptionBackgroundColor',
				'label'         => __( 'Background Color', 'search-filter' ),
				'group'         => 'description-color',
				'tab'           => 'styles',
				'stylesDefault' => '',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'dependsOn'     => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showDescription',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),

			array(
				'name'        => 'dataType',
				'isReserved'  => true,
				'label'       => __( 'Data Type', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Select',
				'placeholder' => __( 'Select a Data Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'     => array(
					array(
						'label'     => __( 'Post Attributes', 'search-filter' ),
						'value'     => 'post_attribute',
						'dependsOn' => array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'type',
									'value'   => 'range',
									'compare' => '!=',
								),
							),
						),
					),
					array(
						'label'     => __( 'Taxonomy', 'search-filter' ),
						'value'     => 'taxonomy',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'type',
									'value'   => 'range',
									'compare' => '!=',
								),
								array(
									'option'  => 'type',
									'value'   => 'advanced',
									'compare' => '!=',
								),
							),
						),
					),
				),
				'isDataType'  => true,
				'supports'    => array(
					'previewAPI'       => true,
					'dependantOptions' => true,
				),
			),
			array(
				'name'        => 'dataPostAttribute',
				'label'       => __( 'Data Source', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Select',
				'placeholder' => __( 'Choose Post Attributes source', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'     => array(
					array(
						'label'     => __( 'Post Type', 'search-filter' ),
						'value'     => 'post_type',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'type',
									'value'   => 'advanced',
									'compare' => '!=',
								),
							),
						),
					),
					array(
						'label'     => __( 'Post Status', 'search-filter' ),
						'value'     => 'post_status',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'type',
									'value'   => 'advanced',
									'compare' => '!=',
								),
							),
						),
					),
					array(
						'label'     => __( 'Published Date', 'search-filter' ),
						'value'     => 'post_published_date',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'type',
									'value'   => 'advanced',
									'compare' => '=',
								),
							),
						),
					),
				),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),

					),
				),
				'isDataType'  => true,
				'supports'    => array(
					'previewAPI'       => true,
					'dependantOptions' => true,
				),
			),
			array(
				'name'         => 'dataTaxonomy',
				'label'        => __( 'Taxonomy', 'search-filter' ),
				'help'         => __( "If you don't see your taxonomy - check that it is set to public and it is available for your post type.", 'search-filter' ),
				'group'        => 'data',
				'tab'          => 'settings',
				'type'         => 'string',
				'inputType'    => 'Select',
				'placeholder'  => __( 'Choose a Taxonomy', 'search-filter' ),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'      => array(),
				'dataProvider' => array(
					'route' => '/settings/options/taxonomies',
					'args'  => array(
						'queryId',
					),
				),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'taxonomy',
						),
					),
				),
				'isDataType'   => true,
				'supports'     => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'        => 'controlType',
				'isReserved'  => true,
				'label'       => 'Control Type',
				'group'       => 'control-type',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Select',
				'placeholder' => __( 'Choose a Control Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/control', 'block/field/control' ),
				'options'     => array(),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'type',
							'compare' => '=',
							'value'   => 'control',
						),
					),
				),
				'supports'    => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'        => 'inputType',
				'isReserved'  => true,
				'label'       => __( 'Input Type', 'search-filter' ),
				'help'        => __( 'Choose the type of input control.', 'search-filter' ) . "\n" . __( 'Your data settings will affect the input types that are available', 'search-filter' ),
				'group'       => 'input',
				'tab'         => 'settings',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Select',
				'placeholder' => __( 'Select an Input Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' /* 'admin/field/control', 'block/field/control' */ ),
				'options'     => array(),
				'supports'    => array(
					'dependantOptions' => true,
					'previewAPI'       => true,
				),
			),
		);

		return $settings;
	}

	public static function get_text_settings() {
		$settings = array(
			array(
				'name'      => 'placeholder',
				'label'     => __( 'Placeholder text', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => '',
				'type'      => 'string',
				'inputType' => 'Text',
				'help'      => __( 'The text that appears inside the field before a user has typed anything', 'search-filter' ),
				'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
		);

		return $settings;
	}

	public static function get_choice_settings() {
		$settings = array(
			array(
				'name'      => 'multiple',
				'label'     => __( 'Multiple selection', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
			),
			array(
				'name'      => 'multipleMatchMethod',
				'label'     => __( 'Match mode', 'search-filter' ),
				'help'      => __( 'Choose whether results must match all the selected options or just one of them.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Match all', 'search-filter' ),
						'value' => 'all',
					),
					array(
						'label' => __( 'Match any', 'search-filter' ),
						'value' => 'any',
					),
				),
			),
			array(
				'name'      => 'inputOptionsAddDefault',
				'label'     => __( 'Add a default option', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'inputOptionsDefaultLabel',
				'label'     => __( 'Default option label', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => __( 'All items', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Text',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'inputOptionsAddDefault',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'hideEmpty',
				'label'     => __( 'Hide options with no results', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'showCount',
				'label'     => __( 'Show the count number', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'showCountBrackets',
				'label'     => __( 'Show brackets around the count', 'search-filter' ),
				'notice'    => __( 'Count numbers in the preview may not be accurate.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showCount',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'showCountPosition',
				'label'     => __( 'Count position', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'inline',
				'type'      => 'string',
				'inputType' => 'ButtonGroup',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Inline', 'search-filter' ),
						'value' => 'inline',
					),
					array(
						'label' => __( 'Space between', 'search-filter' ),
						'value' => 'space-between',
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'showCount',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),
			array(
				'name'        => 'dataTotalNumberOfOptions',
				'label'       => __( 'Limit number of options', 'search-filter' ),
				'group'       => 'input',
				'tab'         => 'settings',
				'default'     => '30',
				'inputType'   => 'Number',
				'min'         => 1,
				'step'        => 1,
				'max'         => 50,
				'type'        => 'string',
				'placeholder' => __( '', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice' ),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
			// Add ordering to custom field options.
			array(
				'name'      => 'inputOptionsOrder',
				'label'     => __( 'Order Options', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label'     => __( 'Inherit', 'search-filter' ),
						'value'     => 'inherit',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'post_attribute',
								),
								// TODO - move into WC class.
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'woocommerce',
								),
							),
						),
					),
					array(
						'label'     => __( 'Alphabetically', 'search-filter' ),
						'value'     => 'alphabetical',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'post_attribute',
								),
								// TODO - move into WC class.
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'woocommerce',
								),
							),
						),
					),
					array(
						'label'     => __( 'Numerically', 'search-filter' ),
						'value'     => 'numerical',
						'dependsOn' => array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'post_attribute',
								),
								// TODO - move into WC class.
								array(
									'option'  => 'dataType',
									'compare' => '!=',
									'value'   => 'woocommerce',
								),
							),
						),
					),
					array(
						'label'     => __( 'Label', 'search-filter' ),
						'value'     => 'label',
						'dependsOn' => array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'dataType',
									'compare' => '=',
									'value'   => 'post_attribute',
								),
								// TODO - move into WC class.
								array(
									'option'  => 'dataType',
									'compare' => '=',
									'value'   => 'woocommerce',
								),
							),
						),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '!=',
							'value'   => 'taxonomy',
						),
					),
				),
				'supports'  => array(
					'previewAPI'       => true,
					'dependantOptions' => true,
				),
			),
			array(
				'name'      => 'inputOptionsOrderDir',
				'label'     => __( 'Order Direction', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Ascending', 'search-filter' ),
						'value' => 'asc',
					),
					array(
						'label' => __( 'Descending', 'search-filter' ),
						'value' => 'desc',
					),
				),
				// Faster to depend on the existence of another field, only 1 check needed.
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '!=',
							'value'   => 'taxonomy',
						),
						array(
							'option'  => 'inputOptionsOrder',
							'compare' => '!=',
							'value'   => 'inherit',
						),
					),
				),
				'supports'  => array(
					'previewAPI'       => true,
					'dependantOptions' => true,
				),
			),

			// Add custom options to sort field.
			array(
				'name'      => 'sortOptions',
				'label'     => __( 'Sort Options', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => array(),
				'type'      => 'array',
				'items'     => array(
					'type' => 'object',
				),
				'inputType' => 'SortOrder',
				'context'   => array( 'admin/field/control', 'block/field/control' ),
				'options'   => array(
					array(
						'value' => 'inherit',
						'label' => __( 'Inherit', 'search-filter' ),
					),
					array(
						'value' => 'ID',
						'label' => __( 'ID', 'search-filter' ),
					),
					array(
						'value' => 'author',
						'label' => __( 'Author', 'search-filter' ),
					),
					array(
						'value' => 'title',
						'label' => __( 'Title', 'search-filter' ),
					),
					array(
						'value' => 'name',
						'label' => __( 'Slug', 'search-filter' ),
					),
					array(
						'value' => 'type',
						'label' => __( 'Post Type', 'search-filter' ),
					),
					array(
						'value' => 'date',
						'label' => __( 'Published Date', 'search-filter' ),
					),
					array(
						'value' => 'modified',
						'label' => __( 'Modified Date', 'search-filter' ),
					),
					array(
						'value' => 'parent',
						'label' => __( 'Parent ID', 'search-filter' ),
					),
					array(
						'value' => 'comment_count',
						'label' => __( 'Comment Count', 'search-filter' ),
					),
					array(
						'value' => 'relevance',
						'label' => __( 'Relevance', 'search-filter' ),
					),
					array(
						'value' => 'menu_order',
						'label' => __( 'Menu Order', 'search-filter' ),
					),
					array(
						'value' => 'post__in',
						'label' => __( 'Restricted Posts Order', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'controlType',
							'compare' => '=',
							'value'   => 'sort',
						),
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			/*
			array(
				'name'        => 'dataShowMoreOptions',
				'label'       => __( 'Show "View more"', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'default'     => 'yes',
				'inputType'   => 'Select',
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'     => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'placeholder' => __( '', 'search-filter' ),
				'dependsOn'   => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'dataLimitOptionsCount',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			),

			array(
				'name'        => 'dataShowMoreOptionsLabel',
				'label'       => __( 'View More Label', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'default'     => __( 'View more', 'search-filter' ),
				'inputType'   => 'Text',
				'placeholder' => __( 'Enter an "All Items" Label', 'search-filter' ),
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'   => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'dataShowMoreOptions',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
			), */
		);

		return $settings;
	}
	public static function get_taxonomy_filter_settings() {
		$settings = array(
			array(
				'name'      => 'taxonomyHierarchical',
				'label'     => __( 'Hierarchical', 'search-filter' ),
				'help'      => __( 'Indent child terms for hierarchical taxonomies', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'limitTaxonomyDepth',
				'label'     => __( 'Limit hierarchical depth', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				// 'help'      => __( 'Limit how many levels to show.', 'search-filter' ),
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'taxonomyHierarchical',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'        => 'taxonomyDepth',
				'label'       => __( 'Hierarchical depth', 'search-filter' ),
				// 'help'        => __( 'Restrict terms to a hierarchical depth. Leave as `0` to display all depths.', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'default'     => '1',
				'type'        => 'string',
				'inputType'   => 'Number',
				'placeholder' => '1',
				'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'min'         => 1,
				'step'        => 1,
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'taxonomyHierarchical',
							'compare' => '=',
							'value'   => 'yes',
						),
						array(
							'option'  => 'limitTaxonomyDepth',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'taxonomyFilterArchive',
				'label'     => __( 'Use archive URL', 'search-filter' ),
				'help'      => __( 'Enabling this option will use the taxonomy archive URL', 'search-filter' ),
				'group'     => 'behaviour',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'dataType',
									'compare' => '=',
									'value'   => 'taxonomy',
								),
								array(
									'store'   => 'query',
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'archive',
								),
								array(
									'store'   => 'query',
									'option'  => 'archiveType',
									'compare' => '=',
									'value'   => 'post_type',
								),
								array(
									'store'   => 'query',
									'option'  => 'archiveFilterTaxonomies',
									'compare' => '=',
									'value'   => 'yes',
								),
							),
						),
					),
				),
			),
			array(
				'name'      => 'taxonomyOrderBy',
				'label'     => __( 'Order Terms By', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Default', 'search-filter' ),
						'value' => 'default',
					),
					array(
						'label' => __( 'ID', 'search-filter' ),
						'value' => 'id',
					),
					array(
						'label' => __( 'Name', 'search-filter' ),
						'value' => 'name',
					),
					array(
						'label' => __( 'Slug', 'search-filter' ),
						'value' => 'slug',
					),
					array(
						'label' => __( 'Count', 'search-filter' ),
						'value' => 'count',
					),
					array(
						'label' => __( 'Term Group', 'search-filter' ),
						'value' => 'term_group',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'taxonomyOrderDir',
				'label'     => __( 'Order Direction', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Inherit', 'search-filter' ),
						'value' => 'inherit',
					),
					array(
						'label' => __( 'Ascending', 'search-filter' ),
						'value' => 'asc',
					),
					array(
						'label' => __( 'Descending', 'search-filter' ),
						'value' => 'desc',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'      => 'taxonomyTermsConditions',
				'label'     => __( 'Terms conditions', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Select',
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'all',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Show all terms', 'search-filter' ),
						'value' => 'all',
					),
					array(
						'label' => __( 'Restrict terms', 'search-filter' ),
						'value' => 'include_terms',
					),
					array(
						'label' => __( 'Exclude terms', 'search-filter' ),
						'value' => 'exclude_terms',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'         => 'taxonomyTerms',
				'label'        => __( 'Taxonomy terms', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'number',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array(),
				'context'      => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'taxonomyTermsConditions',
							'compare' => '=',
							'value'   => 'include_terms',
						),
						array(
							'option'  => 'taxonomyTermsConditions',
							'compare' => '=',
							'value'   => 'exclude_terms',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/taxonomy-terms',
					'args'  => array(
						'dataTaxonomy',
					),
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
		);

		return $settings;
	}

	public static function get_post_type_choice_settings() {
		$settings = array(
			array(
				'name'         => 'dataPostTypes',
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'default'      => array(),
				'inputType'    => 'MultiSelect',
				'tab'          => 'settings',
				'label'        => __( 'Posts Types', 'search-filter' ),
				'placeholder'  => __( 'Choose post types', 'search-filter' ),
				'help'         => __( 'Leave empty to choose all available post types.  Available post types are controlled by your query settings.', 'search-filter' ),
				'group'        => 'data',
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'      => array(),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_type',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/post-types',
					'args'  => array(
						'queryId',
					),
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),

		);
		return $settings;
	}
	public static function get_post_status_choice_settings() {
		$settings = array(
			array(
				'name'         => 'dataPostStati',
				'label'        => __( 'Post Status', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array( 'publish' ),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_status',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/post-stati',
					'args'  => array(
						'queryId',
					),
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),

		);

		return $settings;
	}



	public static function get_range_settings() {

		$settings = array();

		return $settings;
	}


	public static function get_shared_settings() {

		$settings = array(

			/*
			date stuff */
			/*
			array(
				'name'        => 'default_date',
				'label'       => __( 'Default Date', 'search-filter' ),
				'group'         => 'data',
				'default'     => 'none',
				'inputType'        => 'Select',
				'placeholder' =>  __( 'Choose a default date', 'search-filter' ),
				'options'     => array(
					array(
						'label' => __( 'None', 'search-filter' ),
						'value' => 'none',
					),
					array(
						'label' => __( 'Today', 'search-filter' ),
						'value' => 'today',
					),
					array(
						'label' => __( 'Tomorrow', 'search-filter' ),
						'value' => 'tomorrow',
					),
					array(
						'label' => __( 'Yesterday', 'search-filter' ),
						'value' => 'tomorrow',
					),
					array(
						'label' => __( 'Custom', 'search-filter' ),
						'value' => 'custom',
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					array(
						'option'  => 'dataPostAttribute',
						'compare' => '=',
						'value'   => 'post_published_date',
					),
				),
			), */
			array(
				'name'        => 'dateDisplayFormat',
				'label'       => __( 'Display format', 'search-filter' ),
				'group'       => 'input',
				'tab'         => 'settings',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Select',
				'context'     => array( 'admin/field', 'admin/field/advanced', 'block/field/advanced' ),
				'placeholder' => __( 'Choose a date format for displaying', 'search-filter' ),

				'options'     => array(
					array(
						'label' => 'F j, Y',
						'value' => 'F j, Y',
					),
					array(
						'label' => 'Y-m-d',
						'value' => 'Y-m-d',
					),
					array(
						'label' => 'm/d/Y',
						'value' => 'm/d/Y',
					),
					array(
						'label' => 'd/m/Y',
						'value' => 'd/m/Y',
					),
					array(
						'label' => 'Custom',
						'value' => 'custom',
					),
				),
			),
			array(
				'name'        => 'dateDisplayFormatCustom',
				'label'       => 'Custom format',
				'group'       => 'input',
				'tab'         => 'settings',
				'default'     => 'F j, Y',
				'type'        => 'string',
				'inputType'   => 'Text',
				'context'     => array( 'admin/field', 'admin/field/advanced', 'block/field/advanced' ),
				'placeholder' => __( 'Enter a custom format for displaying the date.', 'search-filter' ),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dateDisplayFormat',
							'value'   => 'custom',
							'compare' => '=',
						),
					),
				),
			),
			array(
				'name'      => 'dateShowMonth',
				'label'     => __( 'Show Month Dropdown', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
			),
			array(
				'name'      => 'dateShowYear',
				'label'     => __( 'Show Year Dropdown', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
			),
			array(
				'name'        => 'addClass',
				'label'       => __( 'Add a class', 'search-filter' ),
				'help'        => __( 'Separate class names with a space', 'search-filter' ),
				'default'     => '',
				'group'       => 'advanced',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Text',
				'context'     => array( 'admin/field', 'admin/field/search', 'admin/field/choice', 'admin/field/range', 'admin/field/advanced', 'admin/field/control' ),
				'placeholder' => __( 'Add a CSS class to the field', 'search-filter' ),
			),
		);

		return $settings;
	}

	public static function get_design_settings() {

		$settings = array(
			array(
				'name'         => 'stylesId',
				'label'        => __( 'Styles preset', 'search-filter' ),
				'group'        => 'styles',
				'tab'          => 'styles',
				'default'      => '0',
				'help'         => __( 'Styles are inherited from the selected preset.', 'search-filter' ),
				'type'         => 'string',
				'inputType'    => 'StylesSelect',
				'context'      => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder'  => __( 'Select styles to apply', 'search-filter' ),
				'dataProvider' => array(
					'route'   => '/settings/options/styles',
					'preload' => true,
				),
			),
			array(
				'name'             => 'inputScale',
				'label'            => __( 'Scale', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'stylesDefault'    => 2,
				'allowEmpty'       => true,
				'type'             => 'number',
				'inputType'        => 'Range',
				'context'          => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder'      => __( 'Choose a scale', 'search-filter' ),
				'min'              => 1,
				'max'              => 10,
				'step'             => 1,
				'isShownByDefault' => true,
			),
			array(
				'name'             => 'width',
				'label'            => __( 'Width', 'search-filter' ),
				'group'            => 'dimensions',
				'tab'              => 'settings',
				'default'          => '',
				'type'             => 'string',
				'inputType'        => 'ButtonGroup',
				'context'          => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'requireSelection' => false,
				'options'          => array(
					array(
						'label' => __( '25%', 'search-filter' ),
						'value' => '25',
					),
					array(
						'label' => __( '50%', 'search-filter' ),
						'value' => '50',
					),
					array(
						'label' => __( '75%', 'search-filter' ),
						'value' => '75',
					),
					array(
						'label' => __( '100%', 'search-filter' ),
						'value' => '100',
					),
				),
			),
			array(
				'name'      => 'inputShowIcon',
				'label'     => __( 'Show icon', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'type',
									'compare' => '=',
									'value'   => 'advanced',
								),
								array(
									'option'  => 'type',
									'compare' => '=',
									'value'   => 'search',
								),
							),
						),
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'inputType',
									'compare' => '=',
									'value'   => 'text',
								),
								array(
									'option'  => 'inputType',
									'compare' => '=',
									'value'   => 'date_picker',
								),
							),
						),
					),
				),
			),
		);

		return $settings;
	}
	public static function get_layout_settings() {
		$settings = array(
			array(
				'name'        => 'align',
				'label'       => __( 'Align', 'search-filter' ),
				'group'       => 'layout',
				'tab'         => 'styles',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Text',
				'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder' => __( 'Align the field inside its container.', 'search-filter' ),
			),
			array(
				'name'        => 'alignment',
				'label'       => __( 'Alignment', 'search-filter' ),
				'group'       => 'layout',
				'tab'         => 'styles',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Text',
				'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'placeholder' => __( 'Align the field inside its container.', 'search-filter' ),
			),
			array(
				'name'              => 'fieldPadding',
				'label'             => __( 'Padding', 'search-filter' ),
				'group'             => 'field-dimensions',
				'tab'               => 'styles',
				'stylesDefault'     => '',
				'allowEmpty'        => true,
				'type'              => 'object',
				'inputType'         => 'Dimension',
				'allowReset'        => false,
				'context'           => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'excludeFromStyles' => true,
			),
			array(
				'name'              => 'fieldMargin',
				'label'             => __( 'Margin', 'search-filter' ),
				'group'             => 'field-dimensions',
				'tab'               => 'styles',
				'stylesDefault'     => '',
				'allowEmpty'        => true,
				'type'              => 'object',
				'inputType'         => 'Dimension',
				'sides'             => array( 'top', 'bottom' ),
				'allowReset'        => false,
				'context'           => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
				'excludeFromStyles' => true,
			),
			array(
				'name'          => 'inputMargin',
				'label'         => __( 'Margin', 'search-filter' ),
				'group'         => 'input-dimensions',
				'tab'           => 'styles',
				'stylesDefault' => '',
				'allowEmpty'    => true,
				'type'          => 'object',
				'inputType'     => 'Dimension',
				'allowReset'    => false,
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputColor',
				'label'          => __( 'Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#3c434a',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputBackgroundColor',
				'label'          => __( 'Background Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#ffffff',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputSelectedColor',
				'label'          => __( 'Selected Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#ffffff',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputSelectedBackgroundColor',
				'label'          => __( 'Selected Background Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#167de4',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),

			// TODO - these can be handled in the border group.
			array(
				'name'          => 'inputBorderColor',
				'label'         => __( 'Border Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#bbbbbb',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputBorderHoverColor',
				'label'         => __( 'Border Hover Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#888888',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputBorderFocusColor',
				'label'         => __( 'Border Focus Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#333333',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),

			array(
				'name'          => 'inputIconColor',
				'label'         => __( 'Icon Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#bbbbbb',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputActiveIconColor',
				'label'         => __( 'Active Icon Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#167de4',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputInactiveIconColor',
				'label'         => __( 'Inactive Icon Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#bbbbbb',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputInteractiveColor',
				'label'          => __( 'Interactive Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#bbbbbb',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'           => 'inputInteractiveHoverColor',
				'label'          => __( 'Interactive Hover Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'stylesDefault'  => '#333333',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputClearColor',
				'label'         => __( 'Clear Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#bbbbbb',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
			array(
				'name'          => 'inputClearHoverColor',
				'label'         => __( 'Clear Hover Color', 'search-filter' ),
				'group'         => 'input-colors',
				'tab'           => 'styles',
				'stylesDefault' => '#333333',
				'allowEmpty'    => true,
				'type'          => 'string',
				'inputType'     => 'ColorPicker',
				'clearable'     => 'true',
				'context'       => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			),
		);
		return $settings;
	}
}
