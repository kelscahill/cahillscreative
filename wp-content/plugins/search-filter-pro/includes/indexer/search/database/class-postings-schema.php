<?php
/**
 * Search Postings Schema Class.
 *
 * Defines the schema for the search postings (inverted index) table.
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
 * Search Postings Schema Class.
 *
 * @since 3.0.9
 */
class Postings_Schema extends \Search_Filter_Pro\Database\Engine\Schema {

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
		),

		// object_id.
		array(
			'name'     => 'object_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
		),

		// field_id.
		array(
			'name'     => 'field_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
		),

		// source_name - the data source type (e.g., 'post_title', 'post_content', 'post_meta:price').
		array(
			'name'    => 'source_name',
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

		// term_frequency.
		array(
			'name'     => 'term_frequency',
			'type'     => 'tinyint',
			'unsigned' => true,
		),

		// positions.
		array(
			'name'     => 'positions',
			'type'     => 'text',
			'nullable' => true,
			'default'  => null,
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
		return $wpdb->prefix . 'search_filter_search_postings';
	}
}
