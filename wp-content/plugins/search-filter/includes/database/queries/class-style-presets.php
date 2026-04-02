<?php
/**
 * Style Presets Query Class.
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
 * Query class for the style presets table.
 *
 * @since 3.0.0
 */
class Style_Presets extends \Search_Filter\Database\Queries\Records {

	/**
	 * Name of the database table to query.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_name = 'styles';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_alias = 'qs';

	/**
	 * Name of class used to setup the database schema.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	protected $table_schema = '\\Search_Filter\\Database\\Schemas\\Style_Presets';

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
	protected $item_name = 'styles_group';

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
	protected $item_name_plural = 'style_presets';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @since 3.0.0
	 * @var   mixed
	 */
	protected $item_shape = '\\Search_Filter\\Database\\Rows\\Style_Preset';


	/** Cache *****************************************************************/

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'styles';

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query The query arguments.
	 */
	public function __construct( $query = array() ) {

		if ( ! Table_Manager::has( 'styles' ) ) {
			Table_Manager::register( 'styles', \Search_Filter\Database\Tables\Style_Presets::class );
		}
		if ( ! Table_Manager::has( 'stylemeta' ) ) {
			Table_Manager::register( 'stylemeta', \Search_Filter\Database\Tables\Styles_Meta::class );
		}

		Table_Manager::use( 'styles' );
		Table_Manager::use( 'stylemeta' );
		parent::__construct( $query );
	}
}
