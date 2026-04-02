<?php
/**
 * Upgrade Result value object
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object representing the result of an upgrade operation.
 */
class Upgrade_Result {

	/**
	 * Status of the upgrade: 'success', 'failed', or 'skipped'.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Optional message describing the result.
	 *
	 * @var string|null
	 */
	public $message;

	/**
	 * Array of error details for debugging.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param string      $status  Status: 'success', 'failed', or 'skipped'.
	 * @param string|null $message Optional message.
	 * @param array       $errors  Optional array of error details.
	 */
	public function __construct( $status, $message = null, $errors = array() ) {
		$this->status  = $status;
		$this->message = $message;
		$this->errors  = $errors;
	}

	/**
	 * Check if the upgrade was successful.
	 *
	 * @return bool
	 */
	public function is_success() {
		return $this->status === 'success';
	}

	/**
	 * Create a success result.
	 *
	 * @param string|null $message Optional success message.
	 * @return Upgrade_Result
	 */
	public static function success( $message = null ) {
		return new self( 'success', $message );
	}

	/**
	 * Create a failed result.
	 *
	 * @param string $message Error message.
	 * @param array  $errors  Optional array of error details.
	 * @return Upgrade_Result
	 */
	public static function failed( $message, $errors = array() ) {
		return new self( 'failed', $message, $errors );
	}

	/**
	 * Create a skipped result.
	 *
	 * @param string|null $message Optional reason for skipping.
	 * @return Upgrade_Result
	 */
	public static function skipped( $message = null ) {
		return new self( 'skipped', $message );
	}
}
