<?php
/**
 * Search Index Strategy.
 *
 * Handles indexing for search fields using an inverted index.
 * Extracts text content from multiple data sources and stores it
 * as tokenized terms for full-text search with BM25 ranking.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Strategy
 */

namespace Search_Filter_Pro\Indexer\Strategy;

use Search_Filter\Fields\Field;
use Search_Filter\Util;
use Search_Filter_Pro\Fields;
use Search_Filter_Pro\Indexer\Search\Indexer as Search_Indexer;
use Search_Filter_Pro\Indexer\Search\Database\Search_Query_Direct;
use Search_Filter_Pro\Indexer\Search\Manager as Search_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Index Strategy.
 *
 * Indexes text content (titles, content, excerpts, custom fields)
 * into an inverted index for full-text search capabilities.
 *
 * Unlike bitmap/bucket strategies that extract VALUES (slugs, numbers),
 * this strategy extracts CONTENT (full text) from configured data sources.
 *
 * @since 3.2.0
 */
class Search_Strategy implements Index_Strategy {

	/**
	 * Supported interaction types.
	 *
	 * @var string[]
	 */
	protected $interaction_types = array( 'search' );

	/**
	 * Search indexer instance.
	 *
	 * @var Search_Indexer|null
	 */
	private static $indexer = null;

	/**
	 * Check if this strategy supports the given field.
	 *
	 * Uses the field's interaction_type to determine support.
	 * Search strategy handles 'search' interaction types with useIndexer enabled.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to check.
	 * @return bool True if this strategy handles the field.
	 */
	public function supports( Field $field ): bool {

		// Get interaction type from the field instance.
		$interaction_type = $field->get_interaction_type();

		$supports = $this->supports_interaction_type( $interaction_type );

		return apply_filters(
			'search-filter-pro/indexer/strategy/supports',
			$supports,
			$field,
			$this
		);
	}

	/**
	 * Check if this strategy supports the given field's interaction type.
	 *
	 * Uses the field's interaction_type to determine support.
	 * Search strategy handles 'search' interaction types.
	 *
	 * @since 3.2.0
	 *
	 * @param string $interaction_type The interaction type to check.
	 * @return bool True if this strategy handles the interaction type.
	 */
	public function supports_interaction_type( $interaction_type ): bool {
		return in_array( $interaction_type, $this->interaction_types, true );
	}

	/**
	 * Get the interaction types this strategy supports.
	 *
	 * @since 3.2.0
	 *
	 * @return string[] Array of supported interaction types.
	 */
	public function get_interaction_types(): array {
		return $this->interaction_types;
	}

	/**
	 * Get the strategy type identifier.
	 *
	 * @since 3.2.0
	 *
	 * @return string The strategy type.
	 */
	public function get_type(): string {
		return 'search';
	}

	/**
	 * Extract indexable content from a post for a field.
	 *
	 * Returns an array of field_name => content pairs, where content
	 * is the text to be tokenized and indexed.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param Field $field     The field instance.
	 * @return array Array of field_name => content pairs.
	 */
	public function extract( int $object_id, Field $field ): array {
		$data_sources = $field->get_attribute( 'dataSources' );

		if ( ! is_array( $data_sources ) || empty( $data_sources ) ) {
			return array();
		}

		$fields_data = array();

		foreach ( $data_sources as $source ) {
			if ( ! isset( $source['dataType'] ) ) {
				continue;
			}

			$content = $this->extract_source_content( $object_id, $source, $field );

			$content = $this->apply_field_formatting( $content, $field );

			if ( ! empty( $content ) ) {
				$field_name                 = $this->build_field_name( $source );
				$fields_data[ $field_name ] = array(
					'content'     => $content,
					'exact_match' => isset( $source['exactMatch'] ) && $source['exactMatch'] === 'yes',
				);
			}
		}

		return $fields_data;
	}

	/**
	 * Apply field-specific formatting to extracted data.
	 *
	 * Allows fields to modify extracted data before indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $data The extracted data.
	 * @param Field $field  The field instance.
	 * @return mixed The formatted data.
	 */
	public function apply_field_formatting( $data, Field $field ) {
		// Allow fields to format index data.
		if ( method_exists( $field, 'prepare_index_data' ) ) {
			$data = $field->prepare_index_data( $data, $this->get_type() );
		}
		return $data;
	}

