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

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles things a field with choices with need - such
 * as a list of options.
 */
class Advanced extends Field {

	public function get_default_attributes() {
		$defaults         = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/advanced' );
		$defaults['type'] = 'advanced';
		$defaults         = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );

		return $defaults;
	}
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

	protected function create_options() {
		if ( ! $this->has_init() ) {
			return;
		}
		$this->set_options( array() );
		return;
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}

		if ( ! isset( $this->attributes['dataType'] ) ) {
			return parent::get_url_name();
		}

		if ( 'post_attribute' === $this->attributes['dataType'] ) {
			$data_source = $this->attributes['dataPostAttribute'];
			return $data_source;
		} elseif ( 'taxonomy' === $this->attributes['dataType'] ) {
			$data_source = isset( $this->attributes['dataTaxonomy'] ) ? $this->attributes['dataTaxonomy'] : '';
			return $data_source;
		}
		return parent::get_url_name();
	}



	/**
	 * Gets the WP_Query args based on the field value.
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

	private function return_apply_wp_query_args( $query_args ) {
		$query_args = \apply_filters( 'search-filter/field/advanced/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}
}
