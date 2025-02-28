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
	 * Get the default attributes for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_default_attributes() {
		// TODO - defaults should be set based on the fields settings.
		// We need to apply the `dependsOn`/ conditional logic to get the necessary defaults.
		// We should probably also "clean" the attributes before saving to remove settings/keys
		// that are not needed anymore by the field.
		$defaults         = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/search' );
		$defaults['type'] = 'search';
		$defaults         = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );

		return $defaults;
	}

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
		$query_args = apply_filters( 'search-filter/field/search/wp_query_args', $query_args, $this );
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
		$query_args = apply_filters( 'search-filter/field/search/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}
}
