<?php
namespace Search_Filter\Database\Tables;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Queries extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'queries';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'search_filter_queries_table_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Queries';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.3';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.2' => 'upgrade_3_0_2',
		'3.0.3' => 'upgrade_3_0_3',
	);

	/**
	 * Setup this database table.
	 *
	 * @since 1.0.0
	 */
	protected function set_schema() {
		$this->schema = '
			id              bigint(20)   NOT NULL AUTO_INCREMENT,
			name            mediumtext   NOT NULL,
			status          varchar(20)  NOT NULL,
			attributes      longtext     NOT NULL,
			context         varchar(250) NOT NULL,
			integration     mediumtext   NOT NULL,
			css             longtext     NOT NULL,
			date_created    datetime     NOT NULL default CURRENT_TIMESTAMP,
			date_modified   datetime     NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
			';
	}


	// Clear the data_store cache when the table is dropped.
	public function drop() {
		// Drop the table
		parent::drop();

		Data_Store::flush( 'query' );
	}
	/**
	 * Add context and integration columns to table.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_2() {
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `context` varchar(250) NOT NULL;"
		);
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `integration` mediumtext NOT NULL;"
		);
		return $this->is_success( $result );
	}

	/**
	 * Add css column to table.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_3() {
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `css` longtext NOT NULL;"
		);
		return $this->is_success( $result );
	}
}
