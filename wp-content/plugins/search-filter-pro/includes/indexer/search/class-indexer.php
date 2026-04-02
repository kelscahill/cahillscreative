<?php
/**
 * Search Indexer Class.
 *
 * Builds the inverted index by tokenizing content and storing
 * term-document mappings with frequencies for BM25 scoring.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search
 */

namespace Search_Filter_Pro\Indexer\Search;

use Search_Filter\Database\Transaction;
use Search_Filter\Options;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Indexer\Search\Database\Search_Query_Direct;
use Search_Filter_Pro\Indexer\Search\Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Indexer: Core indexing and updating functionality.
 *
 * Indexes posts into the inverted index with proper term extraction,
 * frequency calculation, and statistics maintenance.
 *
 * @since 3.0.9
 */
class Indexer {

	/**
	 * Tokenizer instance.
	 *
	 * @since 3.0.9
	 * @var   Tokenizer|Stemming_Tokenizer
	 */
	private $tokenizer;

	/**
	 * Default language.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	private $default_language = 'en';

	/**
	 * Whether to use stemming.
	 *
	 * @since 3.0.9
	 * @var   bool
	 */
	private $use_stemming = true;

	/**
	 * Constructor.
	 *
	 * @since 3.0.9
	 * @param Tokenizer|null $tokenizer Optional tokenizer instance.
	 */
	public function __construct( $tokenizer = null ) {
		// Auto-detect language from WordPress locale.
		$this->default_language = Stemming_Tokenizer::get_wp_language_code();

		// Auto-enable stemming if the current WordPress language supports it.
		$default_use_stemming = Stemming_Tokenizer::wp_language_supports_stemming();

		/**
		 * Filter whether to use stemming for search indexing.
		 *
		 * By default, stemming is automatically enabled for supported languages
		 * based on the WordPress locale setting.
		 *
		 * @since 3.0.9
		 * @since 3.1.0 Auto-enabled for supported languages.
		 *
		 * @param bool $use_stemming Whether to use stemming. Default is auto-detected.
		 */
		$this->use_stemming = apply_filters( 'search-filter-pro/indexer/search/use_stemming', $default_use_stemming );

		// Set tokenizer if provided, otherwise lazy-load on first use.
		$this->tokenizer = $tokenizer;
	}

	/**
	 * Get tokenizer instance (lazy initialization).
	 *
	 * Lazy loads the tokenizer to avoid heavy library loading during object construction.
	 * This is important for performance when many Indexer instances are created.
	 *
	 * @since 3.0.9
	 * @return Tokenizer|Stemming_Tokenizer
	 */
	private function get_tokenizer() {
		// Lazy initialize tokenizer on first use.
		if ( null === $this->tokenizer ) {
			if ( $this->use_stemming ) {
				$this->tokenizer = new Stemming_Tokenizer( $this->default_language );
			} else {
				$this->tokenizer = new Tokenizer();
			}
		}

		return $this->tokenizer;
	}

