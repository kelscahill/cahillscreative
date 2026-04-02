<?php
/**
 * Query Row Class.
 *
 * @package     Database
 * @subpackage  Rows
 * @copyright   Copyright (c) 2020
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0.0
 */

namespace Search_Filter\Database\Rows;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query row class.
 *
 * @since 3.0.0
 */
class Query extends \Search_Filter\Database\Engine\Row {
	/**
	 * The ID of the query.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $id = 0;

	/**
	 * The name of the query.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $name = '';

	/**
	 * The attributes of the query.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	public $attributes;

	/**
	 * The status of the query.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $status = '';

	/**
	 * The CSS of the query.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $css = '';

	/**
	 * The context of the query.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $context = '';

	/**
	 * The integration type of the query.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $integration = '';

	/**
	 * The date the query was created.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $date_created = 0;

	/**
	 * The date the query was modified.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $date_modified = 0;

	/**
	 * Queries constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The query item data.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id         = (int) $this->id;
		$this->name       = (string) $this->name;
		$this->attributes = is_string( $this->attributes ) ? json_decode( $this->attributes, true ) : $this->attributes;
		// Need to check the property exists, as it may not be set when accessing the DB on the frontend.
		// DB upgrade routines only run on the admin side, so we need to check for these properties.
		$this->context     = (string) $this->context;
		$this->integration = (string) $this->integration;

		$this->date_created  = empty( $this->date_created ) ? 0 : strtotime( (string) $this->date_created );
		$this->date_modified = empty( $this->date_modified ) ? 0 : strtotime( (string) $this->date_modified );

		Data_Store::set( 'query', $this->id, $this );
	}

	/**
	 * Retrieves the HTML to display the information about this book.
	 *
	 * @since 3.0.0
	 *
	 * @return string HTML output to display this record's data.
	 */
	public function display() {
		return '';
	}
	/**
	 * Get for the ID.
	 *
	 * @return int The ID of the query.
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Get the Name.
	 *
	 * @return string The name of the query.
	 */
	public function get_name() {
		return $this->name;
	}
	/**
	 * Get the attributes.
	 *
	 * @return array Assoc array of the attributes.
	 */
	public function get_attributes() {
		$attributes = (array) $this->attributes;
		return $attributes;
	}


	/**
	 * Get an attribute by name.
	 *
	 * @param string $attribute_name   The attribute name.
	 * @return mixed The attribute value.
	 */
	public function get_attribute( $attribute_name ) {
		$attributes = (array) $this->attributes;
		if ( isset( $attributes[ $attribute_name ] ) ) {
			return $attributes[ $attribute_name ];
		}
		return null;
	}

	/**
	 * Get the status.
	 *
	 * @return string The status of the query.
	 */
	public function get_status() {
		return $this->status;
	}
	/**
	 * Get the date created.
	 *
	 * @return int The date the query was created.
	 */
	public function get_date_created() {
		return $this->date_created;
	}
	/**
	 * Get the date modified.
	 *
	 * @return int The date the query was last modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * Get the context.
	 *
	 * @return string The context.
	 */
	public function get_context() {
		return $this->context;
	}
	/**
	 * Get the integration type.
	 *
	 * @return string The integration type.
	 */
	public function get_integration() {
		return $this->integration;
	}
	/**
	 * Get the generated CSS.
	 *
	 * @return string The CSS string of the styles for the query.
	 */
	public function get_css() {
		return $this->css;
	}
}
