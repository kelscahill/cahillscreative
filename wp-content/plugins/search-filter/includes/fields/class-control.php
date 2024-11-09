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
class Control extends Field {

	public function get_default_attributes() {
		// TODO - defaults should be set based on the fields settings.
		// We need to apply the `dependsOn`/ conditional logic to get the necessary defaults.
		// We should probably also "clean" the attributes before saving to remove settings/keys
		// that are not needed anymore by the field.
		$defaults         = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/control' );
		$defaults['type'] = 'control';
		$defaults         = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );

		return $defaults;
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
		$url_name = parent::get_url_name();

		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}
}
