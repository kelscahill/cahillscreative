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
 *
 * @since 3.0.0
 */
class Range extends Field {

	/**
	 * The type of field.
	 *
	 * @var string
	 */
	public static $type = 'range';

	/**
	 * Get the list of options based on data attributes
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ! parent::has_options() ) {
			$this->create_options();
		}
		return parent::get_options();
	}

	/**
	 * Create the local options array.
	 */
	public function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		do_action( 'search-filter/fields/range/create_options/start' );

		$options = array();

		// Allow overriding for custom options.
		$options = apply_filters( 'search-filter/fields/range/options', $options, $this );

		do_action( 'search-filter/fields/range/create_options/finish' );

		$this->set_options( $options );
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {

		if ( ! $this->get_attribute( 'dataType' ) ) {
			return parent::get_url_name();
		}

		if ( 'post_attribute' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataPostAttribute' );
			return $data_source;
		} elseif ( 'taxonomy' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataTaxonomy' );
			return $data_source;
		}
		return parent::get_url_name();
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {
		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only set if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		$values = $this->get_values();

		$query_values = array();
		// Now check things like data type and data source, to figure out which part of the query should be updated.

		return $this->return_apply_wp_query_args( $query_args );
	}

	/**
	 * Returns the WP_Query args after applying filters.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	private function return_apply_wp_query_args( $query_args ) {
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/range/wp_query_args', '3.2.0', 'search-filter/fields/range/wp_query_args' );
		$query_args = apply_filters( 'search-filter/field/range/wp_query_args', $query_args, $this );
		// Filter the WP_Query args.
		$query_args = apply_filters( 'search-filter/fields/range/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}
}
