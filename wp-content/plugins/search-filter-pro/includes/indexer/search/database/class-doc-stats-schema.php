<?php
/**
 * Document Statistics Schema Class.
 *
 * Defines the schema for the document statistics table.
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
 * Document Statistics Schema Class.
 *
 * @since 3.0.9
 */
class Doc_Stats_Schema extends \Search_Filter_Pro\Database\Engine\Schema {

	/**
	 * Array of database column objects.
	 *
	 * @since 3.0.9
	 * @var   array
	 */
	public $columns = array(

		// object_id.
		array(
			'name'     => 'object_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'primary'  => true,
		),

		// language.
		array(
			'name'    => 'language',
			'type'    => 'varchar',
			'length'  => '5',
			'default' => 'en',
		),

		// word_count.
		array(
			'name'     => 'word_count',
			'type'     => 'int',
			'length'   => '11',
			'unsigned' => true,
		),

		// avg_term_frequency.
		array(
			'name'     => 'avg_term_frequency',
			'type'     => 'decimal',
			'length'   => '6,2',
			'nullable' => true,
			'default'  => null,
		),

		// indexed_timestamp.
		array(
			'name'    => 'indexed_timestamp',
			'type'    => 'datetime',
			'default' => '', // Defaults to current time in query class.
		),
	);

	/**
	 * Get the table name.
	 *
	 * @since 3.0.9
	 * @return string Table name with prefix.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'search_filter_search_doc_stats';
	}
}