	/**
	 * Extract content from a single data source.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param array $source    The data source configuration.
	 * @param Field $field     The field instance.
	 * @return string|null The extracted content or null.
	 */
	private function extract_source_content( int $object_id, array $source, Field $field ): ?string {
		$data_type = $source['dataType'];

		// Allow integrations to override extraction for any data type.
		// Return non-null to skip built-in extraction.
		$override = apply_filters(
			'search-filter-pro/indexer/sync_field_search_index/override_values',
			null,
			$source,
			$field,
			$object_id
		);

		if ( null !== $override ) {
			return $override;
		}

		switch ( $data_type ) {
			case 'post_attribute':
				return $this->extract_post_attribute( $object_id, $source );

			case 'taxonomy':
				return $this->extract_taxonomy( $object_id, $source );

			case 'custom_field':
				return $this->extract_custom_field( $object_id, $source );

			default:
				return null;
		}
	}

	/**
	 * Index a single post for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id        The post/object ID.
	 * @param Field $field            The field instance.
	 * @return bool True on success.
	 */
	public function index( int $object_id, Field $field ): bool {
		// Guard: skip if search tables shouldn't exist.
		if ( ! Search_Manager::should_use() ) {
			return false;
		}

		$field_id = $field->get_id();

		// Clear existing index data for this object/field.
		$this->clear( $field_id, $object_id );

		// Extract content.
		$fields_data = $this->extract( $object_id, $field );

		if ( empty( $fields_data ) ) {
			return true; // Nothing to index is success.
		}

		// Apply content size limits.
		$fields_data = $this->apply_content_limits( $fields_data, $object_id );

		// Get language for this document.
		$language = apply_filters(
			'search-filter-pro/indexer/search/document_language',
			'en',
			$object_id,
			$field
		);

		// Write to search index.
		$indexer = $this->get_indexer();
		$success = $indexer->index_document( $object_id, $fields_data, $language, $field_id );

		if ( ! $success ) {
			Util::error_log(
				sprintf(
					'Search Filter: Failed to index search document for post %d, field %d',
					$object_id,
					$field_id
				),
				'error'
			);
		}

		return $success;
	}

	/**
	 * Clear index data for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id  The field ID.
	 * @param int $object_id The object ID to clear, or -1 for all.
	 * @return bool True on success.
	 */
	public function clear( int $field_id, int $object_id = -1 ): bool {
		if ( $object_id === -1 ) {
			return Search_Query_Direct::clear_field_postings( $field_id );
		}

		$indexer = $this->get_indexer();
		return $indexer->delete_document( $object_id, $field_id );
	}

	/**
	 * Extract post attribute content.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post ID.
	 * @param array $source    The data source configuration.
	 * @return string|null The content or null.
	 */
	private function extract_post_attribute( int $object_id, array $source ): ?string {
		if ( ! isset( $source['dataPostAttribute'] ) ) {
			return null;
		}

		$attribute = $source['dataPostAttribute'];
		$post      = get_post( $object_id );

		if ( ! $post ) {
			return null;
		}

		switch ( $attribute ) {
			case 'post_title':
				return ! empty( $post->post_title ) ? $post->post_title : null;

			case 'post_content':
				if ( empty( $post->post_content ) ) {
					return null;
				}
				// Strip HTML, optionally process shortcodes.
				$process_shortcodes = apply_filters(
					'search-filter-pro/indexer/search/process_shortcodes',
					false,
					$object_id
				);
				if ( $process_shortcodes ) {
					return wp_strip_all_tags( do_shortcode( $post->post_content ) );
				}
				return wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

			case 'post_excerpt':
				return ! empty( $post->post_excerpt ) ? wp_strip_all_tags( $post->post_excerpt ) : null;

			case 'post_type':
				$post_type = get_post_type( $object_id );
				if ( ! $post_type ) {
					return null;
				}
				$post_type_object = get_post_type_object( $post_type );
				if ( ! $post_type_object ) {
					return null;
				}

				$labels = array(
					$post_type_object->labels->name,
					$post_type_object->labels->singular_name,
				);
				return implode( ' ', $labels );

			case 'post_status':
				$post_status = get_post_status( $object_id );
				if ( ! $post_status ) {
					return null;
				}
				$post_status_object = get_post_status_object( $post_status );
				if ( ! $post_status_object ) {
					return null;
				}
				return $post_status_object->label;

			case 'post_author':
				$post_author = get_post_field( 'post_author', $object_id );
				if ( empty( $post_author ) ) {
					return null;
				}
				$author_display_name = get_the_author_meta( 'display_name', (int) $post_author );
				return $author_display_name !== false ? $author_display_name : null;

			default:
				return null;
		}
	}

