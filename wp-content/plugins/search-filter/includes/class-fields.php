<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields\Field;
use Search_Filter\Database\Queries\Fields as Field_Query;
use Search_Filter\Core\SVG_Loader;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Queries\Query;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Fields\Settings_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all things to do with fields
 */
class Fields {

	const SHORTCODE_TAG = 'searchandfilter';

	/**
	 * Keeps track of which fields are active on page load so we can load their data on page load
	 *
	 * @var array
	 */
	private static $active_fields = array();
	/**
	 * Keeps track of which query URLs will be needed on the page load.
	 *
	 * @var array
	 */
	private static $active_query_urls = array();
	/**
	 * Keeps track of which query IDs will be needed on the page.
	 *
	 * @var array
	 */
	private static $active_queries_ids = array();
	/**
	 * Initialize the class
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Register the shortcode.
		add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'shortcode' ) );
		add_action( 'search-filter/record/save', array( __CLASS__, 'save_css' ), 10, 2 );

		// Register settings.
		self::register_settings();
	}

	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'fields', 'Search_Filter\\Fields::get_css' );
	}
	/**
	 * Initialises and registers the settings.
	 *
	 * @since    3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Fields_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
		do_action( 'search-filter/settings/register/fields' );
	}
	/**
	 * The main `[searchandfilter]` shortcode.
	 *
	 * @since    3.0.0
	 *
	 * @param array $attributes  The supplied shortcode attributes.
	 */
	public static function shortcode( $attributes ) {

		// This allows us to override the shortcode output in the legacy plugin.
		// TODO - remove this when we remove the legacy plugin - September 2024?
		$override = apply_filters( 'search-filter/fields/shortcode/override', false, $attributes );
		if ( $override ) {
			return $override;
		}

		$defaults = array(
			'field'  => '',
			'query'  => '',
			/**
			 * Assume we're in the most likely sceanrio, a shortcode used within a rich
			 * text editor (ie after `the_content` has been applied). This will probably
			 * run esc_html on our attributes
			 */
			'decode' => in_the_loop() ? 'yes' : 'no',
		);

		$attributes = shortcode_atts( $defaults, $attributes, self::SHORTCODE_TAG );

		if ( 'yes' === $attributes['decode'] ) {
			$attributes = array_map( 'wp_specialchars_decode', $attributes );
		}

		$output = '';
		// Get the field data associated with the ID.
		// TODO - throw error if no field is passed
		// TODO - field should be identified using name or ID, not 'field' if its in get_field, we already know that.
		$conditions = array(
			'status' => 'enabled',
		);
		// Then we want to display a field.
		if ( is_numeric( $attributes['field'] ) ) {
			// Lookup by ID.
			$conditions['id'] = absint( $attributes['field'] );
		} else {
			// Search by name.
			$conditions['name'] = $attributes['field'];
			// If the query arg is passed, use that (because duplicate names are allowed).
			if ( isset( $attributes['query'] ) && '' !== $attributes['query'] ) {
				$conditions['query_id'] = absint( $attributes['query'] );
			}
		}
		$field = Field::find( $conditions );
		if ( is_wp_error( $field ) ) {
			$output = $field->get_error_message();
		} else {
			$output = $field->render( true );
		}
		return $output;
	}

	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @param array $config The field render config.
	 */
	public static function register_active_field( $config ) {
		self::$active_fields[ 'field_' . $config['uid'] ] = $config;
		SVG_Loader::enqueue_array( $config['icons'] );
	}
	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_active_fields() {
		return self::$active_fields;
	}
	/**
	 * Keep track of active queries to preload their data.
	 *
	 * TODO - we are currently collecting the query IDs when a field is rendered,
	 * but we might need to include queries that are not used in fields, ie, if the current page
	 * is a search page, or a has a query block that we integrate with.
	 *
	 * @param array $query_id The query ID.
	 */
	public static function register_active_query( $query_id ) {
		self::$active_queries_ids[] = $query_id;
	}
	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_query_urls() {
		return self::$active_query_urls;
	}

