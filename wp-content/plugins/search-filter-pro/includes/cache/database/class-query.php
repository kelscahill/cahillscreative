<?php
/**
 * Cache Query Class.
 *
 * Query builder for cache table operations.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache\Database
 */

namespace Search_Filter_Pro\Cache\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cache Query Class.
 *
 * @since 3.2.0
 */
class Query extends \Search_Filter\Database\Queries\Records {

	/**
	 * Name of the database table to query.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $table_name = 'cache';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $table_alias = 'sfc';

	/**
	 * Name of class used to setup the database schema.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $table_schema = '\\Search_Filter_Pro\\Cache\\Database\\Schema';

	/**
	 * Name for a single item.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $item_name = 'cache_row';

	/**
	 * Plural version for a group of items.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $item_name_plural = 'cache_rows';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * @since 3.2.0
	 * @var mixed
	 */
	protected $item_shape = '\\Search_Filter_Pro\\Cache\\Database\\Row';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected $cache_group = 'sfpro_cache';
}
