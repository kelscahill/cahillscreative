<?php
/**
 * Cache Row Class.
 *
 * Represents a single row in the cache table.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache\Database
 */

namespace Search_Filter_Pro\Cache\Database;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Row Class.
 *
 * @since 3.2.0
 */
class Row extends \Search_Filter_Pro\Database\Engine\Row {

	/**
	 * The ID.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	public $id = 0;

	/**
	 * Cache group.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $cache_group = '';

	/**
	 * Cache key (MD5 hash).
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $cache_key = '';

	/**
	 * Cache value.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $cache_value = '';

	/**
	 * Format indicator.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	public $format = 'raw';

	/**
	 * Expires timestamp.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	public $expires = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 *
	 * @param array $item The item to create the row from.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// Set the type of each column.
		$this->id          = (int) $this->id;
		$this->cache_group = (string) $this->cache_group;
		$this->cache_key   = (string) $this->cache_key;
		$this->cache_value = (string) $this->cache_value;
		$this->format      = (string) $this->format;
		$this->expires     = (int) $this->expires;

		Data_Store::set( 'cache', $this->id, $this );
	}

	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the cache group.
	 *
	 * @return string
	 */
	public function get_cache_group() {
		return $this->cache_group;
	}

	/**
	 * Get the cache key.
	 *
	 * @return string
	 */
	public function get_cache_key() {
		return $this->cache_key;
	}

	/**
	 * Get the cache value.
	 *
	 * @return string
	 */
	public function get_cache_value() {
		return $this->cache_value;
	}

	/**
	 * Get the format.
	 *
	 * @return string
	 */
	public function get_format() {
		return $this->format;
	}

	/**
	 * Get the expires timestamp.
	 *
	 * @return int
	 */
	public function get_expires() {
		return $this->expires;
	}
}
