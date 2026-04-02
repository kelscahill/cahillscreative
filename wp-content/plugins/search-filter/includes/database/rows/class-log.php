<?php
/**
 * Log Row Class.
 *
 * @package     Database
 * @subpackage  Rows
 * @copyright   Copyright (c) 2020
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0.0
 */

namespace Search_Filter\Database\Rows;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log row class.
 *
 * @since 3.0.0
 */
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
	 * The date the field was created.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	public $date_created = 0;

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
	 * @since 3.0.0
	 *
	 * @param object $item The log item data.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id           = (int) $this->id;
		$this->message      = (string) $this->message;
		$this->level        = (string) $this->level;
		$this->date_created = empty( $this->date_created ) ? 0 : strtotime( (string) $this->date_created );
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
