<?php
/**
 * Index Schema Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema;

/**
 * Index Schema Class.
 *
 * @since 3.0
 */
class Index_Schema extends Schema {

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
		// object_id.
		array(
			'name'     => 'object_id',
			'type'     => 'bigint',
			'length'   => '20',
			'default'  => '0',
			'unsigned' => true,
		),
		// object_parent_id.
		array(
			'name'     => 'object_parent_id',
			'type'     => 'bigint',
			'length'   => '20',
			'default'  => '0',
			'unsigned' => true,
		),
		// field_id.
		array(
			'name'     => 'field_id',
			'type'     => 'bigint',
			'length'   => '20',
			'default'  => '0',
			'unsigned' => true,
		),
		// value.
		array(
			'name'        => 'value',
			'type'        => 'varchar',
			'length'      => '200',
			'default'     => '',
			'unsigned'    => true,
			'range_query' => true,
		),

		// Date modified.
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current time in query class.
			'modified'   => true,
			'date_query' => true,
		),
	);
}
