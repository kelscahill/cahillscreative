<?php
/**
 * Handles loading of theme defined S&F styles.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Styles\Style;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The theme styles class tries to load styles from the theme if they have been set.
 *
 * Currently supports 2 approaches: theme.json and theme_support.
 *
 * TODO - Pro should extend this class and add support the 3rd party plugins.
 */
class Theme_Styles {

	/**
	 * Plugin styles data coming from the theme.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected static $plugin_styles = null;

	/**
	 * `theme.json` file cache.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected static $theme_json_file_cache = array();

	/**
	 * Init.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Needs to run after i18n is loaded.
		add_action( 'init', array( __CLASS__, 'maybe_setup_theme_styles' ), 2 );
		add_action( 'after_switch_theme', 'Search_Filter\\Theme_Styles::after_theme_changes' );
	}
	public static function after_theme_changes() {
		self::disable_theme_style();
	}
	// Check if we need to setup a new record for the theme.
	public static function maybe_setup_theme_styles() {

		self::maybe_create_theme_style();
		self::check_theme_style_version();
	}

	public static function check_theme_style_version() {
		// Try to see if we need to update the theme style.

		// First lets try to grab the settings we already know about the theme from out meta db.
		$theme_style = self::get_theme_style();
		if ( ! $theme_style ) {
			return;
		}
		$theme_style_meta = Style::get_meta( $theme_style->get_id(), 'version_data', true );
		$theme_style_meta = wp_parse_args(
			$theme_style_meta,
			array(
				'json'     => array(
					'parent_version'  => 0,
					'current_version' => 0,
				),
				'supports' => array(),
			)
		);

		// Check to see if its version is higher than the last stored verion:
		$theme_versions             = self::get_theme_json_versions();
		$should_refresh_theme_style = false;

		if ( $theme_versions['parent_version'] > absint( $theme_style_meta['json']['parent_version'] ) ) {
			// We need to update the theme style.
			$should_refresh_theme_style = true;
		}

		if ( $theme_versions['current_version'] > absint( $theme_style_meta['json']['current_version'] ) ) {
			// We need to update the theme style.
			$should_refresh_theme_style = true;
		}

		// Check to see if theme_style_supports matches the current active theme supports.
		// They are nested arrays with keyed indexes.
		$theme_style_supports  = $theme_style_meta['supports'];
		$active_theme_supports = self::get_theme_supports();

		// Check they match by looping their props and array key names/indexes:
		$compare_keys = array_merge( array_keys( $active_theme_supports ), array_keys( $theme_style_supports ) );
		foreach ( $compare_keys as $key ) {
			if ( ! isset( $theme_style_supports[ $key ] ) ) {
				$should_refresh_theme_style = true;
				break;
			}
			if ( ! isset( $active_theme_supports[ $key ] ) ) {
				$should_refresh_theme_style = true;
				break;
			}
			if ( $theme_style_supports[ $key ] !== $active_theme_supports[ $key ] ) {
				$should_refresh_theme_style = true;
				break;
			}
		}

		// We should store a copy of theme_supports settings too, and check if they've changed, if
		// so, then we should refresh the theme style.

		if ( $should_refresh_theme_style === true ) {
			self::refresh_theme_style();
		}
	}
	public static function refresh_theme_style() {
		$theme_data = static::get_theme_data();
		$color_map  = array(
			'text'              => 'textColor',
			'background'        => 'backgroundColor',
			'active-text'       => 'activeTextColor',
			'active-background' => 'activeBackgroundColor',
			'label-text'        => 'labelTextColor',
			'secondary'         => 'secondaryColor',
			'primary-accent'    => 'primaryAccentColor',
			'secondary-accent'  => 'secondaryAccentColor',
			'tertiary-accent'   => 'tertiaryAccentColor',
		);

		// Map the color settings to attribute names.
		$attributes = array();
		if ( isset( $theme_data['color'] ) ) {
			$color_data = $theme_data['color'];
			$attributes = array();
			foreach ( $color_data as $key => $value ) {
				if ( ! isset( $color_map[ $key ] ) ) {
					continue;
				}
				$attributes[ $color_map[ $key ] ] = $value;
			}
		}
		$theme_style = self::get_theme_style();
		if ( ! $theme_style ) {
			return;
		}
		$theme_style->set_attributes( $attributes );
		if ( empty( $attributes ) ) {
			$theme_style->set_status( 'hidden' );
		} else {
			$theme_style->set_status( 'enabled' );
		}

		$theme_style->save();

		// Get and set current version meta.
		$version_data = array(
			'json'     => self::get_theme_json_versions(),
			'supports' => self::get_theme_supports(),
		);
		Style::update_meta( $theme_style->get_id(), 'version_data', $version_data );
	}

	public static function get_theme_supports() {
		if ( current_theme_supports( 'search-filter-styles' ) ) {
			$theme_supports_setting = get_theme_support( 'search-filter-styles' );
			if ( is_array( $theme_supports_setting ) && isset( $theme_supports_setting[0] ) ) {
				$all_supports = array();

				foreach ( $theme_supports_setting as $theme_setting ) {
					foreach ( $theme_setting as $option_type => $values ) {
						if ( ! isset( $all_supports[ $option_type ] ) ) {
							$all_supports[ $option_type ] = array();
						}
						$all_supports[ $option_type ] = array_merge( $all_supports[ $option_type ], $values );
					}
				}
				return( $all_supports );
			}
		}
		return array();
	}
	public static function maybe_create_theme_style() {
		// TODO - check if we need to setup a new record for the theme.
		// Try to fetch a styles preset with the context of 'theme'.
		$theme_style = self::get_theme_style();
		if ( $theme_style ) {
			return;
		}
		self::create_theme_style();
	}
	public static function create_theme_style() {
		$theme_data = static::get_theme_data();

		if ( $theme_data === null || ! isset( $theme_data['color'] ) ) {
			// TODO - maybe delete the style record if there is no theme data?
			return;
		}

		// Create a new record for the theme.
		$theme_style = Style::create();
		$theme_style->set_name( __( 'Theme', 'search-filter' ) );
		$theme_style->set_context( 'theme' );
		$theme_style->save();
	}

	// Remove function
	public static function disable_theme_style() {
		$theme_style = self::get_theme_style();
		if ( ! $theme_style ) {
			return;
		}
		$theme_style->set_status( 'hidden' );
		$theme_style->set_attributes( array(), true );
		$theme_style->save();

		Style::delete_meta( $theme_style->get_id(), 'version_data' );
	}

	/**
	 * Get plugin styles from theme.json.
	 *
	 * @since 3.0.0
	 * @param array $theme_json_data Theme JSON data.
	 * @return array
	 */
	private static function get_plugin_styles_from_theme_json( $theme_json_data ) {
		$plugin_slug = 'search-filter';

		if ( ! isset( $theme_json_data['styles'] ) ) {
			return array();
		}

		if ( ! isset( $theme_json_data['styles']['plugins'] ) ) {
			return array();
		}

		if ( ! isset( $theme_json_data['styles']['plugins'][ $plugin_slug ] ) ) {
			return array();
		}

		return $theme_json_data['styles']['plugins'][ $plugin_slug ];
	}

