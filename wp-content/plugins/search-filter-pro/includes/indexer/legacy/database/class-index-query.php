<?php
/**
 * Index Query Class.
 *
 * @since 3.2.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Legacy\Database;

use Search_Filter_Pro\Database\Table_Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Legacy Index Query Class.
 *
 * @since 3.2.0
 */
class Index_Query extends \Search_Filter\Database\Queries\Records {

	/**
	 * Name of the database table to query.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $table_name = 'index';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $table_alias = 'qli';

	/**
	 * Name of class used to setup the database schema.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $table_schema = '\\Search_Filter_Pro\\Indexer\\Legacy\\Database\\Index_Schema';

	/**
	 * Name for a single item.
	 *
	 * Use underscores between words. I.E. "term_relationship"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $item_name = 'legacy_index_row';

	/**
	 * Plural version for a group of items.
	 *
	 * Use underscores between words. I.E. "term_relationships"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $item_name_plural = 'legacy_index_rows';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @since 3.2.0
	 * @var   mixed
	 */
	protected $item_shape = '\\Search_Filter_Pro\\Indexer\\Legacy\\Database\\Index_Row';


	/** Cache *****************************************************************/

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'index';

	/**
	 * Constructor.
	 *
	 * Ensures the legacy index table is registered and ready for use.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query Optional. Query arguments.
	 */
	public function __construct( $query = array() ) {

		// Ensure legacy index table exists before using.
		if ( ! Table_Manager::has( 'index' ) ) {
			Table_Manager::register( 'index', \Search_Filter_Pro\Indexer\Legacy\Database\Index_Table::class );
		}

		Table_Manager::use( 'index' );

		parent::__construct( $query );
	}
}
