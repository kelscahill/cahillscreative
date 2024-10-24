<?php
/**
 * Index Row Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Row Class.
 *
 * @since 3.0.0
 */
class Index_Row extends \Search_Filter\Database\Engine\Row {
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
	 * The ID of the parent object.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $object_parent_id = 0;
	/**
	 * The ID of the field.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $field_id = 0;
	/**
	 * The value of the field.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $value = '';
	/**
	 * The date the index option was last modified.
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
		$this->id               = (int) $this->id;
		$this->object_id        = (int) $this->object_id;
		$this->object_parent_id = (int) $this->object_parent_id;
		$this->field_id         = (int) $this->field_id;
		$this->value            = (string) $this->value;
		$this->date_modified    = false === $this->date ? 0 : strtotime( $this->date_modified );

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
	 * Get the object parent ID.
	 *
	 * @return int The ID of the parent object.
	 */
	public function get_object_parent_id() {
		return $this->object_parent_id;
	}

	/**
	 * Get the field ID.
	 *
	 * @return int The ID of the field.
	 */
	public function get_field_id() {
		return $this->field_id;
	}

	/**
	 * Get the value.
	 *
	 * @return string The value of the field.
	 */
	public function get_value() {
		return $this->value;
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
