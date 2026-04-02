<?php
/**
 * Logs Database Table.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Database\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing the logs database table.
 *
 * @since 3.0.0
 */
class Logs extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'logs';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Logs';

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
		$this->schema = '
			id              bigint(20)   NOT NULL AUTO_INCREMENT,
			message         longtext     NOT NULL,
			level           varchar(20)  NOT NULL,
			date_created    datetime     NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		';
	}
}
