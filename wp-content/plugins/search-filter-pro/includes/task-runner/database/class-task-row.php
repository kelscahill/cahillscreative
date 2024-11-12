<?php
/**
 * Index Row Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Task_Runner\Database
 */

namespace Search_Filter_Pro\Task_Runner\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Row Class.
 *
 * @since 3.0.0
 */
class Task_Row extends \Search_Filter\Database\Engine\Row {
	/**
	 * The ID of the field.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $id = 0;
	/**
	 * The ID of the object.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $object_id = 0;

	/**
	 * The type of the task.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $type = '';

	/**
	 * The action of the task.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $action = '';

	/**
	 * The status of the task.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $status = '';

	/**
	 * The date the task was last modified.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $date_modified = false;
	/**
	 * Fields constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item The item to create the row from.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id            = (int) $this->id;
		$this->object_id     = (int) $this->object_id;
		$this->type          = (string) $this->type;
		$this->action        = (string) $this->action;
		$this->status        = (string) $this->status;
		$this->date_modified = false === $this->date ? 0 : strtotime( $this->date_modified );

		Data_Store::set( 'index', $this->id, $this );
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
	 * Get the object ID.
	 *
	 * @return int The ID of the object.
	 */
	public function get_object_id() {
		return $this->object_id;
	}

	/**
	 * Get the type.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the action.
	 */
	public function get_action() {
		return $this->action;
	}

	/**
	 * Get the status.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get the date modified.
	 *
	 * @return int The date the field was last modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}
}
