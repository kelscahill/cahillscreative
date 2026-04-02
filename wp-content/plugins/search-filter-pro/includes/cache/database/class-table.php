<?php
/**
 * Cache Table Class.
 *
 * Defines the database table for the generic cache.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache\Database
 */

namespace Search_Filter_Pro\Cache\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Table Class.
 *
 * @since 3.2.0
 */
class Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $name = 'cache';

	/**
	 * Optional description.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $description = 'Cache';

	/**
	 * Database version.
	 *
	 * @since 3.2.0
	 * @var mixed
	 */
	protected $version = '1.0.0';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $upgrades = array();

	/**
	 * Setup this database table.
	 *
	 * @since 3.2.0
	 */
	protected function set_schema() {
		$this->schema = "
			id                    bigint(20)  unsigned NOT NULL AUTO_INCREMENT,
			cache_group           varchar(100)         NOT NULL default '',
			cache_key             char(32)             NOT NULL default '',
			cache_value           longtext             NOT NULL default '',
			format                varchar(30)          NOT NULL default 'raw',
			expires               bigint(20)  unsigned NOT NULL default 0,
			PRIMARY KEY (id),
			UNIQUE KEY cache_lookup (cache_group, cache_key),
			KEY expiry_cleanup (expires)
			";
	}

	/**
	 * Drop the table.
	 *
	 * Also clear the data_store cache when the table is dropped.
	 *
	 * @since 3.2.0
	 */
	public function drop() {
		$result = parent::drop();

		Data_Store::flush( 'cache' );

		return $result;
	}
}
