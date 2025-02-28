<?php
namespace Search_Filter\Database\Rows;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Log extends \Search_Filter\Database\Engine\Row {

	/**
	 * The ID of the option.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * The message of the log.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $message = '';

	/**
	 * The level of the log.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $level = '';

	/**
	 * Log constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id      = (int) $this->id;
		$this->message = (string) $this->message;
		$this->level   = (string) $this->level;
	}

	/**
	 * Get for the ID.
	 *
	 * @return int The ID of the field.
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Get the Name.
	 *
	 * @return string The name of the field.
	 */
	public function get_message() {
		return $this->message;
	}
	/**
	 * Get the status.
	 *
	 * @return string The status of the field.
	 */
	public function get_level() {
		return $this->level;
	}
}