	/**
	 * Extract taxonomy term names for search.
	 *
	 * Uses term names (not slugs) for better search relevance.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post ID.
	 * @param array $source    The data source configuration.
	 * @return string|null The content or null.
	 */
	private function extract_taxonomy( int $object_id, array $source ): ?string {
		if ( ! isset( $source['dataTaxonomy'] ) ) {
			return null;
		}

		$taxonomy = $source['dataTaxonomy'];
		$terms    = get_the_terms( $object_id, $taxonomy );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return null;
		}

		$term_names = array();

		foreach ( $terms as $term ) {
			// Use term name (not slug) for better search relevance.
			$term_names[] = $term->name;

			// Include parent term names for hierarchical taxonomies.
			$parent_id = $term->parent;
			while ( $parent_id !== 0 ) {
				$parent_term = get_term( $parent_id, $taxonomy );
				if ( is_wp_error( $parent_term ) || ! $parent_term ) {
					break;
				}
				if ( ! in_array( $parent_term->name, $term_names, true ) ) {
					$term_names[] = $parent_term->name;
				}
				$parent_id = $parent_term->parent;
			}
		}

		return implode( ' ', $term_names );
	}

	/**
	 * Extract custom field content.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post ID.
	 * @param array $source    The data source configuration.
	 * @return string|null The content or null.
	 */
	private function extract_custom_field( int $object_id, array $source ): ?string {
		if ( ! isset( $source['dataCustomField'] ) ) {
			return null;
		}

		$field_key    = $source['dataCustomField'];
		$field_values = get_post_meta( $object_id, $field_key, false );

		if ( empty( $field_values ) ) {
			return null;
		}

		// Flatten and normalize values.
		$normalized = array();
		foreach ( $field_values as $value ) {
			if ( is_scalar( $value ) && '' !== $value ) {
				$normalized[] = $value;
			} elseif ( is_array( $value ) ) {
				array_walk_recursive(
					$value,
					function ( $item ) use ( &$normalized ) {
						if ( is_scalar( $item ) && '' !== $item ) {
							$normalized[] = $item;
						}
					}
				);
			}
		}

		return ! empty( $normalized ) ? implode( ' ', $normalized ) : null;
	}

	/**
	 * Build a standardized field name from data source.
	 *
	 * @since 3.2.0
	 *
	 * @param array $source Data source configuration.
	 * @return string Sanitized field name.
	 */
	private function build_field_name( array $source ): string {
		$parts = array();

		if ( isset( $source['dataType'] ) ) {
			$parts[] = $source['dataType'];
		}

		foreach ( $source as $key => $value ) {
			if ( $key === 'uid' || $key === 'dataType' ) {
				continue;
			}
			if ( is_scalar( $value ) && '' !== $value ) {
				$parts[] = $value;
			}
		}

		$field_name = implode( '_', $parts );
		$field_name = sanitize_text_field( $field_name );

		// Enforce database column limit.
		if ( strlen( $field_name ) > 20 ) {
			$hash       = substr( md5( $field_name ), 0, 6 );
			$field_name = substr( $field_name, 0, 13 ) . '_' . $hash;
		}

		return apply_filters(
			'search-filter-pro/indexer/search_field_name',
			$field_name,
			$source
		);
	}

	/**
	 * Apply content size limits to prevent memory issues.
	 *
	 * @since 3.2.0
	 *
	 * @param array $fields_data The fields data (structured with 'content' and 'exact_match' keys).
	 * @param int   $object_id   The object ID.
	 * @return array The limited fields data.
	 */
	private function apply_content_limits( array $fields_data, int $object_id ): array {
		$max_content_length = apply_filters(
			'search-filter-pro/indexer/search/max_content_length',
			5000000 // 5MB default.
		);

		foreach ( $fields_data as $field_name => $field_data ) {
			$content = $field_data['content'] ?? '';
			if ( strlen( $content ) > $max_content_length ) {
				$fields_data[ $field_name ]['content'] = substr( $content, 0, $max_content_length );
				Util::error_log(
					sprintf(
						'Search Filter: Content truncated for post %d field %s (exceeded %d bytes)',
						$object_id,
						$field_name,
						$max_content_length
					),
					'warning'
				);
			}
		}

		return $fields_data;
	}

	/**
	 * Get search indexer instance (singleton).
	 *
	 * @since 3.2.0
	 *
	 * @return Search_Indexer
	 */
	private function get_indexer(): Search_Indexer {
		if ( null === self::$indexer ) {
			self::$indexer = new Search_Indexer();
		}
		return self::$indexer;
	}
}
