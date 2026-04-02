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

use Search_Filter\Fields\Field;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Show_Hide {

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		add_filter( 'search-filter/fields/field/render/html_classes', array( __CLASS__, 'add_html_render_classes' ), 20, 2 );
		add_filter( 'search-filter/fields/field/render/html_attributes', array( __CLASS__, 'add_html_render_attributes' ), 20, 2 );
		add_filter( 'search-filter/fields/field/render_data', array( __CLASS__, 'apply_hidden_field_attributes' ), 20, 2 );
	}
	/**
	 * Check if the field should be hidden.
	 *
	 * @param Field $field The field to check.
	 * @return boolean
	 */
	private static function should_hide_field( $field ) {

		// Return early for fields without the option enabled.
		$hide_field_when_empty_attribute = $field->get_attribute( 'hideFieldWhenEmpty' ) === 'yes';
		if ( ! $hide_field_when_empty_attribute ) {
			return false;
		}

		// Never hide fields that have values set by the user.
		$values = $field->get_values();
		if ( ! empty( $values ) ) {
			return false;
		}

		$field_type = $field->get_attribute( 'type' );
		if ( $field_type !== 'choice' && $field_type !== 'range' && $field_type !== 'control' ) {
			return false;
		}

		if ( $field_type === 'choice' ) {
			if ( count( $field->get_options() ) > 0 ) {
				return false;
			}
		} elseif ( $field_type === 'range' ) {
			if ( $field->get_attribute( 'rangeMinQuery' ) === null && $field->get_attribute( 'rangeMaxQuery' ) === null ) {
				return false;
			}
		} elseif ( $field_type === 'control' ) {
			if ( $field->get_attribute( 'controlType' ) !== 'selection' ) {
				return false;
			}
			if ( count( $field->get_options() ) > 0 ) {
				return false;
			}
		}

		return true;
	}
	/**
	 * Add classes to the field based on the hide field when empty setting.
	 *
	 * @param array<string> $classes The classes to add.
	 * @param Field         $field   The field to add the classes to.
	 * @return array<string> The classes to add.
	 */
	public static function add_html_render_classes( $classes, $field ) {

		if ( ! self::should_hide_field( $field ) ) {
			return $classes;
		}

		$classes[] = 'search-filter-field--hidden';

		return $classes;
	}

	/**
	 * Add the aria-hidden attribute to the field.
	 *
	 * @param array $attributes The attributes to add.
	 * @param Field $field      The field to add the attributes to.
	 * @return array The attributes to add.
	 */
	public static function add_html_render_attributes( $attributes, $field ) {
		if ( ! self::should_hide_field( $field ) ) {
			return $attributes;
		}

		$attributes['aria-hidden'] = 'true';

		return $attributes;
	}

	/**
	 * Set the render data for the field.
	 *
	 * @param array $render_data The render data to update.
	 * @param Field $field       The field to update the render data for.
	 * @return array The updated render data.
	 */
	public static function apply_hidden_field_attributes( $render_data, $field ) {

		if ( ! self::should_hide_field( $field ) ) {
			return $render_data;
		}

		// If a field is  aria-hidden it should not be tabbable, so we need to
		// disable the interactivity which does this for us.
		$render_data['isInteractive'] = false;

		return $render_data;
	}
}
