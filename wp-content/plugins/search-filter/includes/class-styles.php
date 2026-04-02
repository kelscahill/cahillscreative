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
use Search_Filter\Database\Table_Manager;
use Search_Filter\Styles\Style;
use Search_Filter\Styles\Settings as Styles_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Styles\Generate;
use Search_Filter\Styles\Tokens;

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
		add_action( 'search-filter/settings/init', array( __CLASS__, 'add_default_styles' ), 1 );
		add_action( 'search-filter/record/save', array( __CLASS__, 'save_css' ), 10, 2 );

		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Register table with Table_Manager.
		add_action( 'search-filter/schema/register', array( __CLASS__, 'register_tables' ) );
	}
	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'styles', array( __CLASS__, 'get_css' ) );
	}

	/**
	 * Register and init the styles tables.
	 *
	 * @since    3.2.0
	 */
	public static function register_tables() {
		Table_Manager::register( 'styles', \Search_Filter\Database\Tables\Style_Presets::class, true );
		Table_Manager::register( 'styles_meta', \Search_Filter\Database\Tables\Styles_Meta::class, true );
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

		// Don't try to add default styles in the frontend.
		if ( ! is_admin() ) {
			return;
		}

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
		CSS_Loader::queue_regeneration();
	}


	/**
	 * Creates the CSS rules for multiple tokens objects.
	 *
	 * @param array $tokens The tokens to create the CSS rules for.
	 * @return string The CSS rules.
	 */
	private static function create_css_tokens_rules( $tokens ) {
		$css = '';
		foreach ( $tokens as $token_name => $token ) {
			$css_variable_name = '--search-filter-token-' . $token_name;
			$declaration       = Generate::create_declaration( $css_variable_name, $token['default'], $token['type'] );
			// If the declaration is empty, skip it.
			if ( $declaration !== '' ) {
				$css .= $declaration . ';';
			}
		}
		return $css;
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

		$css .= '.search-filter-base {';
		// First, create the default tokens from the default values.
		$tokens = Tokens::get();
		$css   .= self::create_css_tokens_rules( $tokens );
		$css   .= '
			--search-filter-input-selection-background-color: color-mix(in srgb, var(--search-filter-input-selected-background-color) 80%, transparent);
		';

		$variation_css = '';

		// Second, map the CSS vars to the tokens according the the settings config.
		$style_settings = Styles_Settings::get();
		foreach ( $style_settings as $setting_name => $setting ) {

			$setting_style = $setting->get_prop( 'style' );
			if ( ! $setting_style ) {
				continue;
			}
			if ( ! isset( $setting_style['variables'] ) ) {
				continue;
			}

			// Setup the setting CSS variables.
			foreach ( $setting_style['variables'] as $variable_name => $variable_config ) {
				// Setup the setting CSS variable name.
				$css_variable_name = '--search-filter-' . $variable_name;
				// If there is no value, skip it.
				if ( ! isset( $variable_config['value'] ) ) {
					continue;
				}
				// Try to get the mapped token or variable, fall back to value.
				$css_value = Generate::variable_value( $variable_config['value'] );

				if ( ! empty( $css_value ) ) {
					$variable_type = $variable_config['type'] ?? 'string';
					$declaration   = Generate::create_declaration( $css_variable_name, $css_value, $variable_type );
					if ( ! empty( $declaration ) ) {
						$css .= $declaration . ';';
					}
				}
			}

			// Now setup any variations that may have different variables.
			// Get any variation variables.
			$variations = $setting->get_prop( 'variations' );
			if ( ! empty( $variations ) ) {
				foreach ( $variations as $field_type => $input_types ) {
					foreach ( $input_types as $input_type => $variation ) {
						if ( ! isset( $variation['style'] ) ) {
							continue;
						}
						if ( ! isset( $variation['style']['variables'] ) ) {
							continue;
						}
						if ( ! is_array( $variation['style']['variables'] ) ) {
							continue;
						}

						$variation_variables = $variation['style']['variables'];
						$variation_css      .= '.search-filter-field--type-' . $field_type . '.search-filter-field--input-type-' . $input_type . ' {';

						// Setup the setting CSS variables.
						foreach ( $variation_variables as $variable_name => $variable_config ) {
							// Setup the setting CSS variable name.
							$css_variable_name = '--search-filter-' . $variable_name;
							// If there is no value, skip it.
							if ( ! isset( $variable_config['value'] ) ) {
								continue;
							}
							// Try to get the mapped token or variable, fall back to value.
							$css_value = Generate::variable_value( $variable_config['value'] );
							if ( ! empty( $css_value ) ) {
								$declaration = Generate::create_declaration( $css_variable_name, $css_value );
								if ( ! empty( $declaration ) ) {
									$variation_css .= $declaration . ';';
								}
							}
						}

						$variation_css .= "}\r\n";
					}
				}
			}
		}
		$css .= "}\r\n";
		$css .= $variation_css;

		// Finally, generate the CSS for the records.
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
