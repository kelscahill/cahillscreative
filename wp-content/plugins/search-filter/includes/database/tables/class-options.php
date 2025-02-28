<?php
namespace Search_Filter\Database\Tables;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Options extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'options';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'search_filter_options_table_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Options';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.1';
	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.1' => 'upgrade_3_0_1',
	);
	/**
	 * Setup this database table.
	 *
	 * @since 1.0.0
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

	// Clear the data_store cache when the table is dropped.
	public function drop() {
		// Drop the table
		parent::drop();
		Data_Store::flush( 'options' );
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
