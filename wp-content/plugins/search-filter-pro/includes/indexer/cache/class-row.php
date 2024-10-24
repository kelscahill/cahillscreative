<?php
/**
 * Index Row Class.
 *
 * @since 3.0.0
 *
 * @package Search_Filter_Pro\Indexer\Database
 */

namespace Search_Filter_Pro\Indexer\Cache;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Row Class.
 *
 * @since 3.0.0
 */
class Row extends \Search_Filter\Database\Engine\Row {
	/**
	 * The ID.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $id = 0;

	/**
	 * Related query ID.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $query_id = 0;

	/**
	 * Related field ID.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $field_id = 0;

	/**
	 * The type of item.
	 *
	 * @since 3.0.0
	 * @var   string
	 */
	public $type = '';
	/**
	 * The key.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $cache_key = '';
	/**
	 * The value.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $cache_value = '';

	/**
	 * The date the cache item was last modified.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $date_modified = false;

	/**
	 * The date the cache item expires.
	 *
	 * @since 3.0.0
	 * @var   int
	 */
	public $expires = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item The item to create the row from.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id            = (int) $this->id;
		$this->query_id      = (int) $this->query_id;
		$this->field_id      = (int) $this->field_id;
		$this->type          = (string) $this->type;
		$this->cache_key     = (string) $this->cache_key;
		$this->cache_value   = (string) $this->cache_value;
		$this->expires       = (int) $this->expires;
		$this->date_modified = false === $this->date_modified ? 0 : strtotime( $this->date_modified );

		Data_Store::set( 'index_cache', $this->id, $this );
	}

	/**
	 * Get for the ID.
	 *
	 * @return int The ID of the field.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the query ID.
	 *
	 * @return int The ID of the query.
	 */
	public function get_query_id() {
		return $this->query_id;
	}

	/**
	 * Get the field ID.
	 *
	 * @return int The ID of the field.
	 */
	public function get_field_id() {
		return $this->field_id;
	}

	/**
	 * Get the type of item.
	 *
	 * @return string The type of item.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the object ID.
	 *
	 * @return int The ID of the object.
	 */
	public function get_cache_key() {
		return $this->cache_key;
	}

	/**
	 * Get the object parent ID.
	 *
	 * @return int The ID of the parent object.
	 */
	public function get_cache_value() {
		return $this->cache_value;
	}

	/**
	 * Get the date modified.
	 *
	 * @return int The date the field was last modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * Get the expires date.
	 *
	 * @return int The date the field was last modified.
	 */
	public function get_expires() {
		return $this->expires;
	}
}
