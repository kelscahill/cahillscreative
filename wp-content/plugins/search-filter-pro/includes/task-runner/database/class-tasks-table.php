<?php
/**
 * Index Table Class.
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
 * Index Table Class.
 *
 * @since 3.0.0
 */
class Tasks_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'tasks';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Tasks';

	/**
	 * Database version.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.3';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.1' => 'upgrade_3_0_1',
		'3.0.2' => 'upgrade_3_0_2',
		'3.0.3' => 'upgrade_3_0_3',
	);

	/**
	 * Setup this database table.
	 *
	 * @since 3.0.0
	 */
	protected function set_schema() {
		$this->schema = "
			id                    bigint(20)   NOT NULL AUTO_INCREMENT,
			type                  varchar(20)  NOT NULL,
			action                varchar(20)  NOT NULL,
			status                varchar(20)  NOT NULL,
			object_id             bigint(20)   NOT NULL default '0',
			parent_id             bigint(20)   NOT NULL default '0',
			batch_id              varchar(32)  NULL default NULL,
			date_modified         datetime     NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type(20)),
			KEY action (action(20)),
			KEY status (status(20)),
			KEY object (object_id),
			KEY parent (parent_id),
			KEY batch (batch_id)
			";
	}

	/**
	 * Drop the table.
	 *
	 * Also clear the data_store cache when the table is dropped.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public function drop() {
		// Drop the table.
		$result = parent::drop();

		Data_Store::flush( 'tasks' );

		return $result;
	}

	/**
	 * Add parent_id column for parent-child task relationships.
	 *
	 * @since 3.0.1
	 * @return bool
	 */
	public function upgrade_3_0_1() {
		// Skip if column already exists (e.g. fresh install with latest schema).
		$column = $this->get_db()->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'parent_id'" );
		if ( ! empty( $column ) ) {
			return true;
		}

		// Add parent_id column.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `parent_id` bigint(20) NOT NULL default '0' AFTER `object_id`;"
		);

		if ( ! $this->is_success( $result ) ) {
			return false;
		}

		// Add index on parent_id for efficient child lookups.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `parent` (`parent_id`);"
		);

		return $this->is_success( $result );
	}

	/**
	 * Add batch_id column for batch task operations.
	 *
	 * @since 3.0.2
	 * @return bool
	 */
	public function upgrade_3_0_2() {
		// Skip if column already exists (e.g. fresh install with latest schema).
		$column = $this->get_db()->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'batch_id'" );
		if ( ! empty( $column ) ) {
			return true;
		}

		// Add batch_id column.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `batch_id` varchar(32) NULL default NULL AFTER `parent_id`;"
		);

		if ( ! $this->is_success( $result ) ) {
			return false;
		}

		// Add index on batch_id for efficient batch lookups.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `batch` (`batch_id`);"
		);

		return $this->is_success( $result );
	}

	/**
	 * Re-run the 3.0.1 and 3.0.2 upgrades for users where they didn't fire.
	 *
	 * Checks if parent_id and batch_id columns already exist before attempting to add them.
	 *
	 * @since 3.0.3
	 * @return bool
	 */
	public function upgrade_3_0_3() {
		// Check if parent_id column exists.
		$parent_column = $this->get_db()->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'parent_id'" );

		if ( empty( $parent_column ) ) {
			// Column doesn't exist, run the 3.0.1 upgrade first.
			$result = $this->upgrade_3_0_1();
			if ( ! $result ) {
				return false;
			}
		}

		// Check if batch_id column already exists.
		$column = $this->get_db()->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'batch_id'" );

		if ( ! empty( $column ) ) {
			return true;
		}

		// Column doesn't exist, run the same upgrade as 3.0.2.
		return $this->upgrade_3_0_2();
	}
}
