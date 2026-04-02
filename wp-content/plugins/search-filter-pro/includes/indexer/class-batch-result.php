<?php
/**
 * Batch Result - Tracks success/failure for batch indexing operations.
 *
 * Provides a structured way to track which posts and fields succeeded or
 * failed during batch indexing, enabling retry logic for failed items.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Batch Result class.
 *
 * Tracks success and failure counts along with detailed failure information
 * for batch indexing operations.
 *
 * @since 3.2.0
 */
class Batch_Result {

	/**
	 * Number of successful operations.
	 *
	 * @var int
	 */
	public $success_count = 0;

	/**
	 * Number of failed operations.
	 *
	 * @var int
	 */
	public $failure_count = 0;

	/**
	 * Array of failed items with details.
	 *
	 * Each item contains: post_id, field_id, index_type, error.
	 *
	 * @var array
	 */
	public $failed_items = array();

	/**
	 * Record a batch of successful operations.
	 *
	 * @since 3.2.0
	 *
	 * @param int $count Number of successful operations.
	 */
	public function record_success( $count ) {
		$this->success_count += $count;
	}

	/**
	 * Record a failed operation.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $post_id    Post ID that failed.
	 * @param int    $field_id   Field ID that failed.
	 * @param string $error      Error message.
	 * @param string $index_type Optional. Index type (bitmap, bucket, search).
	 */
	public function record_failure( $post_id, $field_id, $error, $index_type = '' ) {
		++$this->failure_count;
		$this->failed_items[] = array(
			'post_id'    => $post_id,
			'field_id'   => $field_id,
			'index_type' => $index_type,
			'error'      => $error,
			'time'       => current_time( 'mysql' ),
		);
	}

	/**
	 * Record multiple failures for a field (e.g., when entire field batch fails).
	 *
	 * @since 3.2.0
	 *
	 * @param int    $field_id   Field ID that failed.
	 * @param array  $post_ids   Array of post IDs that failed.
	 * @param string $error      Error message.
	 * @param string $index_type Optional. Index type (bitmap, bucket, search).
	 */
	public function record_field_failure( $field_id, $post_ids, $error, $index_type = '' ) {
		foreach ( $post_ids as $post_id ) {
			$this->record_failure( $post_id, $field_id, $error, $index_type );
		}
	}

	/**
	 * Check if there are any failures.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if there are failures.
	 */
	public function has_failures() {
		return $this->failure_count > 0;
	}

	/**
	 * Get unique post IDs that failed.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of unique post IDs.
	 */
	public function get_failed_post_ids() {
		return array_unique( array_column( $this->failed_items, 'post_id' ) );
	}

	/**
	 * Get unique field IDs that failed.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of unique field IDs.
	 */
	public function get_failed_field_ids() {
		return array_unique( array_column( $this->failed_items, 'field_id' ) );
	}

	/**
	 * Get a summary of errors for logging.
	 *
	 * @since 3.2.0
	 *
	 * @return string Error summary.
	 */
	public function get_error_summary() {
		if ( empty( $this->failed_items ) ) {
			return '';
		}

		$unique_errors = array_unique( array_column( $this->failed_items, 'error' ) );
		return implode( '; ', $unique_errors );
	}

	/**
	 * Check if the batch was fully successful.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if no failures occurred.
	 */
	public function is_fully_successful() {
		return ! $this->has_failures();
	}

	/**
	 * Merge another Batch_Result into this one.
	 *
	 * @since 3.2.0
	 *
	 * @param Batch_Result $other Another batch result to merge.
	 */
	public function merge( Batch_Result $other ) {
		$this->success_count += $other->success_count;
		$this->failure_count += $other->failure_count;
		$this->failed_items   = array_merge( $this->failed_items, $other->failed_items );
	}

	/**
	 * Get total operations count.
	 *
	 * @since 3.2.0
	 *
	 * @return int Total number of operations (success + failure).
	 */
	public function get_total_count() {
		return $this->success_count + $this->failure_count;
	}

	/**
	 * Reset the result tracker.
	 *
	 * @since 3.2.0
	 */
	public function reset() {
		$this->success_count = 0;
		$this->failure_count = 0;
		$this->failed_items  = array();
	}
}
