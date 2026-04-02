<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields\Features;

use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Url_Arg {
	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		add_action( 'search-filter/settings/init', array( __CLASS__, 'register_url_arg_setting' ), 20 );
		add_filter( 'search-filter/fields/field/url_name', array( __CLASS__, 'add_url_arg_name' ), 20, 2 );
	}

	/**
	 * Register the URL arg setting.
	 *
	 * @since 3.0.0
	 */
	public static function register_url_arg_setting() {
		$setting = array(
			'name'        => 'dataUrlName',
			'label'       => __( 'URL Name', 'search-filter-pro' ),
			'help'        => __( 'Must only use characters a-z, underscores or hyphens.', 'search-filter-pro' ),
			'group'       => 'data',
			'tab'         => 'settings',
			'type'        => 'string',
			'default'     => '',
			'inputType'   => 'Text',
			'regex'       => '/[^0-9A-Za-z_/-]/gi',
			'context'     => array( 'admin/field', 'block/field' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter' ),
		);

		Fields_Settings::add_setting( $setting );
	}

	/**
	 * Support custom url names for fields.
	 *
	 * @param string $url_name    The URL name to add.
	 * @param Field  $field       The field to add the URL name to.
	 * @return string    The URL name.
	 */
	public static function add_url_arg_name( $url_name, $field ) {
		$url_name_attribute = $field->get_attribute( 'dataUrlName' );
		if ( ! $url_name_attribute ) {
			return $url_name;
		}
		return $url_name_attribute;
	}
}
