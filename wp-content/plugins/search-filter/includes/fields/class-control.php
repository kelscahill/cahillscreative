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
class Control extends Field {

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {

		$url_name = parent::get_url_name();

		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );

		return $url_name;
	}
}
