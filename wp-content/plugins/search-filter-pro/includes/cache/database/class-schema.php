<?php
/**
 * Cache Schema Class.
 *
 * Defines the database schema for the generic cache table.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache\Database
 */

namespace Search_Filter_Pro\Cache\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema as Engine_Schema;

/**
 * Cache Schema Class.
 *
 * @since 3.2.0
 */
class Schema extends Engine_Schema {

	/**
	 * Array of database column objects.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	public $columns = array(

		// id.
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
			'not_in'   => true,
		),

		// Cache group (e.g., 'query_cache_42', 'parent_map').
		array(
			'name'       => 'cache_group',
			'type'       => 'varchar',
			'length'     => '100',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true,
		),

		// Cache key (MD5 hash for fast lookups).
		array(
			'name'     => 'cache_key',
			'type'     => 'char',
			'length'   => '32',
			'default'  => '',
			'sortable' => true,
		),

		// Cache value (stored data).
		array(
			'name'    => 'cache_value',
			'type'    => 'longtext',
			'default' => '',
		),

		// Format indicator (raw, processed, serialized).
		array(
			'name'     => 'format',
			'type'     => 'varchar',
			'length'   => '30',
			'default'  => 'raw',
			'sortable' => true,
		),

		// Expires timestamp.
		array(
			'name'        => 'expires',
			'type'        => 'bigint',
			'length'      => '20',
			'default'     => '0',
			'range_query' => true,
			'unsigned'    => true,
		),
	);
}
