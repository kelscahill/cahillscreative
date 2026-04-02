<?php
/**
 * Options Database Table.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Database\Tables;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing the options database table.
 *
 * @since 3.0.0
 */
class Options extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'options';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Options';

	/**
	 * Database version.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.1';
	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.1' => 'upgrade_3_0_1',
	);
	/**
	 * Setup this database table.
	 *
	 * @since 3.0.0
	 */
	protected function set_schema() {
		$this->schema = '
			id              bigint(20)   NOT NULL AUTO_INCREMENT,
			name            varchar(200) NOT NULL,
			value           longtext     NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY name (name(191))
		';
	}

	/**
	 * Drop the table and clear the data_store cache.
	 *
	 * @since 3.0.0
	 * @return bool True if the table was dropped successfully.
	 */
	public function drop() {
		// Drop the table.
		$dropped = parent::drop();
		Data_Store::flush( 'option' );
		return $dropped;
	}

	/**
	 * Add unique key to the name column.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_1() {
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD UNIQUE INDEX name (name(191));"
		);
		return $this->is_success( $result );
	}
}
