<?php
/**
 * ACF Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Core\Dependants;
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
		add_filter( 'search-filter/field/get_data_support', array( __CLASS__, 'update_field_data_support' ), 10, 3 );
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
			'isPluginEnabled'      => $is_relevanssi_enabled,
			'isExtensionInstalled' => true,
		);
		if ( $is_relevanssi_enabled ) {
			$update_integration_settings['isPluginInstalled'] = true;
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
		self::add_relevanssi_option_to_search_field();

		add_filter( 'search-filter/query/query_args', array( __CLASS__, 'disable_relevanssi_query' ), 10, 2 );
		add_filter( 'search-filter/field/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
		// TODO - add ordering option to the query to order by relevance (or just use `relevance`??)
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
	 * Add the Relevanssi option to the data type setting.
	 *
	 * @since 3.0.0
	 */
	protected static function add_relevanssi_option_to_search_field() {
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );
		if ( ! $data_type_setting ) {
			return;
		}

		$relevanssi_data_type_option = array(
			'label'     => __( 'Relevanssi', 'search-filter' ),
			'value'     => 'relevanssi',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => 'search',
					),
				),
			),
		);
		$data_type_setting->add_option( $relevanssi_data_type_option, array( 'after' => 'search' ) );
	}

	/**
	 * Disable Relevanssi from taking over our queries.
	 *
	 * @since 3.0.0
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
	 * Update the field data support for the Relevanssi integration.
	 *
	 * @since 3.0.0
	 *
	 * @param array $matrix    The matrix to update.
	 * @return array    The updated matrix.
	 */
	public static function update_field_data_support( $data_support, $type, $input_type ) {

		$supported_matrix = array(
			'search' => array( 'text', 'autocomplete' ),
		);

		if ( ! isset( $supported_matrix[ $type ] ) ) {
			return $data_support;
		}

		if ( ! in_array( $input_type, $supported_matrix[ $type ], true ) ) {
			return $data_support;
		}

		$data_support[] = array(
			'dataType' => 'relevanssi',
		);

		return $data_support;
	}
	/**
	 * Get the WP_Query args for the search field.
	 *
	 * @since 3.0.0
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
		if ( isset( $args['order'] ) ) {
			unset( $args['order'] );
		}

		return $args;
	}
}
