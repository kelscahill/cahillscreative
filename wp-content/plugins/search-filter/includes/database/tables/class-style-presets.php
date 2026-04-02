<?php
/**
 * Style Presets Database Table.
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
 * Class for managing the style presets database table.
 *
 * @since 3.0.0
 */
class Style_Presets extends \Search_Filter\Database\Engine\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $name = 'styles';

	/**
	 * Optional description.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $description = 'Style Groups';

	/**
	 * Database version.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $version = '3.0.3';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 3.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'3.0.1' => 'upgrade_3_0_1',
		'3.0.2' => 'upgrade_3_0_2',
		'3.0.3' => 'upgrade_3_0_3',
	);
	/**
	 * Setup this database table.
	 *
	 * @since 3.0.0
	 */
	protected function set_schema() {
		$this->schema = '
			id              bigint(20)   NOT NULL AUTO_INCREMENT,
			name            mediumtext   NOT NULL,
			status          varchar(20)  NOT NULL,
			attributes      longtext     NOT NULL,
			tokens          text         NOT NULL,
			context         varchar(250) NOT NULL,
			css             longtext     NOT NULL,
			date_created    datetime     NOT NULL default CURRENT_TIMESTAMP,
			date_modified   datetime     NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
			';
	}

	/**
	 * Add context and integration columns to table.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_1() {
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `context` varchar(250) NOT NULL;"
		);
		return $this->is_success( $result );
	}
	/**
	 * Add tokens column to table.
	 *
	 * @return bool
	 */
	public function upgrade_3_0_2() {
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} ADD COLUMN `tokens` text NOT NULL;"
		);
		return $this->is_success( $result );
	}
	/**
	 * Re-run the 3.0.2 upgrade for users where it didn't fire.
	 *
	 * Checks if tokens column already exists before attempting to add it.
	 *
	 * @since 3.0.3
	 * @return bool
	 */
	public function upgrade_3_0_3() {
		// Check if tokens column already exists.
		$column = $this->get_db()->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'tokens'" );

		if ( ! empty( $column ) ) {
			return true;
		}

		// Column doesn't exist, run the same upgrade as 3.0.2.
		return $this->upgrade_3_0_2();
	}
	/**
	 * Drop the table.
	 *
	 * @return bool
	 */
	public function drop() {
		// Drop the table.
		$dropped = parent::drop();

		// Clear the data_store cache when the table is dropped.
		Data_Store::flush( 'style' );
		return $dropped;
	}
}
