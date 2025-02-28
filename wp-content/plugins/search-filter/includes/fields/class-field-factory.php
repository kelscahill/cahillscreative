<?php
/**
 * Class for handling the creation of fields.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Fields;

use Search_Filter\Core\Exception;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$init_fields = array(
	'search'   => array(
		'text' => '\Search_Filter\Fields\Search\Text',
	),
	'choice'   => array(
		'select'   => '\Search_Filter\Fields\Choice\Select',
		'radio'    => '\Search_Filter\Fields\Choice\Radio',
		'checkbox' => '\Search_Filter\Fields\Choice\Checkbox',
		'button'   => '\Search_Filter\Fields\Choice\Button',
	),
	'range'    => array(),
	'advanced' => array(
		'date_picker' => '\Search_Filter\Fields\Advanced\Date_Picker',
	),
	'control'  => array(
		'submit' => '\Search_Filter\Fields\Control\Submit',
		'reset'  => '\Search_Filter\Fields\Control\Reset',
		'sort'   => '\Search_Filter\Fields\Control\Sort',
	),
);

foreach ( $init_fields as $field_type => $input_types ) {
	foreach ( $input_types as $input_type => $class_name ) {
		Field_Factory::register_field_input( $field_type, $input_type, $class_name );
	}
}
/**
 * Instantiates fields using the correct class.
 */
