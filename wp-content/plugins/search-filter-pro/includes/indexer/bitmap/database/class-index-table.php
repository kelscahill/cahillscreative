<?php
/**
 * Index Table Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Bitmap\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Table Class.
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
	public $name = 'bitmap_index';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Index';

	/**
	 * Database version.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.0';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.0.0
	 * @var   array
	 */
	protected $upgrades = array();

	/**
	 * Setup this database table.
	 *
	 * @since 3.0.0
	 */
	protected function set_schema() {
		$this->schema = "
			id                       bigint(20)   unsigned NOT NULL AUTO_INCREMENT,
			field_id                 bigint(20)   NOT NULL,
			value                    varchar(200) NOT NULL,
			bitmap_data              mediumblob   NOT NULL,
			object_count             int(11)      NOT NULL DEFAULT '0',
			max_object_id            bigint(20)   NOT NULL DEFAULT '0',
			last_updated             datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY field_value (field_id, value(191)),
			KEY field_id (field_id),
			KEY object_count (object_count),
			KEY last_updated (last_updated)
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

		Data_Store::flush( 'bitmap_index' );

		return $result;
	}
}
