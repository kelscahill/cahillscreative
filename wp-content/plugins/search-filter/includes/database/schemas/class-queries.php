<?php
/**
 * Queries Schema Class.
 */
namespace Search_Filter\Database\Schemas;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema;

/**
 * Queries Schema Class.
 *
 * @since 3.0
 */
class Queries extends Schema {

	/**
	 * Array of database column objects.
	 *
	 * @since 3.0
	 * @var   array
	 */
	public $columns = array(

		// id
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
		),

		// status
		array(
			'name'     => 'status',
			'type'     => 'varchar',
			'length'   => '20',
			'sortable' => true,

		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current time in query class
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// date_modified
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// name
		array(
			'name'       => 'name',
			'type'       => 'mediumtext',
			'sortable'   => true,
			'searchable' => true,

		),

		// attributes (json object)
		array(
			'name' => 'attributes',
			'type' => 'longtext',
		),

		// Context.
		array(
			'name'     => 'context',
			'type'     => 'varchar',
			'length'   => '250',
			'sortable' => true,
		),

		// Context path.
		array(
			'name' => 'integration',
			'type' => 'mediumtext',
		),
		// Generated CSS (cached).
		array(
			'name' => 'css',
			'type' => 'longtext',
		),
	);
}