class Field_Factory {
	/**
	 * Keeps track of whether `init` function has been called already.
	 *
	 * @var boolean
	 */
	private static $has_registered_types = false;
	/**
	 * The field classes mapped by their types and their input types.
	 *
	 * @var array
	 */
	private static $fields = array(
		'search'   => array(),
		'choice'   => array(),
		'range'    => array(),
		'advanced' => array(),
		'control'  => array(),
	);
	/**
	 * Initialises mapping of classes for our different field + input types
	 *
	 * @return void
	 */
	public static function register_types() {
		if ( ! self::$has_registered_types ) {
			do_action( 'search-filter/fields/register' );
			self::$has_registered_types = true;
		}
	}
	/**
	 * Sets has registered field
	 *
	 * Necessary for testing as we can't assert in hooks so must be disabled manually.
	 *
	 * @param bool $has_registered_types Whether fields have been registered.
	 */
	public static function set_has_registered_types( $has_registered_types ) {
		self::$has_registered_types = $has_registered_types;
	}
	/**
	 * Registers a class to be used for a field type and sub input type
	 *
	 * @param string $field_type  The main field type - eg, search, filter or control.
	 * @param string $input_type  The input type for a field type.
	 * @param string $class_name  The string name of the class.
	 * @return bool
	 * @throws Exception For various conditions, such as invalid field type or input type.
	 */
	public static function register_field_input( $field_type, $input_type, $class_name ) {
		/**
		 * Note: using phpcs:ignore after the exceptions because the rule `WordPress.Security.EscapeOutput.ExceptionNotEscaped`
		 * is being triggered because the last argument is not escaped - but this is not used in the message or displayed to the user,
		 * it's a constant/error code used in our custom exception class.
		 */
		if ( true === self::$has_registered_types ) {
			throw new Exception( esc_html__( 'You can only register fields inside the action "search-filter/fields/register".', 'search-filter' ), SEARCH_FILTER_EXCEPTION_FIELD_NOT_READY ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! isset( self::$fields[ $field_type ] ) ) {
			/* translators: %s is the internal field type - eg search/filter/control */
			throw new Exception( sprintf( esc_html__( 'The field type "%1$s" has not been found.', 'search-filter' ), esc_html( $field_type ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( isset( self::$fields[ $field_type ][ $input_type ] ) ) {
			/* translators: %1$s is the internal field type - eg search/filter/control', %2$s is the internal input type - eg radio/checkbox/text */
			throw new Exception( sprintf( esc_html__( 'The input type "%1$s" has already been registered for the "%2$s" field type.', 'search-filter' ), esc_html( $input_type ), esc_html( $field_type ) ), SEARCH_FILTER_EXCEPTION_FIELD_EXISTS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Make sure it exists.
		if ( ! class_exists( $class_name ) ) {
			/* translators: %s is the PHP class name */
			throw new Exception( sprintf( esc_html__( 'The class `%1$s` cannot be found.', 'search-filter' ), esc_html( $class_name ) ), SEARCH_FILTER_EXCEPTION_FIELD_CLASS_MISSING ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Remove this once we hit 3.1.x.
		if ( defined( 'SEARCH_FILTER_PRO_VERSION' ) && version_compare( SEARCH_FILTER_PRO_VERSION, '3.0.2', '<' ) ) {
			// Run the static init function `register` if it exists.
			if ( method_exists( $class_name, 'render' ) ) {
				$class_name::register();
			}
		} elseif ( self::class_ready( $class_name ) === true ) {
			$class_name::register();
		}

		self::$fields[ $field_type ][ $input_type ] = $class_name;
		return true;
	}
	/**
	 * Replaces a field class with the one passed
	 *
	 * @param string $field_type  The main type - eg, search, filter or control.
	 * @param string $input_type  The input type for a field type.
	 * @param string $class_name  The string name of the class.
	 * @return bool
	 * @throws Exception If the fields have already been registered.
	 */
	public static function update_field_input( $field_type, $input_type, $class_name ) {

		if ( true === self::$has_registered_types ) {
			// Use phpcs ignore - we're using a custom exception class and the second paramater is not out.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( esc_html__( 'You can only update fields inside the action "search-filter/fields/register".', 'search-filter' ), SEARCH_FILTER_EXCEPTION_FIELD_NOT_READY );
		}
		// TODO - we need to check if the field + input type exist first.
		self::$fields[ $field_type ][ $input_type ] = $class_name;
		return true;
	}
	/**
	 * Has registered field input.
	 *
	 * @since 3.0.0
	 *
	 * @param string $field_type The field type.
	 * @param string $input_type The input type.
	 *
	 * @return bool True if the field input is registered.
	 */
	public static function has_registered_field_input( $field_type, $input_type ) {
		return isset( self::$fields[ $field_type ][ $input_type ] );
	}

	/**
	 * Gets a field input class by attributes field type and input type.
	 *
	 * @param array $attributes  Field attributes, can vary based on the `type` attribute.
	 * @return string
	 */
	private static function get_field_class( $attributes ) {
		$field_atts = self::get_field_atts( $attributes );
		$field_type = $field_atts['fieldType'];
		$input_type = $field_atts['inputType'];
		// TODO - is this working for control fields?

		if ( ! isset( self::$fields[ $field_type ][ $input_type ] ) ) {

			// If class_ref is false it means the field type / input type combination does not exist.
			/* translators: %1$s is the internal field type - eg search/filter/control, %2$s is the internal input type - eg radio/checkbox/text */
			Util::error_log( sprintf( __( 'Class not found for `%1$s` / `%2$s`.', 'search-filter' ), esc_html( $field_type ), esc_html( $input_type ) ), 'error' );
			return null;
		}
		return self::$fields[ $field_type ][ $input_type ];
	}

	/**
	 * Get the field attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes The field attributes.
	 *
	 * @return array The field attributes.
	 *
	 * @throws Exception If the field doesn't have the required attributes.
	 */
	public static function get_field_atts( $attributes ) {

		if ( ! isset( $attributes['type'] ) ) {
			// Use phpcs ignore - we're using a custom exception class and the second paramater is not out.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( esc_html__( 'The field type has not been set.', 'search-filter' ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE );
		}

		$field_type = $attributes['type'];
		if ( ! isset( self::$fields[ $field_type ] ) ) {
			// Use phpcs ignore - we're using a custom exception class and the second paramater is not out.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( esc_html__( 'Invalid field type.', 'search-filter' ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE );
		}

		if ( ! isset( $attributes['inputType'] ) && ! isset( $attributes['controlType'] ) ) {
			// Use phpcs ignore - we're using a custom exception class and the second paramater is not out.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( esc_html__( 'The input or control type has not been set.', 'search-filter' ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_INPUT_TYPE );
		}

		$input_type = '';
		if ( $field_type === 'control' ) {
			$input_type = $attributes['controlType'];
		} else {
			$input_type = $attributes['inputType'];
		}

		if ( ! isset( self::$fields[ $field_type ][ $input_type ] ) ) {
			/* translators: %1$s is the internal input type - eg radio/checkbox/text, %2$s is the internal field type - eg search/filter/control */
			Util::error_log( sprintf( __( 'The input type `%1$s` does not have a class associated with it for field type `%2$s`. Use `register_field_input`.', 'search-filter' ), esc_html( $input_type ), esc_html( $field_type ) ), 'error' );
		}
		return array(
			'inputType' => $input_type,
			'fieldType' => $field_type,
		);
	}

	/**
	 * Check if a class is ready to be used.
	 *
	 * Fields need at least the render and create methods.
	 *
	 * @since 3.0.0
	 *
	 * @param object $class_ref The class reference.
	 * @return bool True if the class is ready.
	 *
	 * @throws Exception If the class does not have a render method or a create method.
	 */
	private static function class_ready( $class_ref ) {

		if ( empty( $class_ref ) ) {
			return new \WP_Error( 'search_filter_field_class_not_found', __( 'Class not found.', 'search-filter' ), array( 'status' => 404 ) );
		}

		/**
		 * Note: using phpcs:ignore after the exceptions because the rule `WordPress.Security.EscapeOutput.ExceptionNotEscaped`
		 * is being triggered because the last argument is not escaped - but this is not used in the message or displayed to the user,
		 * it's a constant/error code used in our custom exception class.
		 */
		// Make sure that it has a render method.
		if ( ! method_exists( $class_ref, 'render' ) ) {
			/* translators: %s is the class name */
			throw new Exception( sprintf( esc_html__( 'The class `%1$s` does not have a public `render()` method.', 'search-filter' ), esc_html( $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_RENDER_METHOD ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		// Make sure that it has a create method.
		if ( ! method_exists( $class_ref, 'create' ) ) {
			/* translators: %s is the class name */
			throw new Exception( sprintf( esc_html__( 'The class `%1$s` does not have a public static `create()` method.', 'search-filter' ), esc_html( $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_CREATE_METHOD ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Make sure that it has a type property thats not empty.
		if ( empty( $class_ref::$type ) ) {
			/* translators: %s is the class name */
			throw new Exception( sprintf( esc_html__( 'The class `%1$s` does not have a type set.', 'search-filter' ), esc_html( $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		// Make sure that it has an input type property thats not empty.
		if ( empty( $class_ref::$input_type ) ) {
			/* translators: %s is the class name */
			throw new Exception( sprintf( esc_html__( 'The class `%1$s` does not have a input type set.', 'search-filter' ), esc_html( $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_INPUT_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return true;
	}

	/**
	 * Creates a field from attributes.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes  Field attributes, can vary based on the `type` attribute.
	 * @param array $context Context for the field.
	 *
	 * @return class|false False if no class was found.
	 * @throws Exception For various conditions, such as invalid field type or input type in the attributes.
	 */
	public static function create( $attributes, $context = array() ) {

		// Lookup the class associated with the input type.
		$field_class_ref = self::get_field_class( $attributes );

		if ( self::class_ready( $field_class_ref ) === true ) {
			// Now create an instance of the correct Field class.
			$field = $field_class_ref::create( $attributes, $context );
			return $field;
		}

		return false;
	}

	/**
	 * Creates a field from db record.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @param stdClass $item  The database record for the field.
	 * @return class
	 * @throws Exception For multiple conditions, such as invalid field type or input type.
	 */
	public static function create_from_record( $item ) {
		$attributes = $item->get_attributes();
		// Lookup the class associated with the input type.
		$field_class_ref = self::get_field_class( $attributes );

		if ( self::class_ready( $field_class_ref ) === true ) {
			// Now create an instance of the correct Field class.
			$field = $field_class_ref::create_from_record( $item );
			return $field;
		}

		return new \WP_Error( 'search_filter_field_class_not_found', __( 'Class not found.', 'search-filter' ), array( 'status' => 404 ) );
	}
	/**
	 * Creates a field from the record ID.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @param number $id  The database record for the field.
	 * @return class
	 * @throws Exception For multiple conditions, such as invalid field type or input type.
	 */
	public static function create_from_id( $id ) {
		$field = Field::find( array( 'id' => $id ) );

		if ( is_wp_error( $field ) ) {
			return $field;
		}

		// Lookup the class associated with the input type.
		$field_class_ref = self::get_field_class( $field->get_attributes() );

		if ( self::class_ready( $field_class_ref ) === true ) {
			// Now create an instance of the correct Field class.
			$field = $field_class_ref::create_from_record( $field->get_record() );
			return $field;
		}
		// TODO - we probably want to throw an exception or error here.
		return new \WP_Error( 'search_filter_field_unable_to_create', __( 'Unable to create field.', 'search-filter' ), array( 'status' => 404 ) );
	}

	/**
	 * Gets the list of registered field input types
	 */
	public static function get_field_input_types() {
		return self::$fields;
	}
}
