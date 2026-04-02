<?php
/**
 * Search Postings Table Class.
 *
 * Stores the inverted index: which documents contain which terms,
 * with frequencies and positions for scoring and phrase search.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search/Database
 */

namespace Search_Filter_Pro\Indexer\Search\Database;

use Search_Filter\Core\Data_Store;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Postings Table Class.
 *
 * @since 3.0.9
 */
class Postings_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $name = 'search_postings';

	/**
	 * Optional description.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $description = 'Search Index - Term Postings';

	/**
	 * Database version.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	protected $version = '2.0.0';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.0.9
	 * @var   array
	 */
	protected $upgrades = array();

	/**
	 * Setup this database table.
	 *
	 * @since 3.0.9
	 */
	protected function set_schema() {
		$this->schema = "
			term_id         bigint(20)   unsigned NOT NULL,
			object_id       bigint(20)   unsigned NOT NULL,
			field_id        bigint(20)   unsigned NOT NULL,
			source_name     varchar(20)  NOT NULL,
			language        varchar(5)   DEFAULT 'en',
			term_frequency  tinyint      unsigned NOT NULL,
			positions       text         DEFAULT NULL,
			PRIMARY KEY (term_id, object_id, field_id, source_name),
			KEY object_id (object_id),
			KEY field_id (field_id),
			KEY term_source (term_id, source_name),
			KEY language (language)
			";
	}

	/**
	 * Drop the table.
	 *
	 * Also clear the data_store cache when the table is dropped.
	 *
	 * @since 3.0.9
	 */
	public function drop() {
		// Drop the table.
		$result = parent::drop();

		Data_Store::flush( 'search_postings' );

		return $result;
	}
}
