<?php
/**
 * Document Statistics Table Class.
 *
 * Stores per-document statistics required for BM25 scoring.
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
 * Document Statistics Table Class.
 *
 * @since 3.0.9
 */
class Doc_Stats_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $name = 'search_doc_stats';

	/**
	 * Optional description.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $description = 'Search Index - Document Statistics';

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
			object_id           bigint(20)   unsigned NOT NULL,
			field_id            bigint(20)   unsigned NOT NULL,
			language            varchar(5)   DEFAULT 'en',
			word_count          int(11)      unsigned NOT NULL,
			avg_term_frequency  decimal(6,2) DEFAULT NULL,
			indexed_timestamp   datetime     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (object_id, field_id),
			KEY field_id (field_id),
			KEY language (language),
			KEY indexed_timestamp (indexed_timestamp)
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

		Data_Store::flush( 'search_doc_stats' );

		return $result;
	}
}
