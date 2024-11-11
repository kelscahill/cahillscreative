<?php
/**
 * Fields Schema Class.
 */
namespace Search_Filter\Database\Schemas;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema;

/**
 * Fields Schema Class.
 *
 * @since 3.0
 */
class Fields extends Schema {

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
		),

		// Name.
		array(
			'name'       => 'name',
			'type'       => 'mediumtext',
			'sortable'   => true,
			'searchable' => true,
		),

		// Attributes (json object).
		array(
			'name' => 'attributes',
			'type' => 'longtext',
		),

		// connected query id.
		array(
			'name'     => 'query_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
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
			'name' => 'context_path',
			'type' => 'mediumtext',
		),

		// Status.
		array(
			'name'     => 'status',
			'type'     => 'varchar',
			'length'   => '20',
			'sortable' => true,
		),

		// Date created.
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current time in query class.
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// Date modified.
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		),
		// Generated CSS (cached).
		array(
			'name' => 'css',
			'type' => 'longtext',
		),
	);
}
