<?php
/**
 * Block Parser Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations/Gutenberg
 */

namespace Search_Filter\Integrations\Gutenberg;

use Search_Filter\Fields\Field;
use Search_Filter\Fields\Field_Factory;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses Gutenberg blocks using regex
 */
class Block_Parser {
	/**
	 * Extract blocks from the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to extract blocks from.
	 * @param array  $restrict_types The types to restrict to.
	 *
	 * @return array|object The extracted blocks.
	 */
	public static function extract_fields( string $content, array $restrict_types = array() ) {

		$regex_tag = '<!-- wp:search-filter/(.*?) (.*?) /-->';
		$pattern   = '/' . self::escape_pattern_slashes( $regex_tag ) . '/';

		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return array();
		}

		$fields = array();
		foreach ( $matches as $match ) {
			if ( empty( $match ) || count( $match ) !== 3 ) {
				continue;
			}
			$block_json_string = $match[2];
			$field_type        = $match[1];

			if ( ! empty( $restrict_types ) && ! in_array( $field_type, $restrict_types, true ) ) {
				continue;
			}

			$block_attributes = self::parse_block( $block_json_string );

			if ( ! isset( $block_attributes['fieldId'] ) ) {
				// Then the field hasn't been selected yet.
				continue;
			}
			// Then we need to get the field from the database.
			$field = Field::get_instance( absint( $block_attributes['fieldId'] ) );
			if ( ! is_wp_error( $field ) ) {
				// Add back in the  field ID.
				$fields[] = $field;
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
	private static function escape_pattern_slashes( string $pattern ) {
		return str_replace( '/', '\/', $pattern );
	}

	/**
	 * Parse a block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_str The block string.
	 *
	 * @return array The parsed block.
	 */
	public static function parse_block( string $block_str ) {
		$attributes = json_decode( $block_str, true );
		return $attributes;
	}

	/**
	 * Fast check if content contains Search & Filter blocks.
	 * Returns early on first match for maximum performance.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content    The content to check.
	 * @param string $block_type Optional. Specific block type to check (e.g., 'reusable').
	 *                          If empty, checks for any Search & Filter block.
	 *
	 * @return bool True if block(s) exist, false otherwise.
	 */
	public static function has_block( string $content, string $block_type = '' ) {
		// Build the search string.
		if ( empty( $block_type ) ) {
			// Check for ANY Search & Filter block.
			$search_string = '<!-- wp:search-filter/';
		} else {
			// Check for specific block type (with space to ensure it's a block comment start).
			$search_string = '<!-- wp:search-filter/' . $block_type . ' ';
		}

		// Fast string search - returns early on first match.
		return strpos( $content, $search_string ) !== false;
	}
}
