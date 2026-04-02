<?php
/**
 * Bucket Metadata Table Class.
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
 * Bucket Metadata Table Class.
 *
 * @since 3.2.0
 */
class Metadata_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $name = 'bucket_metadata';

	/**
	 * Optional description.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $description = 'Bucket Metadata';

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
			field_id              bigint(20)    unsigned NOT NULL,
			min_value             decimal(20,6) NOT NULL,
			max_value             decimal(20,6) NOT NULL,
			unique_count          int(11)       unsigned NOT NULL DEFAULT '0',
			total_count           int(11)       unsigned NOT NULL DEFAULT '0',
			bucket_count          int(11)       unsigned NOT NULL DEFAULT '50',
			bucket_strategy       varchar(50)   NOT NULL DEFAULT 'percentile',
			last_rebuild          datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (field_id),
			KEY last_rebuild (last_rebuild)
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

		Data_Store::flush( 'bucket_metadata' );

		return $result;
	}
}
