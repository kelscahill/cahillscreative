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
use Search_Filter\Core\WP_Data;
use Search_Filter\Fields\Data\Taxonomy_Options;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles things a field with choices with need - such
 * as a list of options.
 */
class Range extends Field {

	public static $type = 'range';


	public function get_default_attributes() {
		// TODO - defaults should be set based on the fields settings.
		// We need to apply the `dependsOn`/ conditional logic to get the necessary defaults.
		// We should probably also "clean" the attributes before saving to remove settings/keys
		// that are not needed anymore by the field.
		$defaults         = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/range' );
		$defaults['type'] = 'range';
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

	/**
	 * Get the list of options based on data attributes
	 *
	 * TODO - we should be able to expect some of these attributes
	 * to be set rather than checking them all.
	 *
	 * This is partly related to the create_field endpoint, it is
	 * hit while fields are being initialised and as a result the
	 * attributes are malformed.
	 *
	 * 1. We need a reliable way to init fields with the correct
	 *    attributes & sensible defaults.
	 * 2. Resolve the settings in PHP before loading the page,
	 *    use it as the default state for the JS so we don't send
	 *    the wrong data to the server when creating new fields.
	 *
	 * @return array
	 */
	protected function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		do_action( 'search-filter/fields/range/create_options/start' );

		$options = array();

		// Allow custom options.
		$options = apply_filters( 'search-filter/field/range/options', $options, $this );

		do_action( 'search-filter/fields/range/create_options/finish' );

		$this->set_options( $options );
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
		$query_args = \apply_filters( 'search-filter/field/range/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}
}
