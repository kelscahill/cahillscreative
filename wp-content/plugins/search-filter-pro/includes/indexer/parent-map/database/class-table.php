<?php
/**
 * Parent Map Table Class.
 *
 * Stores global child→parent ID mappings for O(1) lookup
 * during child-level filtering (e.g., product variations, hierarchical posts).
 *
 * @since 3.2.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Parent_Map\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parent Map Table Class.
 *
 * @since 3.2.0
 */
class Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $name = 'parent_map_index';

	/**
	 * Optional description.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	public $description = 'Child to Parent ID Mapping';

	/**
	 * Database version.
	 *
	 * @since 3.2.0
	 * @var   mixed
	 */
	protected $version = '1.0.0';

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
	 * Composite primary key (child_id, source) allows:
	 * - Same child_id to have different parents in different sources
	 * - Future support for custom tables and data types
	 *
	 * @since 3.2.0
	 */
	protected function set_schema() {
		$this->schema = "
			child_id         bigint(20) unsigned NOT NULL,
			parent_id        bigint(20) unsigned NOT NULL,
			source           varchar(50) NOT NULL DEFAULT '',
			last_updated     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (child_id, source),
			KEY idx_parent_covering (parent_id, child_id),
			KEY idx_source_covering (source, child_id, parent_id),
			KEY idx_last_updated (last_updated)
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
		// Drop the table.
		$result = parent::drop();

		Data_Store::flush( 'parent_map' );

		return $result;
	}
}
