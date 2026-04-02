<?php
/**
 * Search Terms Table Class.
 *
 * Stores unique vocabulary across all indexed documents.
 * Central table for term lookups and fuzzy matching.
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
 * Search Terms Table Class.
 *
 * @since 3.0.9
 */
class Terms_Table extends \Search_Filter_Pro\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $name = 'search_terms';

	/**
	 * Optional description.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	public $description = 'Search Index - Terms Vocabulary';

	/**
	 * Database version.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	protected $version = '1.0.0';

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
			term_id              bigint(20)   unsigned NOT NULL AUTO_INCREMENT,
			term                 varchar(100) NOT NULL,
			term_stem            varchar(100) NOT NULL DEFAULT '',
			term_metaphone       varchar(20)  NOT NULL DEFAULT '',
			language             varchar(5)   DEFAULT 'en',
			doc_frequency        int(11)      unsigned DEFAULT 0,
			collection_frequency int(11)      unsigned DEFAULT 0,
			PRIMARY KEY (term_id),
			UNIQUE KEY term_lang (term, language),
			KEY term (term(20)),
			KEY stem (term_stem(20)),
			KEY metaphone (term_metaphone),
			KEY language (language),
			KEY doc_frequency (doc_frequency)
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

		Data_Store::flush( 'search_terms' );

		return $result;
	}
}
