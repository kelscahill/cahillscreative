<?php
/**
 * Search Batch Query - Batch database operations for search index.
 *
 * Provides multi-row INSERT/DELETE operations for efficient batch
 * processing of search index data (terms, postings, doc stats).
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Search/Database
 */

namespace Search_Filter_Pro\Indexer\Search\Database;

use Search_Filter\Options;
use Search_Filter_Pro\Indexer\Search\Tokenizer;
use Search_Filter_Pro\Indexer\Search\Stemming_Tokenizer;
use Search_Filter_Pro\Indexer\Search\Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Batch Query class.
 *
 * Provides batch operations for search index tables.
 *
 * @since 3.2.0
 */
class Batch_Query {

	/**
	 * Default language.
	 *
	 * @var string
	 */
	private static $default_language = 'en';

	/**
	 * Batch delete postings for multiple documents.
	 *
	 * Also updates term statistics for removed postings.
	 *
	 * @since 3.2.0
	 *
	 * @param array $object_ids Array of object IDs to delete postings for.
	 * @param int   $field_id   Field ID (0 = all fields).
	 * @return bool True on success.
	 */
	public static function batch_delete_postings( $object_ids, $field_id = 0 ) {
		if ( empty( $object_ids ) ) {
			return true;
		}

		global $wpdb;
		$postings_table = Manager::get_table_name( 'postings' );
		$stats_table    = Manager::get_table_name( 'doc_stats' );

		// Build object ID placeholders.
		$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );

