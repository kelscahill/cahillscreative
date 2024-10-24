<?php
/**
 * Index Schema Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Cache
 */

namespace Search_Filter_Pro\Indexer\Cache;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema as Engine_Schema;

/**
 * Index Schema Class.
 *
 * @since 3.0
 */
class Schema extends Engine_Schema {

	/**
	 * Array of database column objects.
	 *
	 * @since 3.0
	 * @var   array
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

		// Related query id.
		array(
			'name'     => 'query_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),

		// Related field id.
		array(
			'name'     => 'field_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),

		// Type of item.
		array(
			'name'     => 'type',
			'type'     => 'varchar',
			'length'   => '20',
			'default'  => '',
			'sortable' => true,
		),

		// Cache key.
		array(
			'name'    => 'cache_key',
			'type'    => 'text',
			'default' => '',
		),
		// cache_value.
		array(
			'name'    => 'cache_value',
			'type'    => 'longtext',
			'default' => '',
		),

		// Date modified.
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current time in query class.
			'modified'   => true,
			'date_query' => true,
		),

		// Expires.
		array(
			'name'        => 'expires',
			'type'        => 'bigint',
			'length'      => '20',
			'default'     => '', // Defaults to current time in query class.
			'range_query' => true,
			'unsigned'    => true,

		),
	);
}
