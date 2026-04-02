<?php
/**
 * Bucket Index Table Class.
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
 * Bucket Index Table Class.
 *
 * @since 3.2.0
 */
class Index_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $name = 'bucket_index';

	/**
	 * Optional description.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $description = 'Bucket Index';

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
			id                       bigint(20)   unsigned NOT NULL AUTO_INCREMENT,
			field_id                 bigint(20)   unsigned NOT NULL,
			bucket_id                int(11)      unsigned NOT NULL,
			bucket_type              enum('percentile','overflow_below','overflow_above') NOT NULL DEFAULT 'percentile',
			min_value                decimal(20,6) NOT NULL,
			max_value                decimal(20,6) NOT NULL,
			item_count               int(11)      unsigned NOT NULL DEFAULT '0',
			bitmap_data              mediumblob   NOT NULL,
			values_data              mediumblob   NULL,
			values_format            varchar(20)  DEFAULT 'serialize',
			values_compressed        tinyint(1)   DEFAULT '1',
			last_updated             datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY field_bucket (field_id, bucket_id),
			KEY field_range (field_id, min_value, max_value),
			KEY field_id (field_id),
			KEY last_updated (last_updated)
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

		Data_Store::flush( 'bucket_index' );

		return $result;
	}
}