	/**
	 * Get the theme styles record.
	 *
	 * @return Style|false
	 * @since 3.0.0
	 */
	private static function get_theme_style() {
		$theme_style = Style::find(
			array(
				'context' => 'theme',
			)
		);

		if ( is_wp_error( $theme_style ) || empty( $theme_style ) ) {
			return false;
		}

		return $theme_style;
	}

	/**
	 * Adapated from WP_Theme_JSON_Resolver::get_theme_data() and simplified.
	 *
	 * Gets the plugin styles data from the theme in this order:
	 *
	 * 1. Current theme.json
	 * 2. Parent theme.json
	 * 3. theme_support
	 *
	 * This is also the order of precedence in term of which rules are applied when
	 * there are conflicts.
	 *
	 * @since 3.0.0
	 * @return array The plugin styles data.
	 */
	public static function get_theme_data() {

		$options = array();
		$options = wp_parse_args( $options, array( 'with_supports' => true ) );

		if ( static::$plugin_styles !== null ) {
			return static::$plugin_styles;
		}

		static::$plugin_styles = array();

		if ( wp_theme_has_theme_json() ) {

			$theme_json_file = static::get_file_path_from_theme( 'theme.json' );
			$wp_theme        = wp_get_theme();
			if ( '' !== $theme_json_file ) {
				$theme_json_data = static::read_json_file( $theme_json_file );
			} else {
				$theme_json_data = array();
			}

			static::$plugin_styles = static::get_plugin_styles_from_theme_json( $theme_json_data );
			// TODO - cache the plugin styles from theme.json and only update when the file changes.

			if ( $wp_theme->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_file = static::get_file_path_from_theme( 'theme.json', true );
				if ( '' !== $parent_theme_json_file ) {
					$parent_theme_json_data    = static::read_json_file( $parent_theme_json_file );
					$parent_plugin_styles_data = static::get_plugin_styles_from_theme_json( $parent_theme_json_data );
					static::$plugin_styles     = array_replace_recursive( $parent_plugin_styles_data, static::$plugin_styles );
				}
			}
		}

		if ( current_theme_supports( 'search-filter-styles' ) ) {
			$theme_supports        = self::get_theme_supports();
			static::$plugin_styles = array_replace_recursive( $theme_supports, static::$plugin_styles );
		}
		return static::$plugin_styles;
	}