		// Step 1: Get aggregated term stats for all documents.
		// Use FOR UPDATE to lock rows and prevent concurrent modification.
		if ( $field_id > 0 ) {
			$query_params = array_merge( array( $postings_table ), $object_ids, array( $field_id ) );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$term_stats = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT term_id,
							COUNT(DISTINCT object_id) as doc_count,
							SUM(term_frequency) as total_frequency
					 FROM %i
					 WHERE object_id IN ({$placeholders}) AND field_id = %d
					 GROUP BY term_id
					 FOR UPDATE",
					$query_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$query_params = array_merge( array( $postings_table ), $object_ids );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$term_stats = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT term_id,
							COUNT(DISTINCT object_id) as doc_count,
							SUM(term_frequency) as total_frequency
					 FROM %i
					 WHERE object_id IN ({$placeholders})
					 GROUP BY term_id
					 FOR UPDATE",
					$query_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Step 2: Batch update term statistics.
		self::batch_update_term_stats_decrements( $term_stats );

		// Step 3: Delete postings.
		if ( $field_id > 0 ) {
			$delete_params = array_merge( array( $postings_table ), $object_ids, array( $field_id ) );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE object_id IN ({$placeholders}) AND field_id = %d",
					$delete_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$delete_params = array_merge( array( $postings_table ), $object_ids );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE object_id IN ({$placeholders})",
					$delete_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Step 4: Delete doc stats.
		if ( $field_id > 0 ) {
			$stats_params = array_merge( array( $stats_table ), $object_ids, array( $field_id ) );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE object_id IN ({$placeholders}) AND field_id = %d",
					$stats_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$stats_params = array_merge( array( $stats_table ), $object_ids );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE object_id IN ({$placeholders})",
					$stats_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Step 5: Cleanup orphaned terms (terms with zero doc_frequency).
		$terms_table = Manager::get_table( 'terms', false );
		if ( $terms_table && $terms_table->exists() ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i WHERE doc_frequency = 0',
					$terms_table->get_table_name()
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return true;
	}

	/**
	 * Batch index multiple documents.
	 *
	 * Tokenizes all documents, collects terms, and batch inserts postings.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id       Field ID.
	 * @param array $documents_data Map of object_id => content array.
	 *                              Each content array is [source_name => ['content' => string, 'exact_match' => bool]].
	 * @return bool True on success.
	 */
	public static function batch_index_documents( $field_id, $documents_data ) {
		if ( empty( $documents_data ) ) {
			return true;
		}

		self::$default_language = Stemming_Tokenizer::get_wp_language_code();

		// Auto-enable stemming if the current WordPress language supports it.
		$default_use_stemming = Stemming_Tokenizer::wp_language_supports_stemming();

		$language     = self::$default_language;
		$use_stemming = apply_filters( 'search-filter-pro/indexer/search/use_stemming', $default_use_stemming );

		$tokenizer = $use_stemming
			? new Stemming_Tokenizer( $language )
			: new Tokenizer();

		// Collected data for batch operations.
		$all_terms     = array(); // term => [stem, metaphone].
		$all_postings  = array(); // [term, object_id, field_id, source_name, frequency, positions].
		$all_doc_stats = array(); // [object_id, field_id, word_count].
		$term_stats    = array(); // term => [doc_count, collection_count].

		// Step 1: Process all documents and collect data.
		foreach ( $documents_data as $object_id => $content_fields ) {
			$total_word_count = 0;
			$document_terms   = array(); // Track terms seen in this document.

			foreach ( $content_fields as $source_name => $field_data ) {
				$content     = $field_data['content'] ?? '';
				$exact_match = $field_data['exact_match'] ?? false;

				if ( empty( $content ) ) {
					continue;
				}

				// Tokenize content.
				if ( $use_stemming && $tokenizer instanceof Stemming_Tokenizer && $tokenizer->has_stemming_support() ) {
					// Use hybrid tokenization for exact match sources (SKUs, identifiers).
					if ( $exact_match ) {
						$term_data         = $tokenizer->tokenize_hybrid_with_stems_and_positions( $content );
						$total_word_count += count( $tokenizer->tokenize_hybrid( $content ) );
					} else {
						$term_data         = $tokenizer->tokenize_with_stems_and_positions( $content );
						$total_word_count += count( $tokenizer->tokenize( $content ) );
					}

					foreach ( $term_data as $term => $data ) {
						$all_terms[ $term ] = array(
							'stem'      => $data['stem'],
							'metaphone' => $data['metaphone'],
						);

						$all_postings[] = array(
							'term'           => $term,
							'object_id'      => $object_id,
							'field_id'       => $field_id,
							'source_name'    => $source_name,
							'term_frequency' => count( $data['positions'] ),
							'positions'      => $data['positions'],
						);

						// Track term stats.
						if ( ! isset( $document_terms[ $term ] ) ) {
							$document_terms[ $term ] = true;
							if ( ! isset( $term_stats[ $term ] ) ) {
								$term_stats[ $term ] = array(
									'doc_count'        => 0,
									'collection_count' => 0,
								);
							}
							++$term_stats[ $term ]['doc_count'];
						}
						$term_stats[ $term ]['collection_count'] += count( $data['positions'] );
					}
				} else {
					// Non-stemming tokenization.
					if ( $exact_match ) {
						$term_data         = $tokenizer->tokenize_hybrid_with_positions( $content );
						$total_word_count += count( $tokenizer->tokenize_hybrid( $content ) );
					} else {
						$term_data         = $tokenizer->tokenize_with_positions( $content );
						$total_word_count += count( $tokenizer->tokenize( $content ) );
					}

					foreach ( $term_data as $term => $positions ) {
						if ( ! isset( $all_terms[ $term ] ) ) {
							$all_terms[ $term ] = array(
								'stem'      => null,
								'metaphone' => null,
							);
						}

						$all_postings[] = array(
							'term'           => $term,
							'object_id'      => $object_id,
							'field_id'       => $field_id,
							'source_name'    => $source_name,
							'term_frequency' => count( $positions ),
							'positions'      => $positions,
						);

						// Track term stats.
						if ( ! isset( $document_terms[ $term ] ) ) {
							$document_terms[ $term ] = true;
							if ( ! isset( $term_stats[ $term ] ) ) {
								$term_stats[ $term ] = array(
									'doc_count'        => 0,
									'collection_count' => 0,
								);
							}
							++$term_stats[ $term ]['doc_count'];
						}
						$term_stats[ $term ]['collection_count'] += count( $positions );
					}
				}
			}

			// Collect doc stats.
			$all_doc_stats[] = array(
				'object_id'  => $object_id,
				'field_id'   => $field_id,
				'word_count' => $total_word_count,
			);
		}

		// Step 2: Batch get or create terms.
		$term_id_map = self::batch_get_or_create_terms( $all_terms, $language );

		// Step 3: Batch insert postings.
		self::batch_insert_postings( $all_postings, $term_id_map, $language );

		// Step 4: Batch update term statistics.
		self::batch_update_term_stats_increments( $term_stats, $term_id_map );

		// Step 5: Batch insert doc stats.
		self::batch_insert_doc_stats( $all_doc_stats, $language );

		// Step 6: Update global statistics.
		self::update_global_stats();

		return true;
	}

	/**
	 * Batch get or create terms.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $terms    Map of term => [stem, metaphone].
	 * @param string $language Language code.
	 * @return array Map of term => term_id.
	 */
	private static function batch_get_or_create_terms( $terms, $language ) {
		if ( empty( $terms ) ) {
			return array();
		}

		global $wpdb;
		$table       = Manager::get_table_name( 'terms' );
		$term_keys   = array_keys( $terms );
		$term_id_map = array();

		// Step 1: Get existing terms.
		$placeholders = implode( ',', array_fill( 0, count( $term_keys ), '%s' ) );
		$query_params = array_merge( array( $table ), $term_keys, array( $language ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$existing = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, term FROM %i WHERE term IN ({$placeholders}) AND language = %s",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $existing as $row ) {
			$term_id_map[ $row->term ] = (int) $row->term_id;
		}

		// Step 2: Insert new terms.
		$new_terms = array_diff( $term_keys, array_keys( $term_id_map ) );

		if ( ! empty( $new_terms ) ) {
			$values_sql = array();
			$values     = array();

			foreach ( $new_terms as $term ) {
				$term_data    = $terms[ $term ];
				$values_sql[] = '(%s, %s, %d, %d, %s, %s)';
				$values[]     = $term;
				$values[]     = $language;
				$values[]     = 0; // doc_frequency.
				$values[]     = 0; // collection_frequency.
				$values[]     = $term_data['stem'] ?? '';
				$values[]     = $term_data['metaphone'] ?? '';
			}

			$values_clause = implode( ', ', $values_sql );

			// Use INSERT IGNORE to handle potential race conditions.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$table}
					(term, language, doc_frequency, collection_frequency, term_stem, term_metaphone)
					VALUES {$values_clause}",
					$values
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Re-fetch to get IDs for newly inserted terms.
			$new_placeholders = implode( ',', array_fill( 0, count( $new_terms ), '%s' ) );
			$new_query_params = array_merge( array( $table ), $new_terms, array( $language ) );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$inserted = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT term_id, term FROM %i WHERE term IN ({$new_placeholders}) AND language = %s",
					$new_query_params
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			foreach ( $inserted as $row ) {
				$term_id_map[ $row->term ] = (int) $row->term_id;
			}
		}

		return $term_id_map;
	}

	/**
	 * Batch insert postings.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $postings    Array of posting data.
	 * @param array  $term_id_map Map of term => term_id.
	 * @param string $language    Language code.
	 * @return bool True on success.
	 */
	private static function batch_insert_postings( $postings, $term_id_map, $language ) {
		if ( empty( $postings ) ) {
			return true;
		}

		global $wpdb;
		$table = Manager::get_table_name( 'postings' );

		// Build multi-row INSERT.
		$values_sql = array();
		$values     = array();

		foreach ( $postings as $posting ) {
			$term    = $posting['term'];
			$term_id = $term_id_map[ $term ] ?? null;

			if ( ! $term_id ) {
				continue; // Skip if term wasn't created.
			}

			$values_sql[] = '(%d, %d, %d, %s, %s, %d, %s)';
			$values[]     = $term_id;
			$values[]     = $posting['object_id'];
			$values[]     = $posting['field_id'];
			$values[]     = $posting['source_name'];
			$values[]     = $language;
			$values[]     = $posting['term_frequency'];
			$values[]     = wp_json_encode( $posting['positions'] );
		}

		if ( empty( $values_sql ) ) {
			return true;
		}

		$values_clause = implode( ', ', $values_sql );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table}
				(term_id, object_id, field_id, source_name, language, term_frequency, positions)
				VALUES {$values_clause}",
				$values
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	/**
	 * Batch update term statistics (increments).
	 *
	 * @since 3.2.0
	 *
	 * @param array $term_stats  Map of term => [doc_count, collection_count].
	 * @param array $term_id_map Map of term => term_id.
	 * @return bool True on success.
	 */
	private static function batch_update_term_stats_increments( $term_stats, $term_id_map ) {
		if ( empty( $term_stats ) ) {
			return true;
		}

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		// Build CASE statements for batch update.
		$doc_cases        = array();
		$collection_cases = array();
		$term_ids         = array();

		foreach ( $term_stats as $term => $stats ) {
			$term_id = $term_id_map[ $term ] ?? null;
			if ( ! $term_id ) {
				continue;
			}

			$term_ids[]         = $term_id;
			$doc_cases[]        = $wpdb->prepare( 'WHEN %d THEN doc_frequency + %d', $term_id, $stats['doc_count'] );
			$collection_cases[] = $wpdb->prepare( 'WHEN %d THEN collection_frequency + %d', $term_id, $stats['collection_count'] );
		}

		if ( empty( $term_ids ) ) {
			return true;
		}

		$doc_case_sql        = implode( ' ', $doc_cases );
		$collection_case_sql = implode( ' ', $collection_cases );
		$term_id_list        = implode( ',', array_map( 'intval', $term_ids ) );

		// Retry logic for concurrent update conflicts.
		$max_retries = 3;
		$retry_delay = 50; // Start with 50ms.

		for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$result = $wpdb->query(
				"UPDATE {$table}
				SET doc_frequency = CASE term_id {$doc_case_sql} ELSE doc_frequency END,
				    collection_frequency = CASE term_id {$collection_case_sql} ELSE collection_frequency END
				WHERE term_id IN ({$term_id_list})"
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( false !== $result ) {
				return true;
			}

			if ( $attempt === $max_retries ) {
				return false;
			}

			usleep( $retry_delay * 1000 );
			$retry_delay *= 2;
		}

		return false;
	}

	/**
	 * Batch update term statistics (decrements).
	 *
	 * @since 3.2.0
	 *
	 * @param array $term_stats Array of objects with term_id, doc_count, total_frequency.
	 * @return bool True on success.
	 */
	private static function batch_update_term_stats_decrements( $term_stats ) {
		if ( empty( $term_stats ) ) {
			return true;
		}

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		// Build CASE statements for batch update.
		$doc_cases        = array();
		$collection_cases = array();
		$term_ids         = array();

		foreach ( $term_stats as $stat ) {
			$term_ids[]         = $stat->term_id;
			$doc_cases[]        = $wpdb->prepare(
				'WHEN %d THEN GREATEST(0, doc_frequency - %d)',
				$stat->term_id,
				$stat->doc_count
			);
			$collection_cases[] = $wpdb->prepare(
				'WHEN %d THEN GREATEST(0, collection_frequency - %d)',
				$stat->term_id,
				$stat->total_frequency
			);
		}

		$doc_case_sql        = implode( ' ', $doc_cases );
		$collection_case_sql = implode( ' ', $collection_cases );
		$term_id_list        = implode( ',', array_map( 'intval', $term_ids ) );

		// Retry logic for concurrent update conflicts.
		$max_retries = 3;
		$retry_delay = 50; // Start with 50ms.

		for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$result = $wpdb->query(
				"UPDATE {$table}
				SET doc_frequency = CASE term_id {$doc_case_sql} ELSE doc_frequency END,
				    collection_frequency = CASE term_id {$collection_case_sql} ELSE collection_frequency END
				WHERE term_id IN ({$term_id_list})"
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( false !== $result ) {
				return true;
			}

			if ( $attempt === $max_retries ) {
				return false;
			}

			usleep( $retry_delay * 1000 );
			$retry_delay *= 2;
		}

		return false;
	}

	/**
	 * Batch insert doc stats.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $doc_stats Array of doc stats data.
	 * @param string $language  Language code.
	 * @return bool True on success.
	 */
	private static function batch_insert_doc_stats( $doc_stats, $language ) {
		if ( empty( $doc_stats ) ) {
			return true;
		}

		global $wpdb;
		$table        = Manager::get_table_name( 'doc_stats' );
		$current_time = current_time( 'mysql' );

		// Build multi-row INSERT with ON DUPLICATE KEY UPDATE.
		$values_sql = array();
		$values     = array();

		foreach ( $doc_stats as $stat ) {
			$values_sql[] = '(%d, %d, %s, %d, %s)';
			$values[]     = $stat['object_id'];
			$values[]     = $stat['field_id'];
			$values[]     = $language;
			$values[]     = $stat['word_count'];
			$values[]     = $current_time;
		}

		$values_clause = implode( ', ', $values_sql );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table}
				(object_id, field_id, language, word_count, indexed_timestamp)
				VALUES {$values_clause}
				ON DUPLICATE KEY UPDATE
					language = VALUES(language),
					word_count = VALUES(word_count),
					indexed_timestamp = VALUES(indexed_timestamp)",
				$values
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	/**
	 * Update global statistics.
	 *
	 * @since 3.2.0
	 */
	private static function update_global_stats() {
		global $wpdb;

		$stats       = Search_Query_Direct::get_statistics();
		$stats_table = Manager::get_table_name( 'doc_stats' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_length = $wpdb->get_var(
			$wpdb->prepare( 'SELECT AVG(word_count) FROM %i', $stats_table )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		Options::update( 'indexer-search-total-documents', (int) $stats['total_documents'] );
		Options::update( 'indexer-search-total-terms', (int) $stats['total_terms'] );
		Options::update( 'indexer-search-avg-doc-length', (float) $avg_length );
	}
}
