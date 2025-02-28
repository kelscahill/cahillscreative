<?php
/**
 * Index Table Class.
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
 * Index Table Class.
 *
 * @since 3.0.0
 */
class Index_Table extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'index';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'search_filter_pro_index_table_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Index';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.2';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.1' => 'upgrade_3_0_1',
		'3.0.2' => 'upgrade_3_0_2',
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
			KEY value (value(200)),
			KEY field (field_id)
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
		parent::drop();

		Data_Store::flush( 'index' );
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
	 * Change the key from value(50) to value(200).
	 *
	 * @return bool
	 */
	public function upgrade_3_0_2() {
		// Alter the table so the `value` column key is changed from value(50) to value(200).
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} DROP INDEX `value`;"
		);
		if ( ! $this->is_success( $result ) ) {
			return false;
		}
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD INDEX `value` (`value`(200));"
		);
		if ( ! $this->is_success( $result ) ) {
			return false;
		}
		return true;
	}
}
