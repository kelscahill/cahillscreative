<?php
/**
 * Index Schema Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Task_Runner\Database
 */

namespace Search_Filter_Pro\Task_Runner\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tasks meta table class.
 *
 * @since 3.0.0
 */
class Tasks_Meta_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'taskmeta';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Tasks Meta';

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
			search_filter_task_id  bigint(20)   NOT NULL default '0',
			meta_key               varchar(255) NOT NULL,
			meta_value             longtext     NOT NULL,
			PRIMARY KEY (meta_id),
			KEY field_id (search_filter_task_id)
			";
	}
}
