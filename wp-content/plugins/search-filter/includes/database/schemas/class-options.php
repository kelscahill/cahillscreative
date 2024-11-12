<?php
/**
 * Options Schema Class.
 */
namespace Search_Filter\Database\Schemas;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema;

/**
 * Options Schema Class.
 *
 * @since 3.0
 */
class Options extends Schema {

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
			'type'       => 'varchar',
			'length'     => '200',
			'sortable'   => true,
			'searchable' => true,
		),

		// Value.
		array(
			'name' => 'value',
			'type' => 'longtext',
		),
	);
}
