<?php
/**
 * Add error codes + data to exceptions.
 *
 * Originally taken from WooCommerce - https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-data-exception.html
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom error codes + data to exceptions.
 */
class Exception extends \Exception {
	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Extra error data.
	 *
	 * @var array
	 */
	protected $error_data;

	/**
	 * Setup exception.
	 *
	 * @param string $message          User-friendly translated error message, e.g. 'Setting is invalid.'.
	 * @param int    $code             Machine-readable error code, e.g 101.
	 * @param array  $previous         The previously thrown exception.
	 * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
	 * @param array  $data             Extra error data.
	 */
	public function __construct( $message, $code, $previous = null, $http_status_code = 400, $data = array() ) {
		$this->error_data = array_merge( array( 'status' => $http_status_code ), $data );
		parent::__construct( $message, $code, $previous );
	}
	/**
	 * Returns error data.
	 *
	 * @return array
	 */
	public function get_error_data() {
		return $this->error_data;
	}
}
