<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields\Features;

use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter_Pro\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Settings {

	/**
	 * Initialize the field settings.
	 */
	public static function init() {
		// Register the field settings.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'register_field_settings' ), 10 );
	}
	/**
	 * Register the pro field settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_field_settings() {
		$group_args = array(
			'position' => array(
				'placement' => 'before',
				'group'     => 'advanced',
			),
		);

		$add_setting_args = array();

		$setting = array(
			'name'      => 'autoSubmit',
			'label'     => __( 'Auto submit', 'search-filter' ),
			'help'      => __( 'Automatically submit after interacting.', 'search-filter' ),
			'default'   => 'yes',
			'offValue'  => 'no',
			'group'     => 'behaviour',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'autoSubmitOnType',
			'label'     => __( 'Auto submit on type', 'search-filter' ),
			'help'      => __( 'Automatically submit as you type.', 'search-filter' ),
			'default'   => 'no',
			'offValue'  => 'no',
			'group'     => 'behaviour',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'autoSubmit',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'autoSubmitDelay',
			'label'       => __( 'Auto submit delay', 'search-filter' ),
			'placeholder' => __( 'Leave empty for default.', 'search-filter' ),
			'help'        => __( 'Delay in milliseconds before auto submit.', 'search-filter' ),
			'group'       => 'behaviour',
			'tab'         => 'settings',
			'type'        => 'string',
			// Important - default must be an empty string '' so it will be overriden, but if not set
			// it was cause react to throw an error related to controlled/uncontrolled inputs.
			'default'     => '',
			'inputType'   => 'Number',
			'min'         => 0,
			'context'     => array( 'admin/field', 'block/field' ),
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'autoSubmit',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'hideFieldWhenEmpty',
			'label'     => __( 'Hide field when empty', 'search-filter' ),
			'help'      => __( 'Hides the field when there are no possible options.', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			// Important - default must be an empty string '' so it will be overriden, but if not set
			// it was cause react to throw an error related to controlled/uncontrolled inputs.
			'default'   => 'no',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
		);

		Fields_Settings::add_setting(
			$setting,
			array(
				'position' => array(
					'placement' => 'after',
					'setting'   => 'dataTotalNumberOfOptionsNotice',
				),
			)
		);

		$setting = array(
			'name'      => 'inputLoadingText',
			'label'     => __( 'Loading Text', 'search-filter' ),
			'help'      => __( 'Text to display when the field is loading options.', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'default'   => __( 'Looking for suggestions…', 'search-filter' ),
			'inputType' => 'Text',
			'context'   => array( 'admin/field', 'block/field' ),

		);

		Fields_Settings::add_setting(
			$setting,
			array(
				'position' => array(
					'placement' => 'after',
					'setting'   => 'dataTotalNumberOfOptions',
				),
			)
		);

		/*
		Fields_Settings::add_group(
			array(
				'name'  => 'conditions',
				'label' => __( 'Visibility conditions', 'search-filter-pro' ),
			),
			$group_args
		);

		$setting = array(
			'name'      => 'conditions',
			'label'     => __( 'Conditions', 'search-filter' ),
			'help'      => __( 'Choose which conditions need to be valid to display the field.', 'search-filter' ),
			'group'     => 'conditions',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'FieldConditions',
			'context'   => array( 'admin/field', 'block/field' ),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );
		 */

		Fields_Settings::add_group(
			array(
				'name'  => 'default',
				'label' => __(
					'Default value',
					'search-filter-pro'
				),
			),
			$group_args
		);

		$setting = array(
			'name'      => 'defaultValueType',
			'label'     => __( 'Default value', 'search-filter' ),
			'help'      => __( 'Enter a default value for this field.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Select',
			'options'   => array(
				array(
					'value' => 'none',
					'label' => __( 'None', 'search-filter' ),
				),
				array(
					'value'     => 'inherit',
					'label'     => __( 'Inherit from current location', 'search-filter' ),
					'dependsOn' => array(
						'relation' => 'OR',
						'rules'    => array(
							array(
								'option'  => 'type',
								'value'   => 'search',
								'compare' => '=',
							),
							array(
								'option'  => 'type',
								'value'   => 'choice',
								'compare' => '=',
							),
						),
					),
				),
				array(
					'value' => 'custom',
					'label' => __( 'Custom', 'search-filter' ),
				),
			),
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'dependantOptions' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueInheritArchive',
			'label'     => __( 'Inherit from archives', 'search-filter' ),
			'help'      => __( 'Inherit the default value from an archive.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
			'default'   => 'yes',
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
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						'rules'    => array(
							array(
								'option'  => 'taxonomyNavigatesArchive',
								'value'   => 'yes',
								'compare' => '!=',
							),
							array(
								'option'  => 'taxonomyNavigatesArchive',
								'compare' => 'NOT EXISTS',
							),
						),
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueInheritSearch',
			'label'     => __( 'Inherit from search', 'search-filter' ),
			'help'      => __( 'Inherit the default value from the search query.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
			'default'   => 'no',
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
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueInheritPost',
			'label'     => __( 'Inherit from posts', 'search-filter' ),
			'help'      => __( 'Inherit the default value from single posts, pages or CPTs.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field' ),
			'default'   => 'no',
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
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueCustom',
			'label'     => __( 'Custom default value', 'search-filter' ),
			'help'      => __( 'Enter a custom default value for this field.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'custom',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueApplyToQuery',
			'label'     => __( 'Initially apply to query', 'search-filter' ),
			'help'      => __( 'Pre-apply the default value to the query when first loading the page.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'no',
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
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'OR',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'custom',
						'compare' => '=',
					),
					array(
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'labelToggleVisibility',
			'label'       => __( 'Toggle input visibility', 'search-filter-pro' ),
			'help'        => __( 'Click the label show/hide the input.', 'search-filter-pro' ),
			'group'       => 'label',
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Toggle',
			'default'     => 'no',
			'context'     => array( 'admin/field', 'block/field' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter-pro' ),
			'options'     => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter-pro' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter-pro' ),
				),
			),
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'showLabel',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'labelInitialVisibility',
			'label'       => __( 'Initial visibility', 'search-filter' ),
			'group'       => 'label',
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Select',
			'default'     => '',
			'context'     => array( 'admin/field', 'block/field' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter-pro' ),
			'options'     => array(
				array(
					'value' => 'visible',
					'label' => __( 'Visible', 'search-filter-pro' ),
				),
				array(
					'value' => 'hidden',
					'label' => __( 'Hidden', 'search-filter-pro' ),
				),
			),
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'showLabel',
						'compare' => '=',
						'value'   => 'yes',
					),
					array(
						'option'  => 'labelToggleVisibility',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );
	}
}
