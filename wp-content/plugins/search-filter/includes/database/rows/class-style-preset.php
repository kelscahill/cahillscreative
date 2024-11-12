<?php
namespace Search_Filter\Database\Rows;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Style_Preset extends \Search_Filter\Database\Engine\Row {
	public $id   = 0;
	public $name = '';
	public $attributes;
	public $context       = '';
	public $css           = '';
	public $status        = '';
	public $date_created  = false;
	public $date_modified = false;
	/**
	 * Styles Preset constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id         = (int) $this->id;
		$this->name       = (string) $this->name;
		$this->attributes = json_decode( $this->attributes, true );
		$this->context    = (string) $this->context;

		$this->date_created  = false === $this->date_created ? 0 : strtotime( $this->date_created );
		$this->date_modified = false === $this->date_modified ? 0 : strtotime( $this->date_modified );

		Data_Store::set( 'style', $this->id, $this );
	}

	/**
	 * Retrieves the HTML to display the information about this book.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML output to display this record's data.
	 */
	public function display() {
		return '';
	}
	/**
	 * Get for the ID.
	 *
	 * @return int The ID of the styles preset.
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Get the Name.
	 *
	 * @return string The name of the style group.
	 */
	public function get_name() {
		return $this->name;
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
	 * Get the attributes.
	 *
	 * @return array Assoc array of the attributes.
	 */
	public function get_attributes() {
		$attributes       = (array) $this->attributes;
		$attributes['id'] = $this->id;
		return $attributes;
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
	 * Get the status.
	 *
	 * @return string The status of the styles preset.
	 */
	public function get_status() {
		return $this->status;
	}
	/**
	 * Get the date created.
	 *
	 * @return int The date the styles preset was created.
	 */
	public function get_date_created() {
		return $this->date_created;
	}
	/**
	 * Get the date modified.
	 *
	 * @return int The date the styles preset was last modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}
}
