<?php
/**
 * Defines the structure of the plugin data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the general Schema of the data used
 */
class Schema extends \Search_Filter\Core\Schema {
	/**
	 * The reference to the tables that are created.
	 *
	 * @var array
	 */
	private $tables = array();

	/**
	 * Init the database tables.
	 *
	 * @since    3.0.0
	 */
	protected function init_db() {
		$this->tables['index'] = new \Search_Filter_Pro\Indexer\Database\Index_Table();
		// If the table does not exist, then create the table.
		if ( ! $this->tables['index']->exists() ) {
			$this->tables['index']->install();
		}

		$this->tables['index_cache'] = new \Search_Filter_Pro\Indexer\Cache\Table();
		// If the table does not exist, then create the table.
		if ( ! $this->tables['index_cache']->exists() ) {
			$this->tables['index_cache']->install();
		}

		$this->tables['tasks'] = new \Search_Filter_Pro\Task_Runner\Database\Tasks_Table();
		// If the table does not exist, then create the table.
		if ( ! $this->tables['tasks']->exists() ) {
			$this->tables['tasks']->install();
		}

		$this->tables['tasks_meta'] = new \Search_Filter_Pro\Task_Runner\Database\Tasks_Meta_Table();
		// If the table does not exist, then create the table.
		if ( ! $this->tables['tasks_meta']->exists() ) {
			$this->tables['tasks_meta']->install();
		}
	}
}
