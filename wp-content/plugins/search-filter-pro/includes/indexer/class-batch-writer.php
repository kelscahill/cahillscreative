<?php
/**
 * Batch Writer - Orchestrates batched writes to all index types.
 *
 * Collects index data from multiple posts and flushes to appropriate index
 * tables in batched operations for optimal performance.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Database\Transaction;
use Search_Filter\Fields\Field;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bitmap\Database\Batch_Query as Bitmap_Batch_Query;
use Search_Filter_Pro\Indexer\Bucket\Database\Batch_Query as Bucket_Batch_Query;
use Search_Filter_Pro\Indexer\Legacy\Database\Batch_Query as Legacy_Batch_Query;
use Search_Filter_Pro\Indexer\Legacy\Manager as Legacy_Manager;
use Search_Filter_Pro\Indexer\Search\Database\Batch_Query as Search_Batch_Query;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Batch Writer class.
 *
 * Collects index data from multiple posts and efficiently writes to index
 * tables using batch operations.
 *
 * @since 3.2.0
 */
class Batch_Writer {

	/**
	 * Collected bitmap field data.
	 *
	 * Structure: [ field_id => [ post_id => [ 'values' => [], 'parent_id' => int ] ] ]
	 *
	 * @var array
	 */
	private $bitmap_data = array();

	/**
	 * Collected bucket field data.
	 *
	 * Structure: [ field_id => [ post_id => [ 'values' => [], 'parent_id' => int ] ] ]
	 *
	 * @var array
	 */
	private $bucket_data = array();

	/**
	 * Collected search field data.
	 *
	 * Structure: [ field_id => [ post_id => [ 'content' => [], 'parent_id' => int ] ] ]
	 *
	 * @var array
	 */
	private $search_data = array();

	/**
	 * Field IDs that have been successfully processed.
	 *
	 * @var array
	 */
	private $processed_fields = array();

	/**
	 * Batch result tracker.
	 *
	 * @var Batch_Result
	 */
	private $result;

	/**
	 * Cache of field index types.
	 *
	 * @var array
	 */
	private static $field_type_cache = array();

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 */
	public function __construct() {
		$this->result = new Batch_Result();
	}

	/**
	 * Add post values for a field to the batch.
	 *
	 * Routes data to appropriate index type based on field configuration.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param int   $post_id   Post ID.
	 * @param int   $parent_id Parent post ID.
	 * @param array $values    Array of values to index.
	 */
	public function add_post_values( $field_id, $post_id, $parent_id, $values ) {
		$index_type = $this->get_field_index_type( $field_id );

		if ( ! $index_type ) {
			return;
		}

		switch ( $index_type ) {
			case 'bitmap':
				$this->add_bitmap_data( $field_id, $post_id, $parent_id, $values );
				break;

			case 'bucket':
				$this->add_bucket_data( $field_id, $post_id, $parent_id, $values );
				break;

			case 'search':
				// For search, values is actually content array.
				$this->add_search_data( $field_id, $post_id, $parent_id, $values );
				break;
		}
	}

	/**
	 * Add bitmap field data.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param int   $post_id   Post ID.
	 * @param int   $parent_id Parent post ID.
	 * @param array $values    Array of values.
	 */
	private function add_bitmap_data( $field_id, $post_id, $parent_id, $values ) {
		if ( ! isset( $this->bitmap_data[ $field_id ] ) ) {
			$this->bitmap_data[ $field_id ] = array();
		}

		$this->bitmap_data[ $field_id ][ $post_id ] = array(
			'values'    => $values,
			'parent_id' => $parent_id,
		);
	}

	/**
	 * Add bucket field data.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param int   $post_id   Post ID.
	 * @param int   $parent_id Parent post ID.
	 * @param array $values    Array of values (typically single numeric value).
	 */
	private function add_bucket_data( $field_id, $post_id, $parent_id, $values ) {
		if ( ! isset( $this->bucket_data[ $field_id ] ) ) {
			$this->bucket_data[ $field_id ] = array();
		}

		$this->bucket_data[ $field_id ][ $post_id ] = array(
			'values'    => $values,
			'parent_id' => $parent_id,
		);
	}

	/**
	 * Add search field data.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param int   $post_id   Post ID.
	 * @param int   $parent_id Parent post ID.
	 * @param array $content   Array of content fields to index.
	 */
	private function add_search_data( $field_id, $post_id, $parent_id, $content ) {
		if ( ! isset( $this->search_data[ $field_id ] ) ) {
			$this->search_data[ $field_id ] = array();
		}

		$this->search_data[ $field_id ][ $post_id ] = array(
			'content'   => $content,
			'parent_id' => $parent_id,
		);
	}

	/**
	 * Flush all collected data to index tables.
	 *
	 * Processes each index type in sequence with per-field transactions.
	 *
	 * @since 3.2.0
	 *
	 * @return Batch_Result Result object with success/failure tracking.
	 */
	public function flush() {
		// Reset result for this flush.
		$this->result = new Batch_Result();

		// Process each index type (order: bitmap, bucket, search).
		$this->flush_bitmap_fields();
		$this->flush_bucket_fields();
		$this->flush_search_fields();

		// Handle legacy dual-write during migration.
		$this->flush_legacy_fields();

		// Clear all collected data.
		$this->clear();

		return $this->result;
	}

