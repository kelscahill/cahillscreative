<?php
namespace Search_Filter\Integrations\Gutenberg;

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Parses Gutenberg blocks using regex
 */
class Shortcode_Parser {
	/**
	 * Extract blocks from the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to extract blocks from.
	 * @param string $return_type  The return type. Either `array` or `field` or `raw`.
	 *
	 * @return array|object The extracted blocks.
	 */
	public static function extract_shortcodes( $content ) {
		$blocks = self::extract_all_shortcodes( $content );
		return $blocks;
	}


	public static function init() {}
	/**
	 * Extract all S&F blocks from the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to extract blocks from.
	 *
	 * @return array The extracted blocks.
	 */
	public static function extract_all_shortcodes( $content ) {

		$regex = '/\[searchandfilter(.*?)\]/';
		preg_match_all( $regex, $content, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return array();
		}

		$fields = array();
		foreach ( $matches as $match ) {
			if ( empty( $match ) || count( $match ) !== 2 ) {
				continue;
			}
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

				$fields[] = $field->get_attributes();
			}
		}
		return $fields;
	}

	/**
	 * Escapes the slashes in the regex pattern.
	 *
	 * @since 3.0.0
	 *
	 * @param string $pattern The pattern to escape.
	 *
	 * @return string The escaped pattern.
	 */
	private static function escape_pattern_slashes( $str ) {
		return str_replace( '/', '\/', $str );
	}

	/**
	 * Parse a block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_str The block string.
	 * @param string $type      The block type.
	 *
	 * @return array The parsed block.
	 */
	public static function parse_block( $block_str, $type ) {
		$attributes = json_decode( $block_str, true );
		return $attributes;
	}
}
