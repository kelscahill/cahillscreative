<?php
/**
 * Search Terms Schema Class.
 *
 * Defines the schema for the search terms vocabulary table.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search/Database
 */

namespace Search_Filter_Pro\Indexer\Search\Database;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Terms Schema Class.
 *
 * @since 3.0.9
 */
class Terms_Schema extends \Search_Filter_Pro\Database\Engine\Schema {

	/**
	 * Array of database column objects.
	 *
	 * @since 3.0.9
	 * @var   array
	 */
	public $columns = array(

		// term_id.
		array(
			'name'     => 'term_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
		),

		// term.
		array(
			'name'    => 'term',
			'type'    => 'varchar',
			'length'  => '100',
			'default' => '',
		),

		// term_stem.
		array(
			'name'    => 'term_stem',
			'type'    => 'varchar',
			'length'  => '100',
			'default' => '',
		),

		// term_metaphone.
		array(
			'name'    => 'term_metaphone',
			'type'    => 'varchar',
			'length'  => '20',
			'default' => '',
		),

		// language.
		array(
			'name'    => 'language',
			'type'    => 'varchar',
			'length'  => '5',
			'default' => 'en',
		),

		// doc_frequency.
		array(
			'name'     => 'doc_frequency',
			'type'     => 'int',
			'length'   => '11',
			'unsigned' => true,
			'default'  => '0',
		),

		// collection_frequency.
		array(
			'name'     => 'collection_frequency',
			'type'     => 'int',
			'length'   => '11',
			'unsigned' => true,
			'default'  => '0',
		),
	);
}
