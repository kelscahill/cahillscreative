<?php
namespace Search_Filter\Database\Rows;

use Search_Filter\Core\Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Option extends \Search_Filter\Database\Engine\Row {

	/**
	 * The ID of the option.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * The name of the option.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The value of the option.
	 *
	 * In the DB it will be a string, but it could be a JSON encoded string.
	 *
	 * @since 3.0.0
	 *
	 * @var mixed
	 */
	public $value = null;

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
		$this->id    = (int) $this->id;
		$this->name  = (string) $this->name;
		$this->value = (string) $this->value;
		Data_Store::set( 'option', $this->name, $this );
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
	public function get_value() {
		if ( $this->looks_like_json( $this->value ) ) {
			return json_decode( $this->value, true );
		}
		return $this->value;
	}

	/**
	 * Returns true, when the given parameter is a valid JSON string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value The value to check.
	 *
	 * @return bool True if the value looks like a JSON string.
	 */
	private function looks_like_json( $value ) {
		// Numeric strings are always valid JSON.
		if ( is_numeric( $value ) ) {
			return true;
		}

		// A non-string value can never be a JSON string.
		if ( ! is_string( $value ) ) {
			return false;
		}

		// Any non-numeric JSON string must be longer than 2 characters.
		if ( strlen( $value ) < 2 ) {
			return false;
		}

		// "null" is valid JSON string.
		if ( 'null' === $value ) {
			return true;
		}

		// "true" and "false" are valid JSON strings.
		if ( 'true' === $value ) {
			return true;
		}
		if ( 'false' === $value ) {
			return true;
		}

		// Any other JSON string has to be wrapped in {}, [] or "".
		if ( '{' !== $value[0] && '[' !== $value[0] && '"' !== $value[0] ) {
			return false; }

		// Verify that the trailing character matches the first character.
		$last_char = $value[ strlen( $value ) - 1 ];
		if ( '{' === $value[0] && '}' !== $last_char ) {
			return false; }
		if ( '[' === $value[0] && ']' !== $last_char ) {
			return false; }
		if ( '"' === $value[0] && '"' !== $last_char ) {
			return false; }

		// Then return true, we're not trying to verify if its valid JSON, only if it looks like JSON.
		return true;
	}
}
