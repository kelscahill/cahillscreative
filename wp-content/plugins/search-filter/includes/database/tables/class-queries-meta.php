<?php
/**
 * Queries Meta Database Table.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Database\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing the queries meta database table.
 *
 * @since 3.0.0
 */
class Queries_Meta extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'querymeta';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Queries Meta';

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
			meta_id                bigint(20)   NOT NULL AUTO_INCREMENT,
			search_filter_query_id bigint(20)   NOT NULL default '0',
			meta_key               varchar(255) NOT NULL,
			meta_value             longtext     NOT NULL,
			PRIMARY KEY (meta_id),
			KEY query_id (search_filter_query_id)
			";
	}
}