	/**
	 * Flush bitmap fields in batch.
	 *
	 * For each field:
	 * 1. Batch clear posts from existing bitmaps
	 * 2. Collect all unique values
	 * 3. Batch load affected bitmaps
	 * 4. Modify bitmaps in memory
	 * 5. Batch store updated bitmaps
	 *
	 * @since 3.2.0
	 */
	private function flush_bitmap_fields() {
		foreach ( $this->bitmap_data as $field_id => $post_data ) {
			Transaction::start();

			try {
				$post_ids = array_keys( $post_data );

				// Step 1: Remove posts from existing bitmaps for this field.
				Bitmap_Batch_Query::batch_remove_posts( $field_id, $post_ids );

				// Step 2: Collect all unique values across posts.
				$all_values = array();
				foreach ( $post_data as $data ) {
					if ( ! empty( $data['values'] ) ) {
						$all_values = array_merge( $all_values, $data['values'] );
					}
				}
				$unique_values = array_unique( $all_values );

				if ( empty( $unique_values ) ) {
					// No values to index - commit and continue.
					Transaction::commit();
					$this->processed_fields[] = $field_id;
					$this->result->record_success( count( $post_ids ) );
					continue;
				}

				// Step 3: Batch load existing bitmaps for these values.
				$bitmaps = Bitmap_Batch_Query::batch_load_bitmaps( $field_id, $unique_values );

				// Step 4: Update bitmaps in memory.
				foreach ( $post_data as $post_id => $data ) {
					foreach ( $data['values'] as $value ) {
						if ( ! isset( $bitmaps[ $value ] ) ) {
							$bitmaps[ $value ] = new Bitmap();
						}
						$bitmaps[ $value ]->set_bit( $post_id );
					}
				}

				// Step 5: Batch store all modified bitmaps.
				Bitmap_Batch_Query::batch_store_bitmaps( $field_id, $bitmaps );

				Transaction::commit();
				$this->processed_fields[] = $field_id;
				$this->result->record_success( count( $post_ids ) );

			} catch ( \Exception $e ) {
				Transaction::rollback();
				// Safe to call - automatically deferred until after rollback completes.
				Util::error_log(
					sprintf(
						'Bitmap batch indexing failed for field %d: %s',
						$field_id,
						$e->getMessage()
					),
					'error'
				);
				$this->result->record_field_failure(
					$field_id,
					array_keys( $post_data ),
					$e->getMessage(),
					'bitmap'
				);
			}
		}
	}

	/**
	 * Flush bucket fields in batch.
	 *
	 * For each field:
	 * 1. Batch delete overflow entries for posts
	 * 2. Batch insert new overflow entries
	 *
	 * @since 3.2.0
	 */
	private function flush_bucket_fields() {
		foreach ( $this->bucket_data as $field_id => $post_data ) {
			Transaction::start();

			try {
				$post_ids = array_keys( $post_data );

				// Step 1: Batch delete existing overflow entries.
				Bucket_Batch_Query::batch_delete_overflow( $field_id, $post_ids );

				// Step 2: Prepare batch insert data.
				$overflow_entries = array();
				foreach ( $post_data as $post_id => $data ) {
					foreach ( $data['values'] as $value ) {
						$overflow_entries[] = array(
							'object_id'        => $post_id,
							'object_parent_id' => $data['parent_id'],
							'value'            => (float) $value,
						);
					}
				}

				// Step 3: Batch insert overflow entries.
				if ( ! empty( $overflow_entries ) ) {
					Bucket_Batch_Query::batch_insert_overflow( $field_id, $overflow_entries );
				}

				Transaction::commit();
				$this->processed_fields[] = $field_id;
				$this->result->record_success( count( $post_ids ) );

			} catch ( \Exception $e ) {
				Transaction::rollback();
				// Safe to call - automatically deferred until after rollback completes.
				Util::error_log(
					sprintf(
						'Bucket batch indexing failed for field %d: %s',
						$field_id,
						$e->getMessage()
					),
					'error'
				);
				$this->result->record_field_failure(
					$field_id,
					array_keys( $post_data ),
					$e->getMessage(),
					'bucket'
				);
			}
		}
	}

