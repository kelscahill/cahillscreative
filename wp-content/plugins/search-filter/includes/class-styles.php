<?php
/**
 * Handles styles presets
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Database\Queries\Style_Presets as Style_Presets_Query;
use Search_Filter\Styles\Style;
use Search_Filter\Styles\Settings as Styles_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all things to do with styles presets
 */
class Styles {

	/**
	 * Gets all styles records.
	 */
	public static function get() {
		return self::find( array( 'number' => 0 ) );
	}
	/**
	 * Find multiple styles by conditions
	 *
	 * @param array $args Column name => value pairs.
	 * @param bool  $return_record Whether to return the object or the record.
	 *
	 * @return array
	 */
	public static function find( $args = array(), $return_record = false ) {

		// Grab db instance.
		$query_args = array(
			'number'  => 10,
			'orderby' => 'date_published',
			'order'   => 'asc',
		);

		$query_args = wp_parse_args( $args, $query_args );

		// TODO - store a reference to the query with these args for re-using.
		$query  = new Style_Presets_Query( $query_args );
		$styles = array();
		if ( $query ) {
			if ( ! $return_record ) {
				foreach ( $query->items as $record ) {
					try {
						$styles[] = Style::create_from_record( $record );
					} catch ( \Exception $e ) {
						$styles[] = new \WP_Error( 'invalid_style', $e->getMessage(), array( 'status' => 400 ) );
					}
				}
			} else {
				$styles = $query->items;
			}
		}
		return $styles;
	}
	/**
	 * Finds the count of styles presets.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Column name => value pairs.
	 *
	 * @return int
	 */
	public static function find_count( $args ) {
		// Grab db instance.
		$query_args = array(
			'number' => 0,
			'count'  => true,
		);
		$query_args = wp_parse_args( $args, $query_args );
		$query      = new Style_Presets_Query( $query_args );
		return $query->found_items;
	}

	/**
	 * Updates a styles preset in the database.
	 *
	 * @param int   $id The ID of the styles preset to update.
	 * @param array $record The data to update.
	 */
	public static function update( $id, $record ) {
		$query = new Style_Presets_Query(
			array(
				'number' => 1,
				'id'     => $id,
				'fields' => 'ids',
			)
		);

		// If there is a result then we can update.
		if ( ! empty( $query->items ) ) {
			$result = $query->update_item( $id, $record );
		} else {
			// Something went wrong, we're trying to update an ID that doesn't exist.
			$action = 'error';
		}
	}

	/**
	 * Initialise styles.
	 *
	 * Add default styles if none exist, attach to the CSS_Loader.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'search-filter/settings/init', 'Search_Filter\\Styles::add_default_styles', 1 );
		add_action( 'search-filter/record/save', 'Search_Filter\\Styles::save_css', 10, 2 );

		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );
	}
	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'styles', 'Search_Filter\\Styles::get_css' );
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Styles_Settings::init( Fields_Settings::get_settings_by_tab( 'styles', 'arrays' ), Fields_Settings::get_groups() );
	}
	/**
	 * Adds a default styles preset to the database.
	 *
	 * @return void
	 */
	public static function add_default_styles() {
		$styles = self::find(
			array(
				'number'        => 1,
				'no_found_rows' => true,
				'status'        => array( 'enabled' ),
			)
		);
		if ( count( $styles ) > 0 ) {
			return;
		}

		$default_styles = array(
			'name'       => __( 'New Styles', 'search-filter' ),
			'status'     => 'enabled',
			'attributes' => Style::generate_default_attributes(),
		);

		$new_style = new Style();
		$new_style->set_name( $default_styles['name'] );
		$new_style->set_status( $default_styles['status'] );
		$new_style->set_attributes( $default_styles['attributes'] );
		$new_style->save();
		$new_id = $new_style->get_id();
		self::set_default_styles_id( $new_id );

		self::save_css( $new_id, 'style' );
	}

	/**
	 * Get the default styles ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public static function get_default_styles_id() {
		return get_option( 'search_filter_default_styles', 0 );
	}

	/**
	 * Set the default styles ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id The ID of the styles preset to set as default.
	 */
	public static function set_default_styles_id( $id ) {
		update_option( 'search_filter_default_styles', $id, false );
	}

	/**
	 * When saving a style preset, rebuild the CSS file.
	 *
	 * @param Style $style The styles record instance.
	 * @param int   $section The section of the styles preset to save.
	 */
	public static function save_css( $style, $section ) {
		if ( $section !== 'style' ) {
			return;
		}
		CSS_Loader::save_css( 'styles' );
	}

	/**
	 * Loop through styles presets, and build their CSS.
	 *
	 * @since 3.0.0
	 */
	public static function get_css() {
		$css = '';
		// Loop through fields, and build their CSS.
		$all_records = self::find(
			array(
				'number' => 0,
				'status' => 'enabled',
			)
		);

		if ( count( $all_records ) > 0 ) {
			foreach ( $all_records as $record ) {
				$css .= self::get_record_css( $record ) . "\r\n";
			}
		}
		return $css;
	}
	/**
	 * Generates or fetches the CSS for a single style preset.
	 *
	 * @since   3.0.0
	 *
	 * @param Style $record The styles record instance.
	 * @return string The generated CSS.
	 */
	public static function get_record_css( $record ) {
		$css  = '';
		$name = $record->get_name();
		// Use cached version.
		$cached_css = $record->get_css();
		if ( $cached_css !== '' ) {
			$css .= '/* Style group: ' . esc_html( $name ) . " */\r\n";
			$css .= CSS_Loader::clean_css( $cached_css );
		}
		return $css;
	}
}
