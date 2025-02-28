<?php
/**
 * Logs Schema Class.
 */
namespace Search_Filter\Database\Schemas;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Search_Filter\Database\Engine\Schema;

/**
 * Logs Schema Class.
 *
 * @since 3.0
 */
class Logs extends Schema {

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

		// Message.
		array(
			'name' => 'message',
			'type' => 'longtext',
		),

		// Level.
		array(
			'name'     => 'level',
			'type'     => 'varchar',
			'length'   => '20',
			'sortable' => true,
		),
	);
}