	/**
	 * Index a document (post).
	 *
	 * @since 3.0.9
	 * @param int    $object_id Object ID.
	 * @param array  $fields    ['source_name' => ['content' => string, 'exact_match' => bool], ...].
	 * @param string $language  Language code (Phase 3: auto-detect).
	 * @param int    $field_id  Field ID.
	 * @return bool True on success, false on failure.
	 */
	public function index_document( $object_id, array $fields, $language = null, $field_id = 0 ) {
		$language = $language ? $language : $this->default_language;

		Transaction::start();

		try {
			// Delete existing postings for this document and field.
			Search_Query_Direct::delete_document_postings( $object_id, $field_id );

			$total_word_count = 0;

			// Index each data source (title, content, excerpt, custom fields, etc.).
			foreach ( $fields as $source_name => $field_data ) {
				$content     = $field_data['content'] ?? '';
				$exact_match = $field_data['exact_match'] ?? false;

				if ( empty( $content ) ) {
					continue;
				}

				// Get tokenizer (lazy initialization).
				$tokenizer = $this->get_tokenizer();

				// Check if we're using stemming tokenizer.
				$use_stems = ( $tokenizer instanceof Stemming_Tokenizer ) && $tokenizer->has_stemming_support();

				if ( $use_stems ) {
					/**
					 * Cast to Stemming_Tokenizer for type safety.
					 *
					 * @var Stemming_Tokenizer $tokenizer Cast to Stemming_Tokenizer for type safety.
					 */
					// Use hybrid tokenization for exact match sources (SKUs, identifiers).
					if ( $exact_match ) {
						$term_data         = $tokenizer->tokenize_hybrid_with_stems_and_positions( $content );
						$total_word_count += count( $tokenizer->tokenize_hybrid( $content ) );
					} else {
						// Standard tokenization with stems and positions.
						$term_data         = $tokenizer->tokenize_with_stems_and_positions( $content );
						$total_word_count += count( $tokenizer->tokenize( $content ) );
					}

					// Process each unique term with its stem and metaphone.
					foreach ( $term_data as $term => $data ) {
						$stem      = $data['stem'];
						$metaphone = $data['metaphone'];
						$positions = $data['positions'];

						$term_id = Search_Query_Direct::get_or_create_term( $term, $language, $stem, $metaphone );

						// Insert posting.
						Search_Query_Direct::insert_posting(
							array(
								'term_id'        => $term_id,
								'object_id'      => $object_id,
								'field_id'       => $field_id,
								'source_name'    => $source_name,
								'language'       => $language,
								'term_frequency' => count( $positions ),
								'positions'      => wp_json_encode( $positions ),
							)
						);

						// Update term statistics.
						Search_Query_Direct::update_term_stats( $term_id, 1, count( $positions ) );
					}
				} else {
					// Non-stemming tokenization.
					if ( $exact_match ) {
						// Use hybrid tokenization for exact match sources.
						$term_data         = $tokenizer->tokenize_hybrid_with_positions( $content );
						$total_word_count += count( $tokenizer->tokenize_hybrid( $content ) );
					} else {
						// Standard tokenization without stems (backward compatibility).
						$term_data         = $tokenizer->tokenize_with_positions( $content );
						$total_word_count += count( $tokenizer->tokenize( $content ) );
					}

					// Process each unique term.
					foreach ( $term_data as $term => $positions ) {
						$term_id = Search_Query_Direct::get_or_create_term( $term, $language );

						// Insert posting.
						Search_Query_Direct::insert_posting(
							array(
								'term_id'        => $term_id,
								'object_id'      => $object_id,
								'field_id'       => $field_id,
								'source_name'    => $source_name,
								'language'       => $language,
								'term_frequency' => count( $positions ),
								'positions'      => wp_json_encode( $positions ),
							)
						);

						// Update term statistics.
						Search_Query_Direct::update_term_stats( $term_id, 1, count( $positions ) );
					}
				}
			}

			// Update document statistics.
			Search_Query_Direct::update_doc_stats(
				array(
					'object_id'         => $object_id,
					'field_id'          => $field_id,
					'language'          => $language,
					'word_count'        => $total_word_count,
					'indexed_timestamp' => current_time( 'mysql' ),
				)
			);

			// Update global statistics.
			$this->update_global_stats();

			Transaction::commit();

			return true;

		} catch ( \Exception $e ) {
			Transaction::rollback();
			Util::error_log( "Search index error for object $object_id: " . $e->getMessage(), 'warning' );
			return false;
		}
	}

	/**
	 * Delete document from index.
	 *
	 * Removes document and updates term frequencies and global statistics.
	 *
	 * @since 3.0.9
	 * @param int $object_id Object ID to remove.
	 * @param int $field_id  Field ID (0 = all fields for this document).
	 * @return bool True on success.
	 */
	public function delete_document( $object_id, $field_id = 0 ) {
		$result = Search_Query_Direct::delete_document_postings( $object_id, $field_id );

		// Update global statistics after deletion.
		// This recalculates total documents, terms, and average document length.
		if ( $result ) {
			$this->update_global_stats();
		}

		return $result;
	}

	/**
	 * Update global statistics.
	 *
	 * Stores total document count, total terms, and average document length
	 * in wp_options for BM25 IDF calculation.
	 *
	 * @since 3.0.9
	 */
	private function update_global_stats() {
		// Get table without auto-creation (pass false).
		$doc_stats_table = Manager::get_table( 'doc_stats', false );

		// If table doesn't exist, set defaults and return.
		if ( ! $doc_stats_table || ! $doc_stats_table->exists() ) {
			Options::update( 'indexer-search-total-documents', 0 );
			Options::update( 'indexer-search-total-terms', 0 );
			Options::update( 'indexer-search-avg-doc-length', 0 );
			return;
		}

		$stats = Search_Query_Direct::get_statistics();

		// Calculate average document length.
		global $wpdb;
		$stats_table = $doc_stats_table->get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_length = $wpdb->get_var(
			$wpdb->prepare( 'SELECT AVG(word_count) FROM %i', $stats_table )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Store in custom options table.
		Options::update( 'indexer-search-total-documents', (int) $stats['total_documents'] );
		Options::update( 'indexer-search-total-terms', (int) $stats['total_terms'] );
		Options::update( 'indexer-search-avg-doc-length', (float) $avg_length );
	}

	/**
	 * Reset all search index data.
	 *
	 * Truncates all search tables and resets global statistics.
	 *
	 * @since 3.0.9
	 */
	public static function reset() {
		// Get tables without auto-creation (pass false).
		$terms     = Manager::get_table( 'terms', false );
		$postings  = Manager::get_table( 'postings', false );
		$doc_stats = Manager::get_table( 'doc_stats', false );

		// Only truncate if tables exist.
		if ( $terms && $terms->exists() ) {
			$terms->truncate();
		}
		if ( $postings && $postings->exists() ) {
			$postings->truncate();
		}
		if ( $doc_stats && $doc_stats->exists() ) {
			$doc_stats->truncate();
		}

		// Reset global statistics.
		Options::update( 'indexer-search-total-documents', 0 );
		Options::update( 'indexer-search-total-terms', 0 );
		Options::update( 'indexer-search-avg-doc-length', 0 );
	}
}
