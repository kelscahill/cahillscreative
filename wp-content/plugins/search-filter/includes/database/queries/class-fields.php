<?php
/**
 * Fields Query Class.
 *
 * @package     Database
 * @subpackage  Queries
 * @copyright   Copyright (c) 2020
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0.0
 */

namespace Search_Filter\Database\Queries;

use Search_Filter\Database\Table_Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query class for the fields table.
 *
 * @since 3.0.0
 */
class Fields extends \Search_Filter\Database\Queries\Records {

	/**
	 * Name of the database table to query.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_name = 'fields';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_alias = 'qf';

	/**
	 * Name of class used to setup the database schema.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_schema = '\\Search_Filter\\Database\\Schemas\\Fields';

	/**
	 * Name for a single item.
	 *
	 * Use underscores between words. I.E. "term_relationship"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $item_name = 'field';

	/**
	 * Plural version for a group of items.
	 *
	 * Use underscores between words. I.E. "term_relationships"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $item_name_plural = 'fields';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $item_shape = '\\Search_Filter\\Database\\Rows\\Field';


	/** Cache *****************************************************************/

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'fields';

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query The query arguments.
	 */
	public function __construct( $query = array() ) {

		if ( ! Table_Manager::has( 'fields' ) ) {
			Table_Manager::register( 'fields', \Search_Filter\Database\Tables\Fields::class );
		}
		if ( ! Table_Manager::has( 'fieldmeta' ) ) {
			Table_Manager::register( 'fieldmeta', \Search_Filter\Database\Tables\Fields_Meta::class );
		}

		Table_Manager::use( 'fields' );
		Table_Manager::use( 'fieldmeta' );

		parent::__construct( $query );
	}
}
