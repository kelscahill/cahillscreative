<?php
/**
 * The task definition.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Task_Runner
 */

namespace Search_Filter_Pro\Task_Runner;

use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The task definition.
 *
 * @since 3.0.0
 */
class Task {

	/**
	 * ID.
	 *
	 * @var int
	 */
	private $id = 0;
	/**
	 * The task type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The task action.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The task status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * The task object ID.
	 *
	 * @var int
	 */
	private $object_id;

	/**
	 * The task date modified.
	 *
	 * @var int
	 */
	private $date_modified;

	/**
	 * Copy of the record.
	 *
	 * @var \Search_Filter_Pro\Task_Runner\Database\Task_Row
	 */
	private $record;

	/**
	 * The table name.
	 *
	 * Used for looking up meta data.
	 *
	 * @var string
	 */
	private static $table = 'search_filter_task';

	/**
	 * Constructor.
	 *
	 * @param array $task The task to create the row from.
	 */
	public function __construct( $task = array() ) {
		$this->set_data( $task );
	}

	/**
	 * Load the record from the database.
	 *
	 * @param Search_Filter_Pro\Task_Runner\Database\Task_Row $item  The record row to load.
	 */
	public function load_record( $item ) {
		$data = array(
			'id'            => $item->get_id(),
			'type'          => $item->get_type(),
			'action'        => $item->get_action(),
			'status'        => $item->get_status(),
			'object_id'     => $item->get_object_id(),
			'date_modified' => $item->get_date_modified(),
			'record'        => $item,
		);
		$this->set_data( $data );
	}

	/**
	 * Set the internal data in one call.
	 *
	 * @param array $data The data to set.
	 */
	public function set_data( $data ) {
		if ( empty( $data ) ) {
			return;
		}
		if ( isset( $data['id'] ) ) {
			$this->id = $data['id'];
		}
		if ( isset( $data['type'] ) ) {
			$this->type = $data['type'];
		}
		if ( isset( $data['action'] ) ) {
			$this->action = $data['action'];
		}
		if ( isset( $data['status'] ) ) {
			$this->status = $data['status'];
		}
		if ( isset( $data['object_id'] ) ) {
			$this->object_id = $data['object_id'];
		}
		if ( isset( $data['date_modified'] ) ) {
			$this->date_modified = $data['date_modified'];
		}
		if ( isset( $data['record'] ) ) {
			$this->record = $data['record'];
		}
	}

	/**
	 * Get the DB data for the task.
	 *
	 * @return array
	 */
	public function get_db_data() {
		$data = array();
		if ( $this->id ) {
			$data['id'] = $this->id;
		}
		if ( $this->type ) {
			$data['type'] = $this->type;
		}
		if ( $this->action ) {
			$data['action'] = $this->action;
		}
		if ( $this->status ) {
			$data['status'] = $this->status;
		}
		if ( $this->object_id ) {
			$data['object_id'] = $this->object_id;
		}
		if ( $this->date_modified ) {
			$data['date_modified'] = $this->date_modified;
		}

		return $data;
	}

	/**
	 * Get the ID of the task.
	 *
	 * @return int The ID of the task.
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Get the type of the task.
	 *
	 * @return string The type of the task.
	 */
	public function get_type() {
		return $this->type;
	}
	/**
	 * Get the action of the task.
	 *
	 * @return string The action of the task.
	 */
	public function get_action() {
		return $this->action;
	}
	/**
	 * Get the status of the task.
	 *
	 * @return string The status of the task.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Set the status of the task.
	 *
	 * @param string $status The status of the task.
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}
	/**
	 * Get the object ID of the task.
	 *
	 * @return int The object ID of the task.
	 */
	public function get_object_id() {
		return $this->object_id;
	}
	/**
	 * Get the date modified of the task.
	 *
	 * @return int The date modified of the task.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}
	/**
	 * Saves the task to the database.
	 *
	 * @since 3.0.0
	 *
	 * @param array $extra_args Extra arguments to pass to the save function.
	 *
	 * @return int The saved task ID.
	 */
	public function save( $extra_args = array() ) {
		// TODO: figure out some good permissions.
		// Need to create new permissions for:
			// - edit_search_filter_fields.
			// - edit_search_filter_queries.
			// - edit_search_filter_styles.
			// - manage_search_filter.

		// Prepare the record.
		$record = $this->get_db_data();

		// Add extra args.
		$record = wp_parse_args( $record, $extra_args );

		// Grab db instance.
		$query = new Tasks_Query();

		do_action( 'search-filter/task/pre_save', $this );

		// Add or update the record.
		if ( $this->id !== 0 ) {
			$result = $query->update_item( $this->id, $record );
		} else {
			$this->id = $query->add_item( $record );
		}

		// TODO - we need to update the local record and the Data_Store.
		$this->record = $query->get_item( $this->id );

		do_action( 'search-filter/task/save', $this );

		return $this->id;
	}
	/**
	 * Deletes an instantiated query from the database.
	 *
	 * @return bool True if deleted, false if not.
	 */
	public function delete() {
		/*
		 if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		} */
		if ( $this->id > 0 ) {
			$result   = self::destroy( $this->id );
			$this->id = 0;
			return $result;
		}
		return false;
	}

	/**
	 * Deletes a query by ID
	 *
	 * @param int $id The ID of the query to delete.
	 *
	 * @return bool True if deleted, false if not.
	 */
	public static function destroy( $id ) {
		/*
		 if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		} */

		$query = new Database\Tasks_Query();

		// Cast to bool, as delete will return an int of number of deleted items on success
		// or false on failure.
		return (bool) $query->delete_item( absint( $id ) );
	}

	/**
	 * Adds meta data to the task.
	 *
	 * @param string  $meta_key    The key of the meta data to add.
	 * @param mixed   $meta_value  The value of the meta data to add.
	 * @param boolean $unique      Whether to make the meta data unique.
	 */
	public function add_meta( $meta_key, $meta_value, $unique = false ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		if ( $this->id === 0 ) {
			return false;
		}
		return add_metadata( static::$table, $this->id, $meta_key, $meta_value, $unique );
	}
	/**
	 * Updates meta data for the field.
	 *
	 * @param string $meta_key    The key of the meta data to update.
	 * @param mixed  $meta_value  The value of the meta data to update.
	 * @param string $prev_value  The previous value to update - leave empty to update all.
	 */
	public function update_meta( $meta_key, $meta_value, $prev_value = '' ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		if ( $this->id === 0 ) {
			return false;
		}
		return update_metadata( static::$table, $this->id, $meta_key, $meta_value, $prev_value );
	}
	/**
	 * Deletes meta data for the field.
	 *
	 * @param string $meta_key    The key of the meta data to delete.
	 * @param string $meta_value  The value of the meta data to delete.
	 */
	public function delete_meta( $meta_key, $meta_value = '' ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		if ( $this->id === 0 ) {
			return false;
		}
		return delete_metadata( static::$table, $this->id, $meta_key, $meta_value );
	}
	/**
	 * Gets meta data for the field.
	 *
	 * @param string $meta_key  The key of the meta data to get.
	 * @param string $single    Whether to retreive a single instance or all.
	 */
	public function get_meta( $meta_key, $single = false ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		if ( $this->id === 0 ) {
			return false;
		}
		return get_metadata( static::$table, $this->id, $meta_key, $single );
	}
}
