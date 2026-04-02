<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Fields\Field_Factory;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries\Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Fields {

	/**
	 * The field defaults.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $field_defaults = array(
		'search'   => array(
			'text'         => array(
				'autoSubmitDelay' => '600',
			),
			'autocomplete' => array(
				'autoSubmitDelay' => '1500',
			),
		),
		'choice'   => array(
			'select'   => array(
				'autoSubmitDelay' => '0',
			),
			'radio'    => array(
				'autoSubmitDelay' => '0',
			),
			'checkbox' => array(
				'autoSubmitDelay' => '0',
			),
			'button'   => array(
				'autoSubmitDelay' => '0',
			),
		),
		'range'    => array(
			'slider' => array(
				'autoSubmitDelay' => '400',
			),
			'radio'  => array(
				'autoSubmitDelay' => '0',
			),
			'select' => array(
				'autoSubmitDelay' => '0',
			),

		),
		'advanced' => array(
			'date_picker' => array(
				'autoSubmitDelay' => '0',
			),
		),
		'control'  => array(),
	);

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		add_action( 'search-filter/fields/register', array( __CLASS__, 'register_fields' ), 10 );
		// Enable the various input types for when a custom field is selected.
		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );

		// Update a fields registered icons.
		add_filter( 'search-filter/fields/field/get_icons', array( __CLASS__, 'add_field_icons' ), 10, 2 );

		// Add indexer support.
		\Search_Filter_Pro\Fields\Indexer::init();

		// Add pro data types.
		\Search_Filter_Pro\Fields\Data_Types\Custom_Field::init();
		\Search_Filter_Pro\Fields\Data_Types\Authors::init();

		// Add pro features.
		\Search_Filter_Pro\Fields\Features\Settings::init();
		\Search_Filter_Pro\Fields\Features\Defaults::init();
		\Search_Filter_Pro\Fields\Features\Url_Arg::init();
		\Search_Filter_Pro\Fields\Features\Show_Hide::init();

		// Register the default values for the field/input type combinations.
		add_filter( 'search-filter/fields/field/get_attributes', array( __CLASS__, 'get_attributes' ), 1, 2 );
	}

	/**
	 * Get the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @param    string $input_type    The input type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function get_field_setting_support( $setting_support, $type, $input_type ) {

		// Add support pro feature support to choice fields.
		if ( $type === 'choice' ) {
			// Add support for thee custom field data type.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataType', array( 'custom_field' => true ) );
			// Add support for the authors data post attribute.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataPostAttribute', array( 'post_author' => true ) );

			// Add support for the custom field setting & notice.
			$setting_support['dataCustomField']              = true;
			$setting_support['dataCustomFieldIndexerNotice'] = true;
			$setting_support['autoSubmit']                   = true;
			$setting_support['autoSubmitDelay']              = true;
			$setting_support['hideFieldWhenEmpty']           = true;

			$setting_support['defaultValueType']           = true;
			$setting_support['defaultValueInheritArchive'] = true;
			$setting_support['defaultValueInheritPost']    = true;
			$setting_support['defaultValueCustom']         = true;
			$setting_support['defaultValueApplyToQuery']   = true;

			$setting_support['dataPostAuthorConditions']   = true;
			$setting_support['dataPostAuthors']            = true;
			$setting_support['dataPostAuthorCapabilities'] = true;
			$setting_support['dataPostAuthorRoles']        = true;

			$setting_support['labelInitialVisibility'] = true;
			$setting_support['labelToggleVisibility']  = true;

			// Support custom data URL name.
			$setting_support['dataUrlName'] = true;

			// Add show count + hide empty to choice fields, for indexed queries.
			$indexed_fields_conditions = array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '=',
				'value'   => 'yes',
			);

			$setting_support['showCount'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'showCount', $indexed_fields_conditions, false ),
			);
			$setting_support['hideEmpty'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'hideEmpty', $indexed_fields_conditions, false ),
			);
		}
		if ( $type === 'control' ) {
			if ( $input_type === 'sort' || $input_type === 'per_page' ) {
				$setting_support['dataUrlName']            = true;
				$setting_support['autoSubmit']             = true;
				$setting_support['autoSubmitDelay']        = true;
				$setting_support['labelInitialVisibility'] = true;
				$setting_support['labelToggleVisibility']  = true;
			}
		}
		if ( $type === 'advanced' ) {
			if ( $input_type === 'date_picker' ) {
				$setting_support['dataUrlName']            = true;
				$setting_support['autoSubmit']             = true;
				$setting_support['autoSubmitDelay']        = true;
				$setting_support['labelInitialVisibility'] = true;
				$setting_support['labelToggleVisibility']  = true;

				$setting_support['defaultValueType']           = true;
				$setting_support['defaultValueInheritArchive'] = true;
				$setting_support['defaultValueInheritPost']    = true;
				$setting_support['defaultValueCustom']         = true;
				$setting_support['defaultValueApplyToQuery']   = true;
			}
		}

		return $setting_support;
	}

	/**
	 * Update field icons to add support when using `labelToggleVisibility`
	 *
	 * @param array  $icons The field icons.
	 * @param object $field The field object.
	 * @return array The updated icons.
	 */
	public static function add_field_icons( $icons, $field ) {

		if ( $field->get_attribute( 'labelToggleVisibility' ) === 'yes' ) {
			$icons[] = 'arrow-down';
		}

		return $icons;
	}

	/**
	 * Filter the field get_attributes() to add in defaults.
	 *
	 * @since 3.0.0
	 *
	 * @param    array                       $attributes    The attributes to set.
	 * @param    \Search_Filter\Fields\Field $field    The field to set the attributes for.
	 *
	 * @return   array    The updated attributes.
	 */
	public static function get_attributes( $attributes, $field ) {
		if ( ! $field->has_init() ) {
			return $attributes;
		}

		$field_type       = $field->get_attribute( 'type', true );
		$field_input_type = $field->get_attribute( 'inputType', true );

		if ( ! isset( self::$field_defaults[ $field_type ] ) ) {
			return $attributes;
		}
		if ( ! isset( self::$field_defaults[ $field_type ][ $field_input_type ] ) ) {
			return $attributes;
		}

		$new_attributes = self::$field_defaults[ $field_type ][ $field_input_type ];

		foreach ( $new_attributes as $key => $value ) {
			if ( ! isset( $attributes[ $key ] ) || $attributes[ $key ] === '' ) {
				$attributes[ $key ] = $value;
			}
		}
		return $attributes;
	}

	/**
	 * Register the fields.
	 *
	 * @since 3.0.0
	 */
	public static function register_fields() {
		// Register Pro fields.

		Field_Factory::update_field_input( 'search', 'autocomplete', 'Search_Filter_Pro\Fields\Search\Autocomplete' );
		Field_Factory::update_field_input( 'control', 'selection', 'Search_Filter_Pro\Fields\Control\Selection' );
		Field_Factory::update_field_input( 'control', 'load_more', 'Search_Filter_Pro\Fields\Control\Load_More' );

		// Replace the text search field to add pro features.
		Field_Factory::update_field_input( 'search', 'text', 'Search_Filter_Pro\Fields\Search\Text' );

		Field_Factory::update_field_input( 'range', 'slider', 'Search_Filter_Pro\Fields\Range\Slider' );
		Field_Factory::update_field_input( 'range', 'select', 'Search_Filter_Pro\Fields\Range\Select' );
		Field_Factory::update_field_input( 'range', 'radio', 'Search_Filter_Pro\Fields\Range\Radio' );
		Field_Factory::update_field_input( 'range', 'number', 'Search_Filter_Pro\Fields\Range\Number' );
		Field_Factory::update_field_input( 'advanced', 'date_picker', 'Search_Filter_Pro\Fields\Advanced\Date_Picker' );
	}

	/**
	 * Get the query object for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to get the query for.
	 * @return   Query|null      The query object or null if not found.
	 */
	public static function get_field_query( $field ) {
		$query_id = $field->get_attribute( 'queryId' );
		if ( ! $query_id ) {
			return null;
		}

		$query = Query::get_instance( absint( $query_id ) );
		if ( is_wp_error( $query ) ) {
			return null;
		}
		return $query;
	}
}
