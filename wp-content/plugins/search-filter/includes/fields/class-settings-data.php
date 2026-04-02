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
		$settings_data = array_merge( $settings_data, self::get_per_page_settings() );
		$settings_data = array_merge( $settings_data, self::get_shared_settings() );
		$settings_data = array_merge( $settings_data, self::get_combobox_settings() );
		$settings_data = array_merge( $settings_data, self::get_design_settings() );
		$settings_data = array_merge( $settings_data, self::get_layout_settings() );
		$settings_data = array_merge( $settings_data, self::get_input_option_settings() );
		$settings_data = array_merge( $settings_data, self::get_dropdown_settings() );
		return $settings_data;
	}

	/**
	 * Get settings groups with labels and name.
	 *
	 * @return array An array of the defined settings groups.
	 */
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
				'name'      => 'field-design',
				'label'     => __( 'Field', 'search-filter' ),
				'subgroups' => array(
					array(
						'name'  => 'field-dimensions',
						'label' => __( 'Field Dimensions', 'search-filter' ),
						'type'  => 'tools-panel',
					),
				),
			),
			array(
				'name'      => 'input-design',
				'label'     => __( 'Input', 'search-filter' ),
				'subgroups' => array(
					array(
						'name'  => 'input-dimensions',
						'label' => __( 'Dimensions', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'input-colors',
						'label' => __( 'Colors', 'search-filter' ),
						'type'  => 'color-panel',
					),
					array(
						'name'  => 'input-border',
						'label' => __( 'Border', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'input-shadow',
						'label' => __( 'Shadow', 'search-filter' ),
						'type'  => 'tools-panel',
					),
				),
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
				'name'      => 'label-design',
				'label'     => __( 'Label', 'search-filter' ),
				'subgroups' => array(
					array(
						'name'  => 'label-dimensions',
						'label' => __( 'Dimensions', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'label-color',
						'label' => __( 'Color', 'search-filter' ),
						'type'  => 'color-panel',
					),
					array(
						'name'  => 'label-border',
						'label' => __( 'Border', 'search-filter' ),
						'type'  => 'tools-panel',
					),
				),
			),

			array(
				'name'  => 'description',
				'label' => __( 'Description', 'search-filter' ),
			),

			array(
				'name'      => 'description-design',
				'label'     => __( 'Description', 'search-filter' ),
				'subgroups' => array(
					array(
						'name'  => 'description-dimensions',
						'label' => __( 'Dimensions', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'description-color',
						'label' => __( 'Color', 'search-filter' ),
						'type'  => 'color-panel',
					),
					array(
						'name'  => 'description-border',
						'label' => __( 'Border', 'search-filter' ),
						'type'  => 'tools-panel',
					),
				),
			),

			array(
				'name'      => 'dropdown-design',
				'label'     => __( 'Dropdown', 'search-filter' ),
				'subgroups' => array(
					array(
						'name'  => 'dropdown-dimensions',
						'label' => __( 'Dimensions', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'dropdown-border',
						'label' => __( 'Border', 'search-filter' ),
						'type'  => 'tools-panel',
					),
					array(
						'name'  => 'dropdown-shadow',
						'label' => __( 'Shadow', 'search-filter' ),
						'type'  => 'tools-panel',
					),
				),
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
				'context'      => array( 'admin/field', 'block/field' ),
				'dataProvider' => array(
					'route'   => '/settings/options/queries',
					'preload' => true,
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
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
				'context'     => array( 'admin/field' ), // TODO - check if we should add this back into the block context - it sets the type manually based on the block.
				'options'     => array(),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'        => 'label',
				'label'       => __( 'Label', 'search-filter' ),
				'group'       => 'label',
				'help'        => __( 'If the label is not shown it will still be available to screen readers', 'search-filter' ),
				'tab'         => 'settings',
				'default'     => __( 'New field', 'search-filter' ),
				'type'        => 'string',
				'inputType'   => 'Text',
				'placeholder' => __( 'Enter a Label', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),

			),
			array(
				'name'      => 'showLabel',
				'label'     => __( 'Show label', 'search-filter' ),
				'group'     => 'label',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'name'      => 'showLabelNotice',
				'content'   => __( 'Hiding the label for search fields does not comply with WCAG Level A (3.3.2 Labels or Instructions Standards).', 'search-filter-pro' ),
				'group'     => 'label',
				'tab'       => 'settings',
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'context'   => array( 'admin/field', 'block/field' ),
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'showLabel',
							'value'   => 'no',
							'compare' => '=',
						),

					),
				),
			),
			array(
				'name'             => 'labelScale',
				'label'            => __( 'Scale', 'search-filter' ),
				'group'            => 'label-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'type'             => 'number',
				'inputType'        => 'Range',
				'context'          => array( 'admin/field', 'block/field' ),
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
				'style'            => array(
					'type'      => 'dimension',
					'group'     => 'label',
					'value'     => 'var:label-scale',
					'variables' => array(
						'label-scale' => array(
							'value' => 'token:label-scale',
							'type'  => 'unit',
						),
					),
				),
			),
			array(
				'name'       => 'labelColor',
				'label'      => __( 'Color', 'search-filter' ),
				'group'      => 'label-color',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'      => 'color',
					'group'     => 'label',
					'layer'     => 'foreground',
					'value'     => 'var:label-color',
					'variables' => array(
						'label-color' => array(
							'value' => 'token:color-contrast-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'labelBackgroundColor',
				'label'      => __( 'Background Color', 'search-filter' ),
				'group'      => 'label-color',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'      => 'color',
					'group'     => 'label',
					'layer'     => 'background',
					'value'     => 'var:label-background-color',
					'variables' => array(
						'label-background-color' => array(
							'value' => 'token:color-transparent',
							'type'  => 'color',
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
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'label',
					'value'         => 'var:label-padding',
					'variables'     => array(
						'label-padding' => array(
							'value' => 'token:label-padding',
							'type'  => 'spacing',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => '.search-filter-field .search-filter-label',
					),
				),
			),
			array(
				'name'       => 'labelMargin',
				'label'      => __( 'Margin', 'search-filter' ),
				'group'      => 'label-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'sides'      => array( 'top', 'bottom' ),
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'label',
					'value'         => 'var:label-margin',
					'variables'     => array(
						'label-margin' => array(
							'value' => 'token:label-margin',
							'type'  => 'spacing',
						),
					),
					'visualization' => array(
						'property' => 'margin',
						'selector' => '.search-filter-field .search-filter-label',
					),
				),
			),
			// Description.
			array(
				'name'      => 'showDescription',
				'label'     => __( 'Show description', 'search-filter' ),
				'group'     => 'description',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'name'        => 'description',
				'label'       => __( 'Description', 'search-filter' ),
				'group'       => 'description',
				'tab'         => 'settings',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Text',
				'placeholder' => __( 'Enter a description', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),
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
				'allowEmpty'       => true,
				'type'             => 'number',
				'inputType'        => 'Range',
				'context'          => array( 'admin/field', 'block/field' ),
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
				'style'            => array(
					'type'      => 'dimension',
					'group'     => 'description',
					'value'     => 'var:description-scale',
					'variables' => array(
						'description-scale' => array(
							'value' => 'token:description-scale',
							'type'  => 'unit',
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
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'description',
					'value'         => 'var:description-padding',
					'variables'     => array(
						'description-padding' => array(
							'value' => 'token:description-padding',
							'type'  => 'spacing',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => '.search-filter-field .search-filter-description',
					),
				),
			),
			array(
				'name'       => 'descriptionMargin',
				'label'      => __( 'Margin', 'search-filter' ),
				'group'      => 'description-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'sides'      => array( 'top', 'bottom' ),
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'description',
					'value'         => 'var:description-margin',
					'variables'     => array(
						'description-margin' => array(
							'value' => 'token:description-margin',
							'type'  => 'spacing',
						),
					),
					'visualization' => array(
						'property' => 'margin',
						'selector' => '.search-filter-field .search-filter-description',
					),
				),
			),

			array(
				'name'       => 'descriptionColor',
				'label'      => __( 'Color', 'search-filter' ),
				'group'      => 'description-color',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'      => 'color',
					'group'     => 'description',
					'layer'     => 'foreground',
					'value'     => 'var:description-color',
					'variables' => array(
						'description-color' => array(
							'value' => 'token:color-contrast-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'descriptionBackgroundColor',
				'label'      => __( 'Background Color', 'search-filter' ),
				'group'      => 'description-color',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
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
				'style'      => array(
					'type'      => 'color',
					'group'     => 'description',
					'layer'     => 'background',
					'value'     => 'var:description-background-color',
					'variables' => array(
						'description-background-color' => array(
							'value' => 'token:color-transparent',
							'type'  => 'color',
						),
					),
				),
			),

			array(
				'name'        => 'dataType',
				'label'       => __( 'Data Type', 'search-filter' ),
				'group'       => 'data',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Select',
				'isDataType'  => true, // Flag data types for the indexer to detect changes.
				'placeholder' => __( 'Select a Data Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),
				'options'     => array(
					array(
						'label' => __( 'Post Attributes', 'search-filter' ),
						'value' => 'post_attribute',
					),
					array(
						'label' => __( 'Taxonomy', 'search-filter' ),
						'value' => 'taxonomy',
					),
				),
				// dataType needs to set action to `hide` because in  pro it depends
				// on indexer conditions, but we never want to disable and  show it.
				'dependsOn'   => array(
					'action'   => 'hide',
					'relation' => 'AND',
					'rules'    => array(), // We have to set an empty rules array to avoid errors.
				),
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
				'isDataType'  => true,
				'placeholder' => __( 'Choose Post Attributes source', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),
				'options'     => array(
					array(
						'label' => __( 'Post Title + Content', 'search-filter' ),
						'value' => 'default',
					),
					array(
						'label' => __( 'Post Type', 'search-filter' ),
						'value' => 'post_type',
					),
					array(
						'label' => __( 'Post Status', 'search-filter' ),
						'value' => 'post_status',
					),
					array(
						'label' => __( 'Published Date', 'search-filter' ),
						'value' => 'post_published_date',
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
				'isDataType'   => true, // Flag data types for the indexer to detect changes.
				'placeholder'  => __( 'Choose a Taxonomy', 'search-filter' ),
				'context'      => array( 'admin/field', 'block/field' ),
				'options'      => array(),
				'dataProvider' => array(
					'route' => '/settings/options/query/taxonomies',
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
				'supports'     => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'        => 'controlType',
				'label'       => 'Control Type',
				'group'       => 'control-type',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Hidden',
				'placeholder' => __( 'Choose a Control Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),
				'options'     => array(),
				'supports'    => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'        => 'inputType',
				'label'       => __( 'Input Type', 'search-filter' ),
				'help'        => __( 'Choose the type of input control.', 'search-filter' ) . "\n" . __( 'Your data settings will affect the input types that are available', 'search-filter' ),
				'group'       => 'input',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Hidden',
				'placeholder' => __( 'Select an Input Type', 'search-filter' ),
				'context'     => array( 'admin/field', 'block/field' ),
				'options'     => array(),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
		);

		return $settings;
	}

	/**
	 * Get text settings.
	 *
	 * @return array An array of the defined text settings.
	 */
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
				'context'   => array( 'admin/field', 'block/field' ),
			),
		);

		return $settings;
	}

	/**
	 * Get combobox settings.
	 *
	 * @return array An array of the defined combobox settings.
	 */
	public static function get_combobox_settings() {
		$settings = array(
			array(
				'name'      => 'inputEnableSearch',
				'label'     => __( 'Enable Search', 'search-filter' ),
				'help'      => __( 'Allow text input to filter the options available. Note: this is always disabled on mobile.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => __( 'yes', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Toggle',
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
				'context'   => array( 'admin/field', 'block/field' ),
			),
			array(
				'name'      => 'inputNoResultsText',
				'label'     => __( 'No results text', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => __( 'No results', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Text',
				'help'      => __( 'The text that appears when no results have been found.', 'search-filter' ),
				'context'   => array( 'admin/field', 'block/field' ),
			),
			array(
				'name'      => 'inputSingularResultsCountText',
				'label'     => __( 'Results Count Text (Singular)', 'search-filter' ),
				// translators: %d is not a placeholder but used to explain its usage - keep in tact.
				'help'      => __( 'Use `%d` to position the number of results. Used for screen readers.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				// translators: %d is the number of results.
				'default'   => __( '%d result available', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Text',
				'context'   => array( 'admin/field', 'block/field' ),
			),
			array(
				'name'      => 'inputPluralResultsCountText',
				'label'     => __( 'Results Count Text (Plural)', 'search-filter' ),
				// translators: %d is not a placeholder but used to explain its usage - keep in tact.
				'help'      => __( 'Use `%d` to position the number of results. Used for screen readers.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				// translators: %d is the number of results.
				'default'   => __( '%d results available', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Text',
				'context'   => array( 'admin/field', 'block/field' ),
			),
		);

		return $settings;
	}

	/**
	 * Get choice settings.
	 *
	 * @return array An array of the defined choice settings.
	 */
	public static function get_choice_settings() {
		$settings = array(
			array(
				'name'      => 'inputCheckboxTristate',
				'label'     => __( 'Tri-state Selection', 'search-filter' ),
				'help'      => __( 'Cycles between 3 states: checked, unchecked, and indeterminate. Checking parents automatically selects children.', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'name'      => 'multiple',
				'label'     => __( 'Multiple selection', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '!=',
							'value'   => 'post_attribute',
						),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'name'      => 'inputOptionsDefaultLabel',
				'label'     => __( 'Default option label', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => __( 'All items', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Text',
				'context'   => array( 'admin/field', 'block/field' ),
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
			),
			array(
				'name'      => 'hideEmpty',
				'label'     => __( 'Hide options with no results', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'default'     => '100',
				'inputType'   => 'Number',
				'min'         => 1,
				'step'        => 1,
				'type'        => 'string',
				'placeholder' => '',
				'context'     => array( 'admin/field', 'block/field' ),
				'supports'    => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'dataTotalNumberOfOptionsNotice',
				'content'   => __( 'Showing too many options creates a poor user experience and can cause performance issues.', 'search-filter-pro' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'context'   => array( 'admin/field', 'block/field' ),
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'dataTotalNumberOfOptions',
							'value'   => '100',
							'compare' => '>',
						),

					),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'default'   => '',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'supports'  => array(
					'previewAPI' => true,
				),
			),
		);

		return $settings;
	}

	/**
	 * Get taxonomy filter settings.
	 *
	 * @return array An array of the defined taxonomy filter settings.
	 */
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'     => array( 'admin/field', 'block/field' ),
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
				'name'      => 'taxonomyNavigatesArchive',
				'label'     => __( 'Navigates to term archive', 'search-filter' ),
				'help'      => __( 'Navigates to the taxonomy term archive URL when an option is selected.', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'context'   => array( 'admin/field', 'block/field' ),
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
									'relation' => 'OR',
									'rules'    => array(
										array(
											'relation' => 'AND',
											'rules'    => array(
												array(
													'store'   => 'query',
													'option'  => 'archiveType',
													'compare' => '=',
													'value'   => 'post_type',
												),
												array(
													'relation' => 'OR',
													'rules'    => array(
														array(
															'store'   => 'query',
															'option'  => 'archiveFilterTaxonomies',
															'compare' => '=',
															'value'   => 'all',
														),
														array(
															'store'   => 'query',
															'option'  => 'archiveFilterTaxonomies',
															'compare' => '=',
															'value'   => 'custom',
														),
													),
												),
											),
										),
										array(
											'relation' => 'AND',
											'rules'    => array(
												array(
													'store'   => 'query',
													'option'  => 'archiveType',
													'compare' => '=',
													'value'   => 'taxonomy',
												),
											),
										),
									),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
						'label' => __( 'Term Order', 'search-filter' ),
						'value' => 'term_order',
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
				'default'   => '',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'      => array( 'admin/field', 'block/field' ),
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

	/**
	 * Get post type choice settings.
	 *
	 * @return array An array of the defined post type choice settings.
	 */
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
				'isDataType'   => true, // Flag data types for the indexer to detect changes.
				'tab'          => 'settings',
				'label'        => __( 'Posts Types', 'search-filter' ),
				'placeholder'  => __( 'Choose post types', 'search-filter' ),
				'help'         => __( 'Leave empty to choose all available post types.  Available post types are controlled by your query settings.', 'search-filter' ),
				'group'        => 'data',
				'context'      => array( 'admin/field', 'block/field' ),
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

	/**
	 * Get the defined post status choice settings.
	 *
	 * @return array An array of the defined post status choice settings.
	 */
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
				'isDataType'   => true, // Flag data types for the indexer to detect changes.
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array( 'publish' ),
				'context'      => array( 'admin/field', 'block/field' ),
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


	/**
	 * Get the defined range settings. Currently an empty array.
	 *
	 * @return array An array of the defined range settings.
	 */
	/**
	 * Get the defined range settings. Currently an empty array.
	 *
	 * @return array An array of the defined range settings.
	 */
	public static function get_per_page_settings() {

		$settings = array(
			// Add custom options to perPage field.
			array(
				'name'      => 'perPageOptions',
				'label'     => __( 'Per Page Options', 'search-filter' ),
				'group'     => 'input',
				'tab'       => 'settings',
				'default'   => array(
					array(
						'label' => '10',
					),
					array(
						'label' => '20',
					),
				),
				'type'      => 'array',
				'items'     => array(
					'type' => 'object',
				),
				'inputType' => 'PerPageOptions',
				'context'   => array( 'admin/field', 'block/field' ),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
		);

		return $settings;
	}

	/**
	 * Get the defined shared settings.
	 *
	 * @return array An array of the defined shared settings.
	 */
	public static function get_shared_settings() {

		$settings = array(
			array(
				'name'        => 'dateDisplayFormat',
				'label'       => __( 'Display format', 'search-filter' ),
				'group'       => 'input',
				'tab'         => 'settings',
				'default'     => '',
				'type'        => 'string',
				'inputType'   => 'Select',
				'context'     => array( 'admin/field', 'block/field' ),
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
				'context'     => array( 'admin/field', 'block/field' ),
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
				'name'        => 'addClass',
				'label'       => __( 'Add a class', 'search-filter' ),
				'help'        => __( 'Separate class names with a space', 'search-filter' ),
				'default'     => '',
				'group'       => 'advanced',
				'tab'         => 'settings',
				'type'        => 'string',
				'inputType'   => 'Text',
				'context'     => array( 'admin/field' ),
				'placeholder' => __( 'Add a CSS class to the field', 'search-filter' ),
			),
		);

		return $settings;
	}

	/**
	 * Get the defined design settings.
	 *
	 * @return array An array of the defined design settings.
	 */
	public static function get_design_settings() {

		$settings = array(
			array(
				'name'              => 'stylesId',
				'label'             => __( 'Styles preset', 'search-filter' ),
				'group'             => 'styles',
				'tab'               => 'styles',
				'default'           => '0',
				'help'              => __( 'Styles are inherited from the selected preset.', 'search-filter' ),
				'type'              => 'string',
				'inputType'         => 'StylesSelect',
				'context'           => array( 'admin/field', 'block/field' ),
				'placeholder'       => __( 'Select styles to apply', 'search-filter' ),
				'excludeFromStyles' => true,
				'dataProvider'      => array(
					'route'   => '/settings/options/styles',
					'preload' => true,
				),
			),
			array(
				'name'              => 'inputScale',
				'label'             => __( 'Scale', 'search-filter' ),
				'group'             => 'input-dimensions',
				'tab'               => 'styles',
				'allowEmpty'        => true,
				'showInheritNotice' => true,
				'isShownByDefault'  => true,
				'type'              => 'number',
				'inputType'         => 'Range',
				'context'           => array( 'admin/field', 'block/field' ),
				'placeholder'       => __( 'Choose a scale', 'search-filter' ),
				'min'               => 1,
				'max'               => 10,
				'step'              => 1,
				'style'             => array(
					'type'      => 'dimension',
					'group'     => 'input',
					'value'     => 'var:input-scale',
					'variables' => array(
						'input-scale' => array(
							'value' => 'token:input-scale',
							'type'  => 'unit',
						),
					),
				),
			),
			array(
				'name'             => 'width',
				'label'            => __( 'Width', 'search-filter' ),
				'group'            => 'dimensions',
				'tab'              => 'settings',
				'default'          => '',
				'type'             => 'string',
				'inputType'        => 'ButtonGroup',
				'context'          => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
		);

		return $settings;
	}


	/**
	 * Get a property from an array or object. Supports nested lookup using dot notation.
	 *
	 * @param array|object      $data Required. The array or object to get the property from.
	 * @param string            $property Required. The property to get.
	 * @param array|object|null $carry Optional. The value to return if the property is not found.
	 *
	 * @return mixed The value of the property or null if $data is invalid or the property does not exist.
	 */
	public static function get_property( $data = array(), $property = '', $carry = null ) {
		$invalid_data = ! is_array( $data ) && ! is_object( $data );

		if ( $invalid_data || ! $property ) {
			return null;
		}

		$parts = explode( '.', $property );
		$carry = $data[ $parts[0] ] ?? null;

		if ( count( $parts ) === 1 ) {
			return $carry;
		}

		// Stop here if we can't do any more lookups.
		if ( ! is_array( $carry ) && ! is_object( $carry ) ) {
			return $carry;
		}

		return self::get_property( $carry, implode( '.', array_slice( $parts, 1 ) ) );
	}

	/**
	 * Get the defined layout settings.
	 *
	 * @return array An array of the defined layout settings.
	 */
	public static function get_layout_settings() {
		$settings = array(
			array(
				'name'              => 'fieldMargin',
				'label'             => __( 'Margin', 'search-filter' ),
				'group'             => 'field-dimensions',
				'tab'               => 'styles',
				'allowEmpty'        => true,
				'type'              => 'object',
				'inputType'         => 'Dimension',
				'sides'             => array( 'top', 'bottom' ),
				'context'           => array( 'block/field' ),
				'excludeFromStyles' => true,
				'style'             => array(
					'type'      => 'dimension',
					'group'     => 'field',
					'value'     => 'var:field-margin',
					'variables' => array(
						'field-margin' => array(
							'value' => 'token:margin',
							'type'  => 'spacing',
						),
					),
				),
			),

			array(
				'name'       => 'inputPadding',
				'label'      => __( 'Padding', 'search-filter' ),
				'group'      => 'input-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field' ),

				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => array(
						'top'    => 'var:input-padding-top',
						'right'  => 'var:input-padding-right',
						'bottom' => 'var:input-padding-bottom',
						'left'   => 'var:input-padding-left',
					),
					'variables'     => array(
						'input-padding-top'    => array(
							'value' => 'calc(0.35 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
						'input-padding-right'  => array(
							'value' => 'calc(0.48 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
						'input-padding-bottom' => array(
							'value' => 'calc(0.35 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
						'input-padding-left'   => array(
							'value' => 'calc(0.48 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => array(
							'search/text'         => '.search-filter-field .search-filter-field__input, .search-filter-field .search-filter-input-text__input',
							'search/autocomplete' => '.search-filter-field .search-filter-field__input, .search-filter-field .search-filter-input-text__input',
							'choice/select'       => '.search-filter-field .search-filter-component-combobox, .search-filter-field .search-filter-component-combobox__actions, .search-filter-field .search-filter-component-combobox__selection',
							'choice/button'       => '.search-filter-field .search-filter-input-button',
							'choice/datepicker'   => '.search-filter-field .search-filter-field__input, .search-filter-field .search-filter-input-text__input',
							'control/submit'      => '.search-filter-field .search-filter-input-button',
							'control/reset'       => '.search-filter-field .search-filter-input-button',
							'control/sort'        => '.search-filter-field .search-filter-component-combobox, .search-filter-field .search-filter-component-combobox__actions, .search-filter-field .search-filter-component-combobox__selection',
							'control/per_page'    => '.search-filter-field .search-filter-component-combobox, .search-filter-field .search-filter-component-combobox__actions, .search-filter-field .search-filter-component-combobox__selection',
						),
					),
				),
			),
			array(
				'name'             => 'inputGap',
				'label'            => __( 'Spacing', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'min'              => 0,
				'max'              => 50,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-gap',
					'variables'     => array(
						'input-gap' => array(
							'value' => 'calc(0.2 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
					'visualization' => array(
						'property' => 'gap',
						'selector' => array(
							'search/text'         => '.search-filter-field .search-filter-input-text',
							'search/autocomplete' => '.search-filter-field .search-filter-input-text',
							'choice/select'       => '.search-filter-field .search-filter-component-combobox__actions',
							'choice/button'       => '.search-filter-field .search-filter-input-button',
							'choice/datepicker'   => '.search-filter-field .search-filter-input-text',
							'control/sort'        => '.search-filter-field .search-filter-component-combobox__actions',
							'control/per_page'    => '.search-filter-field .search-filter-component-combobox__actions',
						),
					),
				),
			),
			array(
				'name'       => 'inputMargin',
				'label'      => __( 'Margin', 'search-filter' ),
				'group'      => 'input-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-margin',
					'variables'     => array(
						'input-margin' => array(
							'value' => 'token:input-margin',
							'type'  => 'spacing',
						),
					),
					'visualization' => array(
						'property' => 'margin',
						'selector' => '.search-filter-field .search-filter-field__input',
					),
				),
			),
			array(
				'name'             => 'inputIconPosition',
				'label'            => __( 'Icon Position', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'default'          => 'left',
				'allowReset'       => true,
				'allowEmpty'       => true,
				'type'             => 'string',
				'inputType'        => 'ButtonGroup',
				'requireSelection' => true,
				'context'          => array( 'admin/field', 'block/field' ),
				'options'          => array(
					array(
						'label' => __( 'Left', 'search-filter' ),
						'value' => 'left',
					),
					array(
						'label' => __( 'Right', 'search-filter' ),
						'value' => 'right',
					),
				),
				// Needs style prop to be shown in preset editor.
				'style'            => array(),
			),
			array(
				'name'             => 'inputIconSize',
				'label'            => __( 'Icon Size', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-icon-size',
					'variables'     => array(
						'input-icon-size' => array(
							'value' => 'calc(1.15 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'box',
						'selector' => '.search-filter-field .search-filter-icon:not(.search-filter-input-text__clear-button) svg',
					),
				),
			),
			array(
				'name'             => 'inputIconPadding',
				'label'            => __( 'Icon Padding', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-icon-padding',
					'variables'     => array(
						'input-icon-padding' => array(
							'value' => 'calc(0.15 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => '.search-filter-field .search-filter-input-text__icon',
					),
				),
			),
			array(
				'name'             => 'inputClearSize',
				'label'            => __( 'Clear Size', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-clear-size',
					'variables'     => array(
						'input-clear-size' => array(
							'value' => 'var(--search-filter-scale-base-size)',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'box',
						'selector' => '.search-filter-field .search-filter-icon.search-filter-input-text__clear-button svg, .search-filter-field .search-filter-component-combobox__clear-button svg',
					),
				),
			),
			array(
				'name'             => 'inputClearPadding',
				'label'            => __( 'Clear Padding', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-clear-padding',
					'variables'     => array(
						'input-clear-padding' => array(
							'value' => 'calc(0.15 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => '.search-filter-field .search-filter-icon.search-filter-input-text__clear-button, .search-filter-field .search-filter-component-combobox__clear-button',
					),
				),
			),
			array(
				'name'             => 'inputSelectionGap',
				'label'            => __( 'Selection Gap', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'max'              => 50,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'dependsOn'        => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'multiple',
							'compare' => '=',
							'value'   => 'yes',
						),
					),
				),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-selection-gap',
					'variables'     => array(
						'input-selection-gap' => array(
							'value' => 'calc(0.175 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'gap',
						'selector' => '.search-filter-field .search-filter-component-combobox__selection',
					),
				),
			),
			array(
				'name'             => 'inputToggleSize',
				'label'            => __( 'Toggle Size', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-toggle-size',
					'variables'     => array(
						'input-toggle-size' => array(
							'value' => 'calc(1.35 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
					'visualization' => array(
						'property' => 'box',
						'selector' => '.search-filter-field .search-filter-component-combobox__listbox-toggle svg',
					),
				),
			),
			array(
				'name'             => 'inputTogglePadding',
				'label'            => __( 'Toggle Padding', 'search-filter' ),
				'group'            => 'input-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => false,
				'type'             => 'object',
				'inputType'        => 'Dimension',
				'sides'            => array( 'left', 'right' ),
				'context'          => array( 'admin/field', 'block/field' ),
				'style'            => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => array(
						'right' => 'var:input-toggle-padding-right',
						'left'  => 'var:input-toggle-padding-left',
					),
					'variables'     => array(
						'input-toggle-padding-right' => array(
							'value' => 'calc(0.4 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-toggle-padding-left'  => array(
							'value' => 'calc(0.4 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => '.search-filter-field .search-filter-component-combobox__listbox-toggle',
					),
				),
			),
			array(
				'name'           => 'inputLabelColor',
				'label'          => __( 'Label Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input-label',
					'layer'     => 'foreground',
					'value'     => 'var:input-label-color',
					'variables' => array(
						'input-label-color' => array(
							'value' => 'token:color-contrast-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputColor',
				'label'          => __( 'Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-color',
					'variables' => array(
						'input-color' => array(
							'value' => 'token:color-contrast-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputBackgroundColor',
				'label'          => __( 'Background Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'background',
					'value'     => 'var:input-background-color',
					'variables' => array(
						'input-background-color' => array(
							'value' => 'token:color-base-1',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputPlaceholderColor',
				'label'          => __( 'Placeholder Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input',
					'value'     => 'var:input-placeholder-color',
					'variables' => array(
						'input-placeholder-color' => array(
							'value' => 'color-mix(in srgb, var(--search-filter-input-color) 67%, transparent)',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputSelectedColor',
				'label'          => __( 'Selected Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'inputSelected',
					'layer'     => 'foreground',
					'value'     => 'var:input-selected-color',
					'variables' => array(
						'input-selected-color' => array(
							'value' => 'token:color-contrast-accent',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputSelectedBackgroundColor',
				'label'          => __( 'Selected Background Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'inputSelected',
					'layer'     => 'background',
					'value'     => 'var:input-selected-background-color',
					'variables' => array(
						'input-selected-background-color' => array(
							'value' => 'token:color-base-accent',
							'type'  => 'color',
						),
					),
				),
			),

			array(
				'name'       => 'inputIconColor',
				'label'      => __( 'Icon Color', 'search-filter' ),
				'group'      => 'input-colors',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-icon-color',
					'variables' => array(
						'input-icon-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputActiveIconColor',
				'label'      => __( 'Active Icon Color', 'search-filter' ),
				'group'      => 'input-colors',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-active-icon-color',
					'variables' => array(
						'input-active-icon-color' => array(
							'value' => 'token:color-base-accent',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputInactiveIconColor',
				'label'      => __( 'Inactive Icon Color', 'search-filter' ),
				'group'      => 'input-colors',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-inactive-icon-color',
					'variables' => array(
						'input-inactive-icon-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputInteractiveColor',
				'label'          => __( 'Interactive Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-interactive-color',
					'variables' => array(
						'input-interactive-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'           => 'inputInteractiveHoverColor',
				'label'          => __( 'Interactive Hover Color', 'search-filter' ),
				'group'          => 'input-colors',
				'tab'            => 'styles',
				'allowEmpty'     => true,
				'type'           => 'string',
				'inputType'      => 'ColorPicker',
				'clearable'      => 'true',
				'paletteSupport' => array( 'solid', 'gradient' ),
				'context'        => array( 'admin/field', 'block/field' ),
				'style'          => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-interactive-hover-color',
					'variables' => array(
						'input-interactive-hover-color' => array(
							'value' => 'token:color-contrast-1',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputClearColor',
				'label'      => __( 'Clear Color', 'search-filter' ),
				'group'      => 'input-colors',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-clear-color',
					'variables' => array(
						'input-clear-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputClearHoverColor',
				'label'      => __( 'Clear Hover Color', 'search-filter' ),
				'group'      => 'input-colors',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'foreground',
					'value'     => 'var:input-clear-hover-color',
					'variables' => array(
						'input-clear-hover-color' => array(
							'value' => 'token:color-contrast-1',
							'type'  => 'color',
						),
					),
				),
			),

			// Border settings.
			array(
				'name'       => 'inputBorder',
				'label'      => __( 'Border', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'BorderBox',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'input',
					'value'     => array(
						'style' => 'var:input-border-style',
						'width' => 'var:input-border-width',
						'color' => 'var:input-border-color',
					),
					'variables' => array(
						'input-border-style' => array(
							'value' => 'solid',
							'type'  => 'compound',
						),
						'input-border-width' => array(
							'value' => '1px',
							'type'  => 'compound',
						),
						'input-border-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'compound',
						),
					),
				),
			),
			// Kept for backward compatibility.
			array(
				'name'       => 'inputBorderColor',
				'label'      => __( 'Border Color', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'border',
					'value'     => 'var:input-border-hover-color',
					'variables' => array(
						'input-border-color' => array(
							'value' => 'token:color-base-2',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputBorderHoverColor',
				'label'      => __( 'Border Hover Color', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'border',
					'value'     => 'var:input-border-hover-color',
					'variables' => array(
						'input-border-hover-color' => array(
							'value' => 'token:color-base-3',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputBorderFocusColor',
				'label'      => __( 'Border Focus Color', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'border',
					'value'     => 'var:input-border-focus-color',
					'variables' => array(
						'input-border-focus-color' => array(
							'value' => 'token:color-contrast-1',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputBorderAccentColor',
				'label'      => __( 'Border Accent Color', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'ColorPicker',
				'clearable'  => 'true',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'color',
					'group'     => 'input',
					'layer'     => 'border',
					'value'     => 'var:input-border-accent-color',
					'variables' => array(
						'input-border-accent-color' => array(
							'value' => 'color-mix(in srgb, var(--search-filter-input-border-focus-color) 47%, transparent)',
							'type'  => 'color',
						),
					),
				),
			),
			array(
				'name'       => 'inputBorderRadius',
				'label'      => __( 'Border Radius', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'presets'    => array(
					'options' => array(
						array(
							'label' => __( 'Square', 'search-filter' ),
							'value' => 'token:border-radius-square',
						),
						array(
							'label' => __( 'Soft', 'search-filter' ),
							'value' => 'token:border-radius-soft',
						),
						array(
							'label' => __( 'Round', 'search-filter' ),
							'value' => 'token:border-radius-round',
						),
					),
				),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'input',
					'value'     => 'var:input-border-radius',
					'variables' => array(
						// Create a shorthand variable for the border radius, we can't use this as the "value" above
						// because its not possible to infer the default values from the connected tokens.
						'input-border-radius'              => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						// Also create one for each corner to do more granular styling.
						'input-border-radius-top-left'     => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'input-border-radius-top-right'    => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'input-border-radius-bottom-right' => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'input-border-radius-bottom-left'  => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
					),
				),
			),
			array(
				'name'       => 'inputBorderDivider',
				'label'      => __( 'Show Divider', 'search-filter' ),
				'group'      => 'input-border',
				'tab'        => 'styles',
				'type'       => 'string',
				'inputType'  => 'Toggle',
				'default'    => 'yes',
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
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(),
			),
			// Label Border settings.
			array(
				'name'       => 'labelBorderStyle',
				'label'      => __( 'Border Style', 'search-filter' ),
				'group'      => 'label-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'BorderBox',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'label',
					'value'     => array(
						'style' => 'var:label-border-style',
						'width' => 'var:label-border-width',
						'color' => 'var:label-border-color',
					),
					'variables' => array(
						'label-border-style' => array(
							'value' => 'solid',
							'type'  => 'border-style',
						),
						'label-border-width' => array(
							'value' => '0px',
							'type'  => 'border-width',
						),
						'label-border-color' => array(
							'value' => 'transparent',
							'type'  => 'border-color',
						),
					),
				),
			),

			array(
				'name'       => 'inputShadow',
				'label'      => __( 'Shadow', 'search-filter' ),
				'group'      => 'input-shadow',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'ShadowPicker',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'box-shadow',
					'group'     => 'input',
					'value'     => 'var:input-box-shadow',
					'variables' => array(
						'input-box-shadow' => array(
							'value' => '',
							'type'  => 'box-shadow',
						),
					),
				),
			),
			array(
				'name'       => 'labelBorderRadius',
				'label'      => __( 'Border Radius', 'search-filter' ),
				'group'      => 'label-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'label',
					'value'     => 'var:label-border-radius',
					'variables' => array(
						// Create a shorthand variable for the border radius, we can't use this as the "value" above
						// because its not possible to infer the default values from the connected tokens.
						'label-border-radius' => array(
							'value' => '0px',
							'type'  => 'unit',
						),
					),
				),
			),

			// Description Border settings.
			array(
				'name'       => 'descriptionBorderStyle',
				'label'      => __( 'Border Style', 'search-filter' ),
				'group'      => 'description-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'BorderBox',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'description',
					'value'     => array(
						'style' => 'var:description-border-style',
						'width' => 'var:description-border-width',
						'color' => 'var:description-border-color',
					),
					'variables' => array(
						'description-border-style' => array(
							'value' => 'solid',
							'type'  => 'border-style',
						),
						'description-border-width' => array(
							'value' => '0px',
							'type'  => 'border-width',
						),
						'description-border-color' => array(
							'value' => 'transparent',
							'type'  => 'border-color',
						),
					),
				),
			),
			array(
				'name'       => 'descriptionBorderRadius',
				'label'      => __( 'Border Radius', 'search-filter' ),
				'group'      => 'description-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'description',
					'value'     => 'var:description-border-radius',
					'variables' => array(
						// Create a shorthand variable for the border radius, we can't use this as the "value" above
						// because its not possible to infer the default values from the connected tokens.
						'description-border-radius' => array(
							'value' => '0px',
							'type'  => 'unit',
						),
					),
				),
			),
		);

		return $settings;
	}

	/**
	 * Get input option settings (for checkbox/radio option rows).
	 *
	 * @return array An array of the defined input option settings.
	 */
	public static function get_input_option_settings() {
		$settings = array(
			array(
				'name'       => 'inputOptionPadding',
				'label'      => __( 'Option Padding', 'search-filter' ),
				'group'      => 'input-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => array(
						'top'    => 'var:input-option-padding-top',
						'right'  => 'var:input-option-padding-right',
						'bottom' => 'var:input-option-padding-bottom',
						'left'   => 'var:input-option-padding-left',
					),
					'variables'     => array(
						'input-option-padding-top'    => array(
							'value' => 'calc(0.3 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-option-padding-right'  => array(
							'value' => '0',
							'type'  => 'spacing-unit',
						),
						'input-option-padding-bottom' => array(
							'value' => 'calc(0.3 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-option-padding-left'   => array(
							'value' => '0',
							'type'  => 'spacing-unit',
						),
					),
					'visualization' => array(
						'property' => 'padding',
						'selector' => array(
							'choice/checkbox' => '.search-filter-field .search-filter-input-checkbox',
							'choice/radio'    => '.search-filter-field .search-filter-input-radio',
						),
					),
				),
			),
			array(
				'name'       => 'inputOptionGap',
				'label'      => __( 'Option Spacing', 'search-filter' ),
				'group'      => 'input-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'min'        => 0,
				'max'        => 50,
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'          => 'dimension',
					'group'         => 'input',
					'value'         => 'var:input-option-gap',
					'variables'     => array(
						'input-option-gap' => array(
							'value' => '0',
							'type'  => 'spacing-unit',
						),
					),
					'visualization' => array(
						'property' => 'gap',
						'selector' => array(
							'choice/checkbox' => '.search-filter-field .search-filter-input-group',
							'choice/radio'    => '.search-filter-field .search-filter-input-group',
						),
					),
				),
			),
			array(
				'name'       => 'inputOptionIndentDepth',
				'label'      => __( 'Option Indent Depth', 'search-filter' ),
				'help'       => __( 'The amount of indentation for nested options.', 'search-filter' ),
				'group'      => 'input-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'min'        => 0,
				'max'        => 50,
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'dimension',
					'group'     => 'input',
					'value'     => 'var:input-option-indent-depth',
					'variables' => array(
						'input-option-indent-depth' => array(
							'value' => 'calc(1.6 * var(--search-filter-scale-base-size))',
							'type'  => 'unit',
						),
					),
				),
			),
		);

		return $settings;
	}

	/**
	 * Get dropdown settings.
	 *
	 * @return array An array of the defined dropdown settings.
	 */
	public static function get_dropdown_settings() {
		$settings = array(

			array(
				'name'       => 'dropdownScale',
				'label'      => __( 'Scale', 'search-filter' ),
				'group'      => 'dropdown-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'string',
				'inputType'  => 'Range',
				'min'        => 1,
				'max'        => 10,
				'step'       => 1,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'scale',
					'group'     => 'dropdown',
					'value'     => 'var:dropdown-scale',
					'variables' => array(
						'dropdown-scale' => array(
							'value' => '2',
							'type'  => 'unit',
						),
					),
				),
			),
			// Dropdown Dimensions settings.
			array(
				'name'             => 'dropdownAttachment',
				'label'            => __( 'Attachment', 'search-filter' ),
				'group'            => 'dropdown-dimensions',
				'tab'              => 'styles',
				'default'          => 'attached',
				'allowReset'       => true,
				'allowEmpty'       => true,
				'type'             => 'string',
				'inputType'        => 'ButtonGroup',
				'requireSelection' => true,
				'context'          => array( 'admin/field', 'block/field' ),
				'options'          => array(
					array(
						'label' => __( 'Attached', 'search-filter' ),
						'value' => 'attached',
					),
					array(
						'label' => __( 'Floating', 'search-filter' ),
						'value' => 'detached',
					),
				),
				// Needs style prop to be shown in preset editor.
				'style'            => array(),
			),
			array(
				'name'             => 'dropdownGap',
				'label'            => __( 'Gap', 'search-filter' ),
				'group'            => 'dropdown-dimensions',
				'tab'              => 'styles',
				'allowEmpty'       => true,
				'isShownByDefault' => true,
				'max'              => 50,
				'type'             => 'object',
				'inputType'        => 'RangeUnit',
				'context'          => array( 'admin/field', 'block/field' ),
				'dependsOn'        => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dropdownAttachment',
							'compare' => '=',
							'value'   => 'detached',
						),
					),
				),
				'style'            => array(
					'type'      => 'dimension',
					'group'     => 'dropdown',
					'value'     => 'var:dropdown-gap',
					'variables' => array(
						'dropdown-gap' => array(
							'value' => '4px',
							'type'  => 'unit',
						),
					),
				),
			),
			array(
				'name'       => 'dropdownOptionPadding',
				'label'      => __( 'Option Padding', 'search-filter' ),
				'group'      => 'dropdown-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'Dimension',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'dimension',
					'group'     => 'dropdown',
					'value'     => array(
						'top'    => 'var:dropdown-option-padding-top',
						'right'  => 'var:dropdown-option-padding-right',
						'bottom' => 'var:dropdown-option-padding-bottom',
						'left'   => 'var:dropdown-option-padding-left',
					),
					'variables' => array(
						'dropdown-option-padding-top'    => array(
							'value' => 'calc(0.5 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'dropdown-option-padding-right'  => array(
							'value' => 'calc(0.5 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'dropdown-option-padding-bottom' => array(
							'value' => 'calc(0.5 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'dropdown-option-padding-left'   => array(
							'value' => 'calc(0.5 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
			array(
				'name'       => 'dropdownOptionIndentDepth',
				'label'      => __( 'Option Indent Depth', 'search-filter' ),
				'help'       => __( 'The amount of indentation for nested options.', 'search-filter' ),
				'group'      => 'dropdown-dimensions',
				'tab'        => 'styles',
				'allowEmpty' => true,
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'dimension',
					'group'     => 'dropdown',
					'value'     => 'var:dropdown-option-indent-depth',
					'variables' => array(
						'dropdown-option-indent-depth' => array(
							'value' => '16px',
							'type'  => 'unit',
						),
					),
				),
			),
			// Dropdown Border settings.
			array(
				'name'       => 'dropdownBorder',
				'label'      => __( 'Border', 'search-filter' ),
				'group'      => 'dropdown-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'BorderBox',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'dropdown',
					'value'     => array(
						'style' => 'var:dropdown-border-style',
						'width' => 'var:dropdown-border-width',
						'color' => 'var:dropdown-border-color',
					),
					'variables' => array(
						'dropdown-border-style' => array(
							'value' => 'var:input-border-style',
							'type'  => 'compound',
						),
						'dropdown-border-width' => array(
							'value' => 'var:input-border-width',
							'type'  => 'compound',
						),
						'dropdown-border-color' => array(
							'value' => 'var:input-border-focus-color',
							'type'  => 'compound',
						),
					),
				),
			),
			array(
				'name'       => 'dropdownBorderRadius',
				'label'      => __( 'Border Radius', 'search-filter' ),
				'group'      => 'dropdown-border',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'RangeUnit',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'border',
					'group'     => 'dropdown',
					'value'     => 'var:dropdown-border-radius',
					'variables' => array(
						// Create a shorthand variable for the border radius, we can't use this as the "value" above
						// because its not possible to infer the default values from the connected tokens.
						'dropdown-border-radius'           => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						// Also create one for each corner to do more granular styling.
						'dropdown-border-radius-top-left'  => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'dropdown-border-radius-top-right' => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'dropdown-border-radius-bottom-right' => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
						'dropdown-border-radius-bottom-left' => array(
							'value' => 'token:border-radius-soft',
							'type'  => 'unit',
						),
					),
				),
			),

			array(
				'name'       => 'dropdownShadow',
				'label'      => __( 'Shadow', 'search-filter' ),
				'group'      => 'dropdown-shadow',
				'tab'        => 'styles',
				'type'       => 'object',
				'inputType'  => 'ShadowPicker',
				'allowReset' => true,
				'allowEmpty' => true,
				'context'    => array( 'admin/field', 'block/field' ),
				'style'      => array(
					'type'      => 'shadow',
					'group'     => 'input',
					'value'     => 'var:dropdown-box-shadow',
					'variables' => array(
						'dropdown-box-shadow' => array(
							'value' => '',
							'type'  => 'compound',
						),
					),
				),
			),
		);

		return $settings;
	}
}