	/**
	 * Get the active query IDs.
	 */
	public static function get_active_query_ids() {
		return self::$active_queries_ids;
	}
	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_active_queries() {
		$active_queries_ids = array_unique( self::$active_queries_ids );
		$active_queries     = array();
		foreach ( $active_queries_ids as $query_id ) {
			// We only want to deal with enabled queries.
			$query = Query::find(
				array(
					'id'     => $query_id,
					'status' => 'enabled',
				)
			);

			if ( is_wp_error( $query ) ) {
				return;
			}

			$query_data                  = array(
				'id'         => $query_id,
				'attributes' => $query->get_attributes(),
				'settings'   => $query->get_render_settings(),
				'url'        => $query->get_results_url(),
				// TODO - only add this if debugging features are enabled?
				'name'       => $query->get_name(),
			);
			$active_queries[ $query_id ] = $query_data;
		}
		return $active_queries;
	}

	/**
	 * Find multiple fields by conditions
	 *
	 * TODO - we don't want to return the whole query - this doesn't match the other
	 * find() functions across our other apis.
	 *
	 * @param array $conditions Column name => value pairs.
	 * @param bool  $return Return the query, object, or record.
	 *
	 * @return array
	 */
	public static function find( $conditions, $return = 'objects' ) {
		$query_args = array(
			'number'  => 10,
			'orderby' => 'date_published',
			'order'   => 'asc',
		);
		$query_args = wp_parse_args( $conditions, $query_args );
		/**
		 * TODO - we probably want to wrap this in our settings API
		 * so we never call the same field twice (maybe we need to
		 * update the API to support searching for fields without
		 * query ID)
		 */
		$query = new Field_Query( $query_args );
		if ( $return === 'query' ) {
			return $query;
		}

		$fields = array();
		if ( $query ) {
			if ( $return === 'objects' ) {
				foreach ( $query->items as $record ) {
					try {
						$fields[] = Field_Factory::create_from_record( $record );
					} catch ( \Exception $e ) {
						$fields[] = new \WP_Error( 'invalid_field', $e->getMessage(), array( 'status' => 400 ) );
					}
				}
			} elseif ( $return === 'records' ) {
				$fields = $query->items;
			}
		}
		return $fields;
	}

	/**
	 * Gets all styles records.
	 */
	public static function get() {
		return self::find( array( 'number' => 0 ) );
	}
	/**
	 * Finds the count of fields.
	 *
	 * @param array $args The arguments to pass to the query.
	 *
	 * @return int The number of fields found.
	 */
	public static function find_count( $args ) {
		// Grab db instance.
		$query_args = array(
			'number' => 0,
			'count'  => true,
		);
		$query_args = wp_parse_args( $args, $query_args );
		$query      = new Field_Query( $query_args );
		return $query->found_items;
	}

	/**
	 * When saving a field, rebuild the CSS file.
	 *
	 * @param Field $field The ID of the styles preset to save.
	 * @param int   $section The section of the styles preset to save.
	 */
	public static function save_css( $field, $section ) {
		if ( $section !== 'field' ) {
			return;
		}
		CSS_Loader::save_css( 'fields' );
	}
	/**
	 * Loop through styles presets, and build their CSS.
	 *
	 * @since 3.0.0
	 *
	 * @return string The generated CSS.
	 */
	public static function get_css() {
		$css = '';
		// Loop through fields, and build their CSS.
		$all_records = self::find(
			array(
				'number' => 0,
				'status' => 'enabled',
			),
			'records'
		);

		if ( count( $all_records ) > 0 ) {
			foreach ( $all_records as $record ) {
				$record_css = self::get_record_css( $record );
				if ( $record_css !== '' ) {
					$css .= $record_css . "\r\n";
				}
			}
		}
		return $css;
	}

	/**
	 * Generates or fetches the CSS for a single style preset.
	 *
	 * @since   3.0.0
	 *
	 * @param Record $record Associative array of style group data.
	 * @return string The generated CSS.
	 */
	public static function get_record_css( $record ) {
		$css  = '';
		$name = $record->get_name();
		// Use cached version.
		$cached_css = $record->get_css();
		if ( $cached_css !== '' ) {
			$css .= '/* Field: ' . esc_html( $name ) . " */\r\n";
			$css .= CSS_Loader::clean_css( $cached_css );
		}
		return $css;
	}
}