<?php
/**
 * Choice Filter base class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields;

use Search_Filter\Core\Deprecations;
use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles things a field with choices with need - such
 * as a list of options.
 */
class Search extends Field {

	/**
	 * List of icons the input type supports.
	 *
	 * @var array
	 */
	public $icons = array(
		'search',
		'clear',
	);

	/**
	 * Apply the WP_Query args.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/search/wp_query_args', '3.2.0', 'search-filter/fields/search/wp_query_args' );
		$query_args = apply_filters( 'search-filter/field/search/wp_query_args', $query_args, $this );

		$query_args = apply_filters( 'search-filter/fields/search/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}


	/**
	 * Apply the query_args for regular WP queries.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @return   array    The updated WP query args.
	 */
	protected function return_apply_wp_query_args( $query_args ) {
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/search/wp_query_args', '3.2.0', 'search-filter/fields/search/wp_query_args' );
		$query_args = apply_filters( 'search-filter/field/search/wp_query_args', $query_args, $this );

		$query_args = apply_filters( 'search-filter/fields/search/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}

	/**
	 * Set the values for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $values The values to set.
	 */
	public function set_values( $values ) {
		parent::set_values( $values );

		foreach ( $values as $value ) {
			$this->value_labels[ $value ] = $value;
		}
	}
}
