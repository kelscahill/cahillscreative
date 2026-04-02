<?php
/**
 * ACF Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\Settings as Integrations_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Acf integration functionality
 * Add options to admin, integrate with frontend queries
 */
class Relevanssi {

	/**
	 * Flag to disable Relevanssi.
	 *
	 * @var array
	 */
	private static $enabled_relevanssi_queries = array();

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 10 );

		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_relevanssi_data_type' ), 10, 1 );
		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'update_field_setting_support' ), 10, 3 );
	}


	/**
	 * Update the ACF integration in the integrations section.
	 *
	 * @since 3.0.0
	 */
	public static function update_integration() {
		// We want to disable coming soon notice and enable the integration toggle.
		$relevanssi_integration = Integrations_Settings::get_setting( 'relevanssi' );
		if ( ! $relevanssi_integration ) {
			return;
		}

		$is_relevanssi_enabled       = self::relevanssi_enabled();
		$update_integration_settings = array(
			'isIntegrationEnabled' => $is_relevanssi_enabled,
			'isExtensionInstalled' => true,
		);
		if ( $is_relevanssi_enabled ) {
			$update_integration_settings['isIntegrationInstalled'] = true;
		}

		$relevanssi_integration->update( $update_integration_settings );

		if ( ! self::relevanssi_enabled() ) {
			return;
		}

		// By default, try to disable Relevanssi when our queries are used.

		self::setup();
	}

	/**
	 * Setup the main hooks for the ACF integration.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {

		add_filter( 'search-filter/query/query_args', array( __CLASS__, 'disable_relevanssi_query' ), 100, 2 );
		add_filter( 'search-filter/fields/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
	}

	/**
	 * Check if Relevanssi is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if Relevanssi is enabled.
	 */
	private static function relevanssi_enabled() {
		global $relevanssi_variables;
		if ( ! empty( $relevanssi_variables ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Disable Relevanssi from taking over our queries.
	 *
	 * @since 3.0.0
	 *
	 * @param array                        $args  The query arguments.
	 * @param \Search_Filter\Queries\Query $query The query object.
	 * @return array The query arguments.
	 */
	public static function disable_relevanssi_query( $args, $query ) {
		if ( in_array( $query->get_id(), self::$enabled_relevanssi_queries, true ) ) {
			return $args;
		}

		// Unhook Relevanssi free + older premium.
		remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
		remove_filter( 'the_posts', 'relevanssi_query', 99 );

		// Unhook new Relevanssi free and premium.
		remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
		remove_filter( 'posts_pre_query', 'relevanssi_query', 99 );
		return $args;
	}

	/**
	 * Add custom field data type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function add_relevanssi_data_type( array $setting ) {

		if ( $setting['name'] !== 'dataType' ) {
			return $setting;
		}

		if ( ! is_array( $setting['options'] ) ) {
			return $setting;
		}

		$setting['options'][] = array(
			'label' => __( 'Relevanssi', 'search-filter' ),
			'value' => 'relevanssi',
		);

		return $setting;
	}
	/**
	 * Get the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @param    string $input_type    The input type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function update_field_setting_support( $setting_support, $type, $input_type ) {

		// Add support pro feature support to choice fields.
		if ( $type === 'search' && $input_type === 'text' ) {
			// Add support for the relelvanssi data type.
			$setting_support = Field::add_setting_support_value(
				$setting_support,
				'dataType',
				array( 'relevanssi' => true )
			);

		}

		return $setting_support;
	}

	/**
	 * Get the WP_Query args for the search field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args  The query arguments.
	 * @param Field $field The field object.
	 * @return array The query arguments.
	 */
	public static function get_search_wp_query_args( $args, $field ) {

		if ( $field->get_attribute( 'dataType' ) !== 'relevanssi' ) {
			return $args;
		}

		if ( empty( $field->get_value() ) ) {
			return $args;
		}

		self::$enabled_relevanssi_queries[] = $field->get_query_id();
		$args['s']                          = $field->get_value();
		$args['relevanssi']                 = true;

		// When searching, relevance should be the only orderby paramater
		// otherwise it doesn't work.
		$args['orderby'] = 'relevance';
		$args['order']   = 'DESC';

		return $args;
	}
}
