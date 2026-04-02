<?php
/**
 * Shortcode parser for extracting Search & Filter shortcodes from content.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features/Shortcodes
 */

namespace Search_Filter\Features\Shortcodes;

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Parses Shortcodes using regex
 */
class Shortcode_Parser {
	/**
	 * Extract blocks from the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to extract fields from.
	 *
	 * @return array|object The extracted fields.
	 */
	public static function extract_fields( $content ) {
		$regex = '/\[searchandfilter(.*?)\]/';
		preg_match_all( $regex, $content, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return array();
		}

		$fields = array();
		foreach ( $matches as $match ) {
			$shortcode_attributes_string = $match[1];
			$shortcode_attributes        = shortcode_parse_atts( $shortcode_attributes_string );
			if ( isset( $shortcode_attributes['field'] ) ) {
				// Then we have a field shortcode.
				// Lookup the field and extrac the field attributes.
				$field_args = array();
				if ( is_numeric( $shortcode_attributes['field'] ) ) {
					$field_args['id'] = $shortcode_attributes['field'];
				} else {
					$field_args['name'] = $shortcode_attributes['field'];
				}
				$field = Field::find( $field_args, 'record' );
				if ( is_wp_error( $field ) ) {
					continue;
				}
				$fields[] = $field;
			}
		}
		return $fields;
	}
}
