<?php
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
	 * @param string $return_type  The return type. Either `array` or `field` or `raw`.
	 *
	 * @return array|object The extracted blocks.
	 */
	public static function extract_blocks( $content ) {
		$blocks = self::extract_all_blocks( $content );
		return $blocks;
	}


	/**
	 * Extract all S&F blocks from the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to extract blocks from.
	 *
	 * @return array The extracted blocks.
	 */
	public static function extract_all_blocks( $content ) {

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
			$block_attributes  = self::parse_block( $block_json_string, $field_type );

			/**
			 * Check to see if a block doesn't have a `queryId`
			 * Then it will be a reusable block - so lookup its query_id.
			 * TODO - probably needs optimizing for re-usable fields - this is only necessary
			 * for re-usable fields that are connected to a "dynamic" query on this page.
			 * Essentially the field needs to declare that it is connected to a query, so we know
			 * the query should be affecting this single post/page.
			 */
			if ( ! isset( $block_attributes['queryId'] ) ) {
				if ( ! isset( $block_attributes['fieldId'] ) ) {
					continue;
				}
				$field = Field::find(
					array(
						'id' => $block_attributes['fieldId'],
					),
					'record'
				);
				if ( ! is_wp_error( $field ) ) {
					$fields[] = $field->get_attributes();
				}
			} else {
				$fields[] = $block_attributes;
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
