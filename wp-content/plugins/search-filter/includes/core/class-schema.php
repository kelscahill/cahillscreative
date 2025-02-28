<?php
/**
 * Defines the structure of the plugin data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the general Schema of the data used
 * Think post types and taxonomies
 */
class Schema {
	/**
	 * The reference to the tables that are created.
	 *
	 * @var array
	 */
	private $tables = array();
	/**
	 * Creates the main structure for the plugin.
	 *
	 * Currently, this is only the tables, but in the future, we might want to
	 * create the options and other data structures.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		$this->init_db();
	}
	/**
	 * Creates the Tables.
	 *
	 * @since    3.0.0
	 */
	protected function init_db() {
		/**
		 * Instantiating the class runs update + install process (if done before
		 * `admin_init` hook has fired).
		 */
		$this->tables['fields'] = new \Search_Filter\Database\Tables\Fields();
		// If the table does not exist, then create the table.
		if ( ! $this->tables['fields']->exists() ) {
			$this->tables['fields']->install();
		}

		$this->tables['fields_meta'] = new \Search_Filter\Database\Tables\Fields_Meta();
		if ( ! $this->tables['fields_meta']->exists() ) {
			$this->tables['fields_meta']->install();
		}

		$this->tables['queries'] = new \Search_Filter\Database\Tables\Queries();
		if ( ! $this->tables['queries']->exists() ) {
			$this->tables['queries']->install();
		}

		$this->tables['queries_meta'] = new \Search_Filter\Database\Tables\Queries_Meta();
		if ( ! $this->tables['queries_meta']->exists() ) {
			$this->tables['queries_meta']->install();
		}

		$this->tables['styles'] = new \Search_Filter\Database\Tables\Style_Presets();
		if ( ! $this->tables['styles']->exists() ) {
			$this->tables['styles']->install();
		}

		$this->tables['styles_meta'] = new \Search_Filter\Database\Tables\Styles_Meta();
		if ( ! $this->tables['styles_meta']->exists() ) {
			$this->tables['styles_meta']->install();
		}

		$this->tables['options'] = new \Search_Filter\Database\Tables\Options();
		if ( ! $this->tables['options']->exists() ) {
			$this->tables['options']->install();
		}

		$this->tables['logs'] = new \Search_Filter\Database\Tables\Logs();
		if ( ! $this->tables['logs']->exists() ) {
			$this->tables['logs']->install();
		}
	}

	/**
	 * Get the tables.
	 *
	 * @since    3.0.0
	 */
	public function get_tables() {
		return $this->tables;
	}
}
