<?php
/**
 * Bucket Overflow Table Class.
 *
 * @since 3.2.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Bucket\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bucket Overflow Table Class.
 *
 * @since 3.2.0
 */
class Overflow_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $name = 'bucket_overflow';

	/**
	 * Optional description.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $description = 'Bucket Overflow';

	/**
	 * Database version.
	 *
	 * @since 3.2.0
	 * @var   mixed
	 */
	protected $version = '3.2.0';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $upgrades = array();

	/**
	 * Setup this database table.
	 *
	 * @since 3.2.0
	 */
	protected function set_schema() {
		$this->schema = "
			id                    bigint(20)    unsigned NOT NULL AUTO_INCREMENT,
			field_id              bigint(20)    unsigned NOT NULL,
			object_id             bigint(20)    unsigned NOT NULL,
			value                 decimal(20,6) NOT NULL,
			overflow_type         enum('BELOW_MIN','ABOVE_MAX','PENDING') NOT NULL DEFAULT 'PENDING',
			created_at            datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY field_value (field_id, value),
			KEY field_id (field_id),
			KEY object_id (object_id)
			";
	}

	/**
	 * Drop the table.
	 *
	 * Also clear the data_store cache when the table is dropped.
	 *
	 * @since 3.2.0
	 * @return bool Success status
	 */
	public function drop() {
		// Drop the table.
		$result = parent::drop();

		Data_Store::flush( 'bucket_overflow' );

		return $result;
	}
}
