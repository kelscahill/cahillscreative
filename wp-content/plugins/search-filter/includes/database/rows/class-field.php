<?php
namespace Search_Filter\Database\Rows;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Field extends \Search_Filter\Database\Engine\Row {
	public $id   = 0;
	public $name = '';
	public $attributes;
	public $status        = '';
	public $context       = '';
	public $context_path  = '';
	public $query_id      = 0;
	public $css           = '';
	public $date_created  = false;
	public $date_modified = false;
	/**
	 * Fields constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id           = (int) $this->id;
		$this->name         = (string) $this->name;
		$this->status       = (string) $this->status;
		$this->attributes   = json_decode( $this->attributes, true );
		$this->query_id     = (int) $this->query_id;
		$this->context      = (string) $this->context;
		$this->context_path = (string) $this->context_path;

		$this->date_created  = false === $this->date_created ? 0 : strtotime( $this->date_created );
		$this->date_modified = false === $this->date_modified ? 0 : strtotime( $this->date_modified );

		Data_Store::set( 'field', $this->id, $this );
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
	 * Get the Name.
	 *
	 * @return string The name of the field.
	 */
	public function get_name() {
		return $this->name;
	}
	/**
	 * Get the status.
	 *
	 * @return string The status of the field.
	 */
	public function get_status() {
		return $this->status;
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
	 * Get the context.
	 *
	 * @return string The name of the field.
	 */
	public function get_context() {
		return $this->context;
	}
	/**
	 * Get the contet path.
	 *
	 * @return string The context path.
	 */
	public function get_context_path() {
		return $this->context_path;
	}
	/**
	 * Get the generated CSS.
	 *
	 * @return string The CSS string of the styles.
	 */
	public function get_css() {
		return $this->css;
	}
	/**
	 * Get the date created.
	 *
	 * @return int The date the field was created.
	 */
	public function get_date_created() {
		return $this->date_created;
	}
	/**
	 * Get the date modified.
	 *
	 * @return int The date the field was last modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}
}