	/**
	 * Flush search fields in batch.
	 *
	 * For each field:
	 * 1. Batch delete existing postings
	 * 2. Tokenize all content
	 * 3. Batch get or create terms
	 * 4. Batch insert postings
	 *
	 * @since 3.2.0
	 */
	private function flush_search_fields() {
		foreach ( $this->search_data as $field_id => $post_data ) {
			Transaction::start();

			try {
				$object_ids = array_keys( $post_data );

				// Step 1: Batch delete existing postings.
				Search_Batch_Query::batch_delete_postings( $object_ids, $field_id );

				// Step 2: Process documents and collect terms/postings.
				$documents_data = array();
				foreach ( $post_data as $object_id => $data ) {
					$documents_data[ $object_id ] = $data['content'];
				}

				// Step 3: Batch index documents.
				if ( ! empty( $documents_data ) ) {
					Search_Batch_Query::batch_index_documents( $field_id, $documents_data );
				}

				Transaction::commit();
				$this->processed_fields[] = $field_id;
				$this->result->record_success( count( $object_ids ) );

			} catch ( \Exception $e ) {
				Transaction::rollback();
				// Safe to call - automatically deferred until after rollback completes.
				Util::error_log(
					sprintf(
						'Search batch indexing failed for field %d: %s',
						$field_id,
						$e->getMessage()
					),
					'error'
				);
				$this->result->record_field_failure(
					$field_id,
					array_keys( $post_data ),
					$e->getMessage(),
					'search'
				);
			}
		}
	}

	/**
	 * Flush legacy index fields for dual-write during migration.
	 *
	 * When migration is incomplete, writes to legacy index in addition
	 * to new indexes (bitmap/bucket). Search fields are excluded as
	 * they don't use the legacy index.
	 *
	 * Uses batch operations for efficiency - single DELETE and INSERT
	 * per field instead of per-post operations.
	 *
	 * @since 3.2.0
	 */
	private function flush_legacy_fields() {
		// Skip if migration completed (no dual-write needed).
		if ( Indexer::migration_completed() ) {
			return;
		}

		Legacy_Manager::ensure_tables();

		// Process bitmap fields (choice fields).
		foreach ( $this->bitmap_data as $field_id => $post_data ) {
			$this->flush_legacy_field_data( $field_id, $post_data );
		}

		// Process bucket fields (range fields).
		foreach ( $this->bucket_data as $field_id => $post_data ) {
			$this->flush_legacy_field_data( $field_id, $post_data );
		}
		// Note: search_data is NOT processed - search fields don't use legacy index.
	}

	/**
	 * Flush legacy index data for a single field using batch operations.
	 *
	 * Collects all posts and values, then performs a single batch delete
	 * followed by a single batch insert for efficiency.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param array $post_data Array of post data keyed by post ID.
	 */
	private function flush_legacy_field_data( $field_id, $post_data ) {
		if ( empty( $post_data ) ) {
			return;
		}

		// Collect all post IDs and build all items for batch insert.
		$post_ids = array_keys( $post_data );
		$items    = array();

		foreach ( $post_data as $post_id => $data ) {
			if ( ! empty( $data['values'] ) ) {
				foreach ( $data['values'] as $value ) {
					$items[] = array(
						'field_id'         => $field_id,
						'object_id'        => $post_id,
						'object_parent_id' => $data['parent_id'],
						'value'            => $value,
					);
				}
			}
		}

		// Use batch replace: delete all posts for field, then insert all items.
		Legacy_Batch_Query::batch_replace( $field_id, $post_ids, $items );
	}

	/**
	 * Get processed field IDs.
	 *
	 * Returns fields that were successfully flushed. Used for resume support.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of field IDs.
	 */
	public function get_processed_fields() {
		return $this->processed_fields;
	}

	/**
	 * Clear all collected data.
	 *
	 * @since 3.2.0
	 */
	public function clear() {
		$this->bitmap_data = array();
		$this->bucket_data = array();
		$this->search_data = array();
	}

	/**
	 * Get count of posts collected for indexing.
	 *
	 * @since 3.2.0
	 *
	 * @return int Total unique posts across all index types.
	 */
	public function get_post_count() {
		// Use array keys as a hash set for O(1) deduplication.
		// Avoids O(n²) array_merge in loops and O(n log n) array_unique.
		$seen = array();

		foreach ( $this->bitmap_data as $posts ) {
			foreach ( $posts as $post_id => $data ) {
				$seen[ $post_id ] = true;
			}
		}
		foreach ( $this->bucket_data as $posts ) {
			foreach ( $posts as $post_id => $data ) {
				$seen[ $post_id ] = true;
			}
		}
		foreach ( $this->search_data as $posts ) {
			foreach ( $posts as $post_id => $data ) {
				$seen[ $post_id ] = true;
			}
		}

		return count( $seen );
	}

	/**
	 * Check if there's data to flush.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if there's collected data.
	 */
	public function has_data() {
		return ! empty( $this->bitmap_data ) ||
				! empty( $this->bucket_data ) ||
				! empty( $this->search_data );
	}

	/**
	 * Get the index type for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return string|null Index type: 'bitmap', 'bucket', 'search', or null.
	 */
	private function get_field_index_type( $field_id ) {
		if ( isset( self::$field_type_cache[ $field_id ] ) ) {
			return self::$field_type_cache[ $field_id ];
		}

		$field = Field::get_instance( $field_id );

		if ( is_wp_error( $field ) ) {
			return null;
		}

		$strategy                            = Index_Strategy_Factory::for_field( $field );
		$index_type                          = $strategy ? $strategy->get_type() : null;
		self::$field_type_cache[ $field_id ] = $index_type;

		return $index_type;
	}

	/**
	 * Reset the batch writer cache.
	 *
	 * Clears the field type cache.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$field_type_cache = array();
	}
}
