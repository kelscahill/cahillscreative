<?php
/**
 * Style Preset Row Class.
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
 * Style preset row class.
 *
 * @since 3.0.0
 */
class Style_Preset extends \Search_Filter\Database\Engine\Row {
	/**
	 * The ID of the style preset.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $id = 0;

	/**
	 * The name of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $name = '';

	/**
	 * The attributes of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $attributes = '';

	/**
	 * The tokens of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $tokens = '';

	/**
	 * The context of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $context = '';

	/**
	 * The CSS of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $css = '';

	/**
	 * The status of the style preset.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $status = '';

	/**
	 * The date the style preset was created.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $date_created = 0;

	/**
	 * The date the style preset was modified.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $date_modified = 0;

	/**
	 * Styles Preset constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The style preset item data.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// This is optional, but recommended. Set the type of each column, and prepare.
		$this->id         = (int) $this->id;
		$this->name       = (string) $this->name;
		$this->attributes = json_decode( $this->attributes, true );
		$this->tokens     = json_decode( $this->tokens, true );
		$this->context    = (string) $this->context;

		$this->date_created  = empty( $this->date_created ) ? 0 : strtotime( (string) $this->date_created );
		$this->date_modified = empty( $this->date_modified ) ? 0 : strtotime( (string) $this->date_modified );

		Data_Store::set( 'style', $this->id, $this );
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
	 * Get the tokens.
	 *
	 * @return array Assoc array of the attributes.
	 */
	public function get_tokens() {
		return (array) $this->tokens;
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
