<?php
/**
 * Index Table Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Legacy\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Index Table Class.
 *
 * @since 3.0.0
 */
class Index_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'index';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Legacy Index';

	/**
	 * Database version.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.4';

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
			object_id             bigint(20)   NOT NULL default '0',
			object_parent_id      bigint(20)   NOT NULL default '0',
			field_id              bigint(20)   NOT NULL,
			value                 varchar(200)  NOT NULL,
			date_modified         datetime     NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY value (value(191)),
			KEY field_objects (field_id, object_id, object_parent_id, value(50))
			";
	}

	/**
	 * Drop the table.
	 *
	 * Also clear the data_store cache when the table is dropped.
	 *
	 * @since 3.0.0
	 */
	public function drop() {
		// Drop the table.
		$result = parent::drop();

		Data_Store::flush( 'index' );

		return $result;
	}

	/**
	 * Change the value column to varchar(200) to match the max length of taxonomy term slugs.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_1() {

		// Alter the table so the `value` column is changed from varchar(50) to varchar(200).
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} MODIFY COLUMN `value` varchar(200) NOT NULL;"
		);

		return $this->is_success( $result );
	}

	/**
	 * Change the key from value(50) to value(191) for utf8mb4 compatibility.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_2() {
		// Alter the table so the `value` column key is changed from value(50) to value(191).
		// 191 is the max length for utf8mb4 indexes on older MySQL/MariaDB versions.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} DROP INDEX `value`;"
		);
		if ( ! $this->is_success( $result ) ) {
			return false;
		}
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `value` (`value`(191));"
		);
		if ( ! $this->is_success( $result ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Add covering index for field queries with parent/child collapsing.
	 *
	 * This index optimizes queries that filter by field_id and need to check
	 * both object_id and object_parent_id, particularly when collapsing
	 * children into parents for counting. Also removes the redundant field
	 * index since the new composite index can serve those queries.
	 *
	 * Additionally fixes the value index length for utf8mb4 compatibility.
	 *
	 * @since 3.0.3
	 * @return bool
	 */
	public function upgrade_3_0_3() {
		// Fix the value index to use 191 length for utf8mb4 compatibility.
		// First drop the existing index.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} DROP INDEX `value`;"
		);
		// phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace, Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( ! $this->is_success( $result ) ) {
			// Continue even if it fails, might not exist.
		}

		// Recreate with correct length.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `value` (`value`(191));"
		);
		if ( ! $this->is_success( $result ) ) {
			return false;
		}

		// Drop the redundant field index since our new composite index starts with field_id.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} DROP INDEX `field`;"
		);
		// phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace, Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( ! $this->is_success( $result ) ) {
			// If the field index doesn't exist (maybe already removed), continue anyway.
			// This ensures the upgrade doesn't fail on repeated attempts.
		}

		// Add a covering index to optimize queries with OR conditions on object_id/object_parent_id.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `field_objects` (`field_id`, `object_id`, `object_parent_id`, `value`(50));"
		);

		return $this->is_success( $result );
	}
}
