<?php
/**
 * Search Query Direct - High-performance direct database queries.
 *
 * Provides optimized direct SQL queries for the search index tables,
 * bypassing ORM for maximum performance.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search/Database
 */

namespace Search_Filter_Pro\Indexer\Search\Database;

use Search_Filter\Database\Transaction;
use Search_Filter_Pro\Indexer\Search\Manager;
use Search_Filter_Pro\Util;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Query Direct Class.
 *
 * @since 3.0.9
 */
class Search_Query_Direct {

	/**
	 * Get or create term ID.
	 *
	 * @since 3.0.9
	 * @param string      $term      Term text.
	 * @param string      $language  Language code.
	 * @param string|null $stem      Optional stemmed version of term.
	 * @param string|null $metaphone Optional metaphone code of term.
	 * @return int Term ID.
	 */
	public static function get_or_create_term( $term, $language, $stem = null, $metaphone = null ) {

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		// Try to find existing.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$term_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT term_id FROM %i WHERE term = %s AND language = %s',
				$table,
				$term,
				$language
			)
		);

		if ( $term_id ) {
			// Term exists - update stem/metaphone if provided and not already set.
			$updates = array();
			$where   = array( 'term_id' => $term_id );

			if ( null !== $stem ) {
				$where['term_stem']   = '';
				$updates['term_stem'] = $stem;
			}

			if ( null !== $metaphone ) {
				$where['term_metaphone']   = '';
				$updates['term_metaphone'] = $metaphone;
			}

			if ( ! empty( $updates ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					$updates,
					$where,
					array_fill( 0, count( $updates ), '%s' ),
					array_merge( array( '%d' ), array_fill( 0, count( $updates ), '%s' ) )
				);
			}

			return (int) $term_id;
		}

		// Create new term with stem and metaphone.
		// Always include stem/metaphone with empty string default for consistency with batch indexing.
		$data   = array(
			'term'                 => $term,
			'language'             => $language,
			'doc_frequency'        => 0,
			'collection_frequency' => 0,
			'term_stem'            => $stem ?? '',
			'term_metaphone'       => $metaphone ?? '',
		);
		$format = array( '%s', '%s', '%d', '%d', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $table, $data, $format );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Insert posting.
	 *
	 * @since 3.0.9
	 * @param array $data Posting data.
	 * @return bool True on success.
	 */
	public static function insert_posting( $data ) {

		global $wpdb;
		$table = Manager::get_table_name( 'postings' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table,
			$data,
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Update term statistics.
	 *
	 * Uses GREATEST(0, ...) to ensure frequencies never go negative.
	 * Safe for concurrent updates due to atomic SQL operation.
	 *
	 * @since 3.0.9
	 * @param int $term_id                 Term ID.
	 * @param int $doc_count_delta         Change in document count.
	 * @param int $collection_count_delta  Change in collection count.
	 * @return bool True on success.
	 */
	public static function update_term_stats( $term_id, $doc_count_delta, $collection_count_delta ) {

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		// Retry logic for concurrent update conflicts.
		$max_retries = 3;
		$retry_delay = 50; // Start with 50ms.

		for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
			// Use GREATEST(0, ...) to ensure frequencies never go below 0.
			// This protects against race conditions and out-of-order operations.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare(
					'UPDATE %i
					 SET doc_frequency = GREATEST(0, doc_frequency + %d),
					     collection_frequency = GREATEST(0, collection_frequency + %d)
					 WHERE term_id = %d',
					$table,
					$doc_count_delta,
					$collection_count_delta,
					$term_id
				)
			);

			// If successful, return immediately.
			if ( false !== $result ) {
				if ( $attempt > 1 ) {
					Util::error_log( "update_term_stats succeeded on retry attempt {$attempt} for term_id {$term_id}", 'notice' );
				}
				return true;
			}

			// If this was the last attempt, give up.
			if ( $attempt === $max_retries ) {
				Util::error_log( "update_term_stats failed after {$max_retries} attempts for term_id {$term_id}: " . $wpdb->last_error, 'error' );
				return false;
			}

			// Wait before retrying (exponential backoff).
			Util::error_log( "update_term_stats attempt {$attempt} failed for term_id {$term_id}, retrying in {$retry_delay}ms", 'notice' );
			usleep( $retry_delay * 1000 ); // Convert ms to microseconds.
			$retry_delay *= 2; // Double delay for next retry.
		}

		return false;
	}

	/**
	 * Update document statistics.
	 *
	 * @since 3.0.9
	 * @param array $data Document stats data.
	 * @return bool True on success.
	 */
	public static function update_doc_stats( $data ) {

		global $wpdb;
		$table = Manager::get_table_name( 'doc_stats' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (object_id, field_id, language, word_count, indexed_timestamp)
				 VALUES (%d, %d, %s, %d, %s)
				 ON DUPLICATE KEY UPDATE
					language = VALUES(language),
					word_count = VALUES(word_count),
					indexed_timestamp = VALUES(indexed_timestamp)',
				$table,
				$data['object_id'],
				$data['field_id'],
				$data['language'],
				$data['word_count'],
				$data['indexed_timestamp']
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Delete all postings for a document.
	 *
	 * Also decrements term frequencies to keep statistics accurate.
	 * Uses transaction to prevent race conditions.
	 *
	 * @since 3.0.9
	 * @param int $object_id Object ID.
	 * @param int $field_id  Field ID (0 = all fields for this document).
	 * @return bool True on success.
	 */
	public static function delete_document_postings( $object_id, $field_id = 0 ) {
		// Get tables without auto-creation (pass false).
		$postings  = Manager::get_table( 'postings', false );
		$doc_stats = Manager::get_table( 'doc_stats', false );

		// Return early if tables don't exist - nothing to delete.
		if ( ! $postings || ! $postings->exists() || ! $doc_stats || ! $doc_stats->exists() ) {
			return true;
		}

		global $wpdb;
		$postings_table = $postings->get_table_name();
		$stats_table    = $doc_stats->get_table_name();

		// Start transaction to ensure atomicity and prevent race conditions.
		Transaction::start();

		try {
			// STEP 1: Get all postings for this document BEFORE deleting.
			// We need this data to decrement term frequencies.
			if ( $field_id > 0 ) {
				// Delete specific field only - lock rows to prevent concurrent modification.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$postings = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT term_id, term_frequency FROM %i WHERE object_id = %d AND field_id = %d FOR UPDATE',
						$postings_table,
						$object_id,
						$field_id
					)
				);
			} else {
				// Delete all fields for this document - lock rows to prevent concurrent modification.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$postings = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT term_id, term_frequency FROM %i WHERE object_id = %d FOR UPDATE',
						$postings_table,
						$object_id
					)
				);
			}

			// STEP 2: Update term statistics (decrement frequencies).
			// This must happen BEFORE deletion in same transaction.
			foreach ( $postings as $posting ) {
				self::update_term_stats(
					$posting->term_id,
					-1,                          // Decrement doc_frequency by 1.
					-$posting->term_frequency    // Decrement collection_frequency.
				);
			}

			// STEP 3: Delete postings.
			if ( $field_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete(
					$postings_table,
					array(
						'object_id' => $object_id,
						'field_id'  => $field_id,
					),
					array( '%d', '%d' )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete(
					$postings_table,
					array( 'object_id' => $object_id ),
					array( '%d' )
				);
			}

			// STEP 4: Delete doc stats.
			if ( $field_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete(
					$stats_table,
					array(
						'object_id' => $object_id,
						'field_id'  => $field_id,
					),
					array( '%d', '%d' )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete(
					$stats_table,
					array( 'object_id' => $object_id ),
					array( '%d' )
				);
			}

			// STEP 5: Cleanup orphaned terms (terms with zero doc_frequency).
			// These terms are no longer referenced by any postings and should be removed.
			$terms = Manager::get_table( 'terms', false );
			if ( $terms && $terms->exists() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM %i WHERE doc_frequency = 0',
						$terms->get_table_name()
					)
				);
			}

			// Commit transaction.
			Transaction::commit();

			return true;

		} catch ( \Exception $e ) {
			// Rollback on error to maintain consistency.
			Transaction::rollback();
			Util::error_log( "Search index delete error for object {$object_id}: " . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Delete all postings for a specific field (across all documents).
	 *
	 * Used when re-indexing a field or when field is deleted.
	 * Updates term statistics to maintain index consistency.
	 *
	 * @since 3.2.0
	 * @param int $field_id Field ID to clear.
	 * @return bool True on success.
	 */
	public static function clear_field_postings( $field_id ) {
		global $wpdb;

		// Get tables without installing - if they don't exist, nothing to delete.
		$postings = Manager::get_table( 'postings', false );
		$stats    = Manager::get_table( 'doc_stats', false );

		// If tables don't exist, nothing to clear.
		if ( ! $postings || ! $postings->exists() || ! $stats || ! $stats->exists() ) {
			return true;
		}

		$postings_table = $postings->get_table_name();
		$stats_table    = $stats->get_table_name();

		Transaction::start();

		try {
			// STEP 1: Get posting stats grouped by term BEFORE deletion.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$posting_stats = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT term_id,
							COUNT(DISTINCT object_id) as doc_count,
							SUM(term_frequency) as total_frequency
					 FROM %i
					 WHERE field_id = %d
					 GROUP BY term_id',
					$postings_table,
					$field_id
				)
			);

			// STEP 2: Update term statistics (decrement).
			foreach ( $posting_stats as $stat ) {
				self::update_term_stats(
					$stat->term_id,
					-$stat->doc_count,
					-$stat->total_frequency
				);
			}

			// STEP 3: Delete postings for this field.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$postings_table,
				array( 'field_id' => $field_id ),
				array( '%d' )
			);

			// STEP 4: Delete doc stats for this field.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$stats_table,
				array( 'field_id' => $field_id ),
				array( '%d' )
			);

			// STEP 5: Cleanup orphaned terms (terms with zero doc_frequency).
			// These terms are no longer referenced by any postings and should be removed.
			$terms_table = Manager::get_table( 'terms', false );
			if ( $terms_table && $terms_table->exists() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM %i WHERE doc_frequency = 0',
						$terms_table->get_table_name()
					)
				);
			}

			Transaction::commit();
			return true;

		} catch ( \Exception $e ) {
			Transaction::rollback();
			Util::error_log( "Clear field postings error for field {$field_id}: " . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Get terms data for query terms.
	 *
	 * @since 3.0.9
	 * @param array  $terms    Array of term strings.
	 * @param string $language Language code.
	 * @return array Term data indexed by term_id.
	 */
	public static function get_terms_data( $terms, $language = null ) {

		if ( empty( $terms ) ) {
			return array();
		}

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		$placeholders = implode( ',', array_fill( 0, count( $terms ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders for IN clause.
		$sql    = "SELECT term_id, term, doc_frequency FROM %i WHERE term IN ({$placeholders})";
		$params = array_merge( array( $table ), $terms );

		if ( $language ) {
			$sql     .= ' AND language = %s';
			$params[] = $language;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $params )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Index by term_id.
		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->term_id ] = $row;
		}

		return $indexed;
	}

	/**
	 * Get terms data by stems (for fuzzy matching).
	 *
	 * @since 3.0.9
	 * @param array  $stems    Array of stem strings.
	 * @param string $language Language code.
	 * @return array Term data indexed by term_id.
	 */
	public static function get_terms_by_stems( $stems, $language = null ) {

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		if ( empty( $stems ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $stems ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders for IN clause.
		$sql    = "SELECT term_id, term, term_stem, doc_frequency FROM %i WHERE term_stem IN ({$placeholders})";
		$params = array_merge( array( $table ), $stems );

		if ( $language ) {
			$sql     .= ' AND language = %s';
			$params[] = $language;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $params )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Index by term_id.
		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->term_id ] = $row;
		}

		return $indexed;
	}

	/**
	 * Get terms data by metaphone codes (for phonetic fuzzy matching).
	 *
	 * @since 3.0.9
	 * @param array  $metaphones Array of metaphone codes.
	 * @param string $language   Language code.
	 * @return array Term data indexed by term_id.
	 */
	public static function get_terms_by_metaphone( $metaphones, $language = null ) {

		global $wpdb;
		$table = Manager::get_table_name( 'terms' );

		if ( empty( $metaphones ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $metaphones ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders for IN clause.
		$sql    = "SELECT term_id, term, term_metaphone, doc_frequency FROM %i WHERE term_metaphone IN ({$placeholders})";
		$params = array_merge( array( $table ), $metaphones );

		if ( $language ) {
			$sql     .= ' AND language = %s';
			$params[] = $language;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $params )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Index by term_id.
		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->term_id ] = $row;
		}

		return $indexed;
	}

	/**
	 * Get postings for terms with optional object ID filtering.
	 *
	 * @since 3.0.9
	 * @param array      $term_ids            Array of term IDs.
	 * @param array|null $allowed_object_ids  Optional object ID filter (from bitmap).
	 * @param int|null   $field_id            Optional S&F field ID to constrain search.
	 * @return array Postings with document stats joined.
	 */
	public static function get_postings_for_terms( $term_ids, $allowed_object_ids = null, $field_id = null ) {

		global $wpdb;
		$postings_table = Manager::get_table_name( 'postings' );
		$stats_table    = Manager::get_table_name( 'doc_stats' );

		if ( empty( $term_ids ) ) {
			return array();
		}

		$term_placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );

		// Build params array: table names first, then term IDs.
		$params = array( $postings_table, $stats_table );
		$params = array_merge( $params, $term_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders for IN clause.
		$sql = "
			SELECT
				p.term_id,
				p.object_id,
				p.field_id,
				p.source_name,
				p.term_frequency,
				s.word_count
			FROM %i p
			INNER JOIN %i s ON p.object_id = s.object_id AND p.field_id = s.field_id
			WHERE p.term_id IN ({$term_placeholders})
		";

		// Apply field ID filtering if provided (constrains to specific search field).
		if ( $field_id !== null && $field_id > 0 ) {
			$sql     .= ' AND p.field_id = %d';
			$params[] = $field_id;
		}

		// Apply object ID filtering if bitmap pre-filter provided.
		if ( $allowed_object_ids !== null ) {
			// Empty array means no posts allowed - return empty immediately.
			if ( empty( $allowed_object_ids ) ) {
				return array();
			}

			// Add constraint for non-empty array.
			$object_placeholders = implode( ',', array_fill( 0, count( $allowed_object_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders for IN clause.
			$sql   .= " AND p.object_id IN ({$object_placeholders})";
			$params = array_merge( $params, $allowed_object_ids );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare( $sql, $params )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get index statistics.
	 *
	 * Returns counts for debugging and admin display.
	 *
	 * @since 3.0.9
	 * @return array Statistics array.
	 */
	public static function get_statistics() {
		// Get tables without auto-creation (pass false).
		$terms     = Manager::get_table( 'terms', false );
		$postings  = Manager::get_table( 'postings', false );
		$doc_stats = Manager::get_table( 'doc_stats', false );

		// Return empty stats if tables don't exist.
		if ( ! $terms || ! $terms->exists() || ! $postings || ! $postings->exists() || ! $doc_stats || ! $doc_stats->exists() ) {
			return array(
				'total_terms'     => 0,
				'total_postings'  => 0,
				'total_documents' => 0,
			);
		}

		global $wpdb;
		$terms_table    = $terms->get_table_name();
		$postings_table = $postings->get_table_name();
		$stats_table    = $doc_stats->get_table_name();

		$stats = array();

		// Total terms.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['total_terms'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $terms_table )
		);

		// Total postings.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['total_postings'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $postings_table )
		);

		// Total documents.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['total_documents'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $stats_table )
		);

		return $stats;
	}
}