	private static function get_theme_json_versions() {
		$theme_version   = 0;
		$theme_json_file = static::get_file_path_from_theme( 'theme.json' );
		if ( file_exists( $theme_json_file ) ) {
			$theme_version = filemtime( $theme_json_file );
		}

		$parent_theme_version = 0;
		if ( is_child_theme() ) {
			$parent_theme_json_file = static::get_file_path_from_theme( 'theme.json', true );
			if ( file_exists( $parent_theme_json_file ) ) {
				$parent_theme_version = filemtime( $parent_theme_json_file );
			}
		}

		return array(
			'parent_version'  => $parent_theme_version,
			'current_version' => $theme_version,
		);
	}

	/**
	 * These functions are taken from WP_Theme_JSON_Resolver class
	 */

	/**
	 * Builds the path to the given file and checks that it is readable.
	 *
	 * If it isn't, returns an empty string, otherwise returns the whole file path.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file_name Name of the file.
	 * @param bool   $template  Optional. Use template theme directory. Default false.
	 * @return string The whole file path or empty if the file doesn't exist.
	 */
	protected static function get_file_path_from_theme( $file_name, $template = false ) {
		$path      = $template ? get_template_directory() : get_stylesheet_directory();
		$candidate = $path . '/' . $file_name;

		return is_readable( $candidate ) ? $candidate : '';
	}
	/**
	 * Processes a file that adheres to the theme.json schema
	 * and returns an array with its contents, or a void array if none found.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file_path Path to file. Empty if no file.
	 * @return array Contents that adhere to the theme.json schema.
	 */
	protected static function read_json_file( $file_path ) {
		if ( $file_path ) {
			if ( array_key_exists( $file_path, static::$theme_json_file_cache ) ) {
				return static::$theme_json_file_cache[ $file_path ];
			}

			$decoded_file = wp_json_file_decode( $file_path, array( 'associative' => true ) );
			if ( is_array( $decoded_file ) ) {
				static::$theme_json_file_cache[ $file_path ] = $decoded_file;
				return static::$theme_json_file_cache[ $file_path ];
			}
		}

		return array();
	}
}
