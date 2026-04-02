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

use Search_Filter\Core\Component_Loader;
use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields\Field;
use Search_Filter\Database\Rows\Field as Field_Record;
use Search_Filter\Database\Queries\Fields as Field_Query;
use Search_Filter\Core\SVG_Loader;
use Search_Filter\Database\Table_Manager;
use Search_Filter\Fields\Field_Factory;
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

	/**
	 * Keeps track of which fields are active on page load so we can load their data on page load
	 *
	 * @var array
	 */
	private static $active_fields = array();
	/**
	 * Keeps track of which fields are active on page load so we can load their data on page load
	 *
	 * @var array
	 */
	private static $active_field_configs = array();

	/**
	 * Map of short names to full table keys.
	 *
	 * @var array<string, string>
	 */
	const TABLE_KEY_MAP = array(
		'fields' => 'fields',
		'meta'   => 'fieldmeta',
	);

	/**
	 * Initialize the class
	 *
	 * @since    3.0.0
	 */
	public static function init() {

		add_action( 'search-filter/record/save', array( __CLASS__, 'save_css' ), 10, 2 );

		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// On delete post, remove the field location.
		add_action( 'delete_post', array( __CLASS__, 'remove_fields_from_post' ), 10 );

		// Register table with Table_Manager.
		add_action( 'search-filter/schema/register', array( __CLASS__, 'register_tables' ) );
	}

	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'fields', array( __CLASS__, 'get_css' ) );
	}


	/**
	 * Initialises and registers the settings.
	 *
	 * @since    3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Fields_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}

	/**
	 * Find fields by location.
	 *
	 * @since 3.0.0
	 * @param string $location The location to search for.
	 * @param string $return_as Return format (objects or array).
	 * @return array The fields found.
	 */
	public static function find_fields_by_location( $location, $return_as = 'objects' ) {
		$fields = self::find(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => array(
					array(
						'key'     => 'locations',
						'value'   => $location,
						'compare' => '=',
					),
				),
			),
			$return_as
		);
		if ( is_wp_error( $fields ) ) {
			return array();
		}
		return $fields;
	}

	/**
	 * Remove fields from a post.
	 *
	 * @since 3.0.0
	 * @param int $post_id The post ID.
	 */
	public static function remove_fields_from_post( $post_id ) {
		self::remove_fields_from_location( 'post/' . $post_id );
	}
	/**
	 * Remove fields from a specific location.
	 *
	 * @since 3.2.0
	 *
	 * @param int|string $location The location.
	 */
	public static function remove_fields_from_location( $location ) {

		$fields = self::find_fields_by_location( $location, 'records' );

		foreach ( $fields as $field_record ) {
			$field = null;
			// Try to get an existing instance if it exists.
			if ( Field::has_instance( $field_record->get_id() ) ) {
				$field = Field::get_instance( $field_record->get_id() );
			} else {
				try {
					$field = Field_Factory::create_from_record( $field_record );
					Field::set_instance( $field );
				} catch ( \Exception $e ) {
					continue;
				}
			}
			if ( $field ) {
				$field->remove_location( $location );
			}
		}
	}

	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @param Field $field The field object.
	 */
	public static function register_active_field( $field ) {
		// Make sure we don't register the same field twice.
		foreach ( self::$active_fields as $active_field ) {
			if ( $active_field->get_id() === $field->get_id() ) {
				return;
			}
		}
		self::$active_fields[] = $field;
		SVG_Loader::enqueue_array( $field->get_icons() );
		Component_Loader::enqueue_array( $field->get_components() );
	}
	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_active_fields() {
		foreach ( self::$active_fields as $field ) {
			self::$active_field_configs[ 'field_' . $field->get_id() ] = $field->get_render_data();
		}
		return self::$active_field_configs;
	}

	/**
	 * Find multiple fields by conditions
	 *
	 * TODO - we don't want to return the whole query - this doesn't match the other
	 * find() functions across our other apis.
	 *
	 * @param array  $conditions Column name => value pairs.
	 * @param string $return_as Return the query, object, or record.
	 *
	 * @return array|Field_Query
	 */
	public static function find( $conditions, $return_as = 'objects' ) {
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
		if ( $return_as === 'query' ) {
			return $query;
		}
		$fields = array();
		if ( $return_as === 'objects' ) {
			foreach ( $query->items as $record ) {
				try {
					$fields[] = Field_Factory::create_from_record( $record );
				} catch ( \Exception $e ) {
					$fields[] = new \WP_Error( 'invalid_field', $e->getMessage(), array( 'status' => 400 ) );
				}
			}
		} elseif ( $return_as === 'records' ) {
			$fields = $query->items;
		}
		return $fields;
	}

	/**
	 * Gets all fields records.
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
		CSS_Loader::queue_regeneration();
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
	 * @param Field_Record $record The field record.
	 * @return string The generated CSS.
	 */
	public static function get_record_css( Field_Record $record ) {
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

	/**
	 * Register and init the fields tables.
	 *
	 * @since    3.2.0
	 */
	public static function register_tables() {

		// Register all tables so we can uninstall them.
		if ( ! Table_Manager::has( 'fields' ) ) {
			Table_Manager::register( 'fields', \Search_Filter\Database\Tables\Fields::class );
		}
		if ( ! Table_Manager::has( 'fieldmeta' ) ) {
			Table_Manager::register( 'fieldmeta', \Search_Filter\Database\Tables\Fields_Meta::class );
		}
	}


	/**
	 * Get a field table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'fields' or 'meta'. Default 'fields'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $type = 'fields', $should_use = true ) {
		$key = self::TABLE_KEY_MAP[ $type ] ?? 'fields';
		return Table_Manager::get( $key, $should_use );
	}

	/**
	 * Get a field table name.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'fields' or 'meta'. Default 'fields'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $type = 'fields', $should_use = true ) {
		$table = self::get_table( $type, $should_use );
		return $table ? $table->get_table_name() : '';
	}
}
