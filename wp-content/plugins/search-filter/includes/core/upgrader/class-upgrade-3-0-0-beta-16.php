<?php

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Styles\Style;

class Upgrade_3_0_0_Beta_16 {

	public static function upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field, query and style.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );

		// First, update any fields that are using the `filter` type to `choice`,
		// do it directly using the DB because otherwise the fields will throw an error.
		$fields = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);
		foreach ( $fields as $field ) {

			// Update filter type to choice.
			$attributes = $field->get_attributes();

			if ( ! isset( $attributes['inputType'] ) ) {
				continue;
			}

			// Upgrade any legacy input types which used to have a prefix.
			$prefixes = array(
				'search-',
				'filter-',
				'control-',
				'choice-',
				'advanced-',
				'range-',
			);

			// If input type starts with a legacy prefix, remove it.
			foreach ( $prefixes as $prefix ) {
				if ( strpos( $attributes['inputType'], $prefix ) === 0 ) {
					$attributes['inputType'] = substr( $attributes['inputType'], strlen( $prefix ) );
				}
			}

			$record = array(
				'attributes' => wp_json_encode( (object) $attributes ),
			);

			$query  = new \Search_Filter\Database\Queries\Fields();
			$result = $query->update_item( $field->get_id(), $record );
		}

		// Resave queries.
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			$query->save();
		}

		// Resave styles.
		$styles = \Search_Filter\Styles::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}
			$style->save();
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );
		// Now build the CSS once.
		CSS_Loader::save_css();
	}

	public static function disable_css_save() {
		return false;
	}

	public static function upgrade_styles_attributes( $previous_attributes ) {
		// Start with the default attributes as a base for the structure.
		$default_attributes = Style::generate_default_attributes();
		// Use a map to map the old attributes to the new ones.
		$attribute_map = array(
			'labelColor'                   => 'labelTextColor',
			// 'labelBackgroundColor'         => '',
			'descriptionColor'             => 'labelTextColor',
			// 'descriptionBackgroundColor'   => '',
			'inputColor'                   => 'textColor',
			'inputBackgroundColor'         => 'backgroundColor',
			'inputSelectedColor'           => 'activeTextColor',
			'inputSelectedBackgroundColor' => 'activeBackgroundColor',
			'inputBorderColor'             => 'primaryAccentColor',
			'inputBorderHoverColor'        => 'secondaryAccentColor',
			'inputBorderFocusColor'        => 'tertiaryAccentColor',
			'inputIconColor'               => 'primaryAccentColor',
			'inputActiveIconColor'         => 'activeBackgroundColor',
			'inputInactiveIconColor'       => 'primaryAccentColor',
			'inputInteractiveColor'        => 'primaryAccentColor',
			'inputInteractiveHoverColor'   => 'tertiaryAccentColor',
			'inputClearColor'              => 'primaryAccentColor',
			'inputClearHoverColor'         => 'tertiaryAccentColor',

		);

		$new_attributes = array();
		foreach ( $default_attributes as $field_type => $input_types ) {
			$new_attributes[ $field_type ] = array();
			$input_types_keys              = array_keys( $input_types );
			foreach ( $input_types_keys as $input_type ) {
				$new_attributes[ $field_type ][ $input_type ] = array();
				$default_input_attributes                     = $default_attributes[ $field_type ][ $input_type ];
				foreach ( $default_input_attributes as $attribute_name => $default_value ) {
					// Check if we have a mapping for this attribute.
					if ( isset( $attribute_map[ $attribute_name ] ) ) {
						$mapped_attribute_name = $attribute_map[ $attribute_name ];
						// Update the value from the old one.
						$new_attributes[ $field_type ][ $input_type ][ $attribute_name ] = $previous_attributes[ $mapped_attribute_name ];
					} else {
						// If not, use the default value.
						$new_attributes[ $field_type ][ $input_type ][ $attribute_name ] = $default_value;
					}
				}
			}
		}
		return $new_attributes;
	}
}
