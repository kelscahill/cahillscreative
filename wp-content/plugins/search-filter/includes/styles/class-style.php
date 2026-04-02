<?php
/**
 * Class for handling the frontend display of a field.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Styles;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Record_Base;
use Search_Filter\Styles\Settings as Styles_Settings;


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The base field for all styles.
 */
class Style extends Record_Base {
	/**
	 * The record store name
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	public static $record_store = 'style';

	/**
	 * The meta type for the meta table.
	 *
	 * @var string
	 */
	public static $meta_table = 'search_filter_style';

	/**
	 * The full string of the class name of the query class for this section.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $records_class    The string class name.
	 */
	public static $records_class = 'Search_Filter\\Database\\Queries\\Style_Presets';

	/**
	 * The class name to handle interacting with the record stores.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $base_class    ID
	 */
	public static $base_class = 'Search_Filter\\Styles';

	/**
	 * Usually we only want to instantiate & lookup a record once,
	 * so store the instance for easy re-use later.
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Context for the styles preset, eg, "theme"
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $context    Context
	 */
	protected $context = '';

	/**
	 * Tokens for the styles.
	 *
	 * @since    3.2.0
	 * @access   protected
	 * @var      array    $tokens    Tokens
	 */
	protected $tokens = array();
	/**
	 * The CSS string for this style preset.
	 *
	 * @var string
	 */
	private $css = '';

	/**
	 * Sets the styles context (theme)
	 *
	 * @param string $context The context to set.
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}

	/**
	 * Gets the styles context
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * Sets the styles tokens
	 *
	 * @param array $tokens The tokens to set.
	 */
	public function set_tokens( $tokens ) {
		$this->tokens = $tokens;
	}

	/**
	 * Gets the styles tokens
	 *
	 * @return array The tokens
	 */
	public function get_tokens() {
		// Populate with the default tokens if they don't exist yet.
		$default_tokens = Tokens::get_defaults();
		return wp_parse_args( $this->tokens, $default_tokens );
	}

	/**
	 * Get the css of the style preset.
	 */
	public function get_css() {
		return $this->css;
	}
	/**
	 * Set the css of the style preset.
	 *
	 * @param string $css The css to set.
	 */
	public function set_css( $css ) {
		$this->css = $css;
	}

	/**
	 * Inits the data from a DB record.
	 *
	 * @param \Search_Filter\Database\Rows\Style_Preset $item Database record.
	 */
	public function load_record( $item ) {
		$this->set_id( $item->get_id() );
		$this->set_status( $item->get_status() );
		$this->set_name( $item->get_name() );
		$this->set_record( $item );
		$this->set_attributes( $item->get_attributes() );
		$this->set_tokens( $item->get_tokens() );
		$this->set_date_modified( $item->get_date_modified() );
		$this->set_date_created( $item->get_date_created() );
		$this->set_context( $item->get_context() );
		$this->set_css( $item->get_css() );

		$this->init();
	}

	/**
	 * Save the style.
	 *
	 * @param array $args The arguments to save the style with.
	 * @return int The ID of the saved style.
	 */
	public function save( array $args = array() ) {
		// Update the CSS.

		$has_id = false;

		if ( $this->get_id() ) {
			$has_id = true;
			$this->regenerate_css();
		}
		$extra_args = array(
			'context' => $this->get_context(),
			'css'     => $this->get_css(),
			'tokens'  => wp_json_encode( (object) $this->get_tokens() ),
		);
		$record_id  = parent::save( $extra_args );

		if ( ! $has_id && $record_id ) {
			// If we don't have an ID, then we need to update the ID in the Data_Store.
			$this->regenerate_css();
			$extra_args['css'] = $this->get_css();
			parent::save( $extra_args );
		}

		// TODO - are we clearing our Data_Store once the info has been updated?
		// Or shall we update it rather than clear it?
		return $record_id;
	}

	/**
	 * Regenerates the CSS for the style preset.
	 *
	 * @since   3.0.0
	 */
	public function regenerate_css() {

		// Prevent updating the CSS until the user has opted-in to version 2.
		$assets_version = Asset_Loader::get_db_version();
		if ( $assets_version !== 2 ) {
			return;
		}

		$this->css = $this->generate_css();
	}

	/**
	 * Generates the CSS for the style preset.
	 *
	 * @return string The generated CSS.
	 */
	public function generate_css() {
		$css = '';

		$base_styles_class = '.search-filter-style--id-' . intval( $this->get_id() );

		$css .= $base_styles_class . '{';

		// Setup the styles tokens.
		$styles_tokens = Tokens::get();
		$preset_tokens = $this->get_tokens();
		foreach ( $styles_tokens as $token_name => $token ) {

			$default_value = isset( $token['default'] ) ? $token['default'] : '';
			$value         = isset( $preset_tokens[ $token_name ] ) ? $preset_tokens[ $token_name ] : $default_value;

			if ( ! isset( $token['type'] ) ) {
				continue;
			}

			$css_variable_name = '--search-filter-token-' . $token_name;

			// Try to create the rule.
			$declaration = Generate::create_declaration( $css_variable_name, $value, $token['type'] );
			if ( empty( $declaration ) ) {
				continue;
			}
			$css .= $declaration . ';';
		}
		$css .= '}';

		$attributes        = $this->get_attributes();
		$input_type_matrix = Field_Factory::get_field_input_types();

		foreach ( $input_type_matrix as $field_type => $input_types ) {
			$input_types_keys = array_keys( $input_types );

			if ( ! isset( $attributes[ $field_type ] ) ) {
				$attributes[ $field_type ] = array();
			}
			foreach ( $input_types_keys as $input_type ) {
				if ( ! isset( $attributes[ $field_type ][ $input_type ] ) ) {
					$attributes[ $field_type ][ $input_type ] = array();
				}
				$parsed_attributes = array(
					'type'            => $field_type,
					'showLabel'       => 'yes',
					'showDescription' => 'yes',
					'stylesId'        => '',
				);
				if ( $field_type === 'control' ) {
					$parsed_attributes['controlType'] = $input_type;
				} else {
					$parsed_attributes['inputType'] = $input_type;
				}

				// Get the settings applicable to this field type and input type.
				$args = array(
					'ghost_state' => array_keys( $parsed_attributes ),
					'filters'     => array(
						array(
							'type'  => 'context',
							'value' => 'admin/field',
						),
					),
				);
				// Get the settings applicable to this field type and input type.
				$processed_settings = Styles_Settings::get_processed_settings( $parsed_attributes, $args );
				// Ensure any missing settings are setup so we can generate valid CSS.
				$resolved_input_type_attributes = wp_parse_args( $attributes[ $field_type ][ $input_type ], $processed_settings->get_attributes() );

				$attributes_css = self::create_attributes_css( $resolved_input_type_attributes );
				if ( empty( $attributes_css ) ) {
					continue;
				}
				// Get the base styles class for the ID.
				$styles_class = $base_styles_class . '.search-filter-style--' . sanitize_key( $field_type ) . '-' . sanitize_key( $input_type );

				$css .= $styles_class . '{';
				$css .= CSS_Loader::clean_css( $attributes_css );
				$css .= '}';
			}
		}

		return $css;
	}

	/**
	 * Generates the default attributes for the style preset.
	 *
	 * @return array The default attributes.
	 */
	public static function generate_default_attributes() {
		$input_type_matrix  = Field_Factory::get_field_input_types();
		$default_attributes = array();
		foreach ( $input_type_matrix as $field_type => $input_types ) {
			$default_attributes[ $field_type ] = array();
			$input_types_keys                  = array_keys( $input_types );
			foreach ( $input_types_keys as $input_type ) {
				$default_attributes[ $field_type ][ $input_type ] = array();
				$parsed_attributes                                = array(
					'type'            => $field_type,
					'showLabel'       => 'yes',
					'showDescription' => 'yes',
					'stylesId'        => '',
				);
				if ( $field_type === 'control' ) {
					$parsed_attributes['controlType'] = $input_type;
				} else {
					$parsed_attributes['inputType'] = $input_type;
				}

				// Get the settings applicable to this field type and input type.
				$args               = array(
					'ghost_state' => array_keys( $parsed_attributes ),
					'filters'     => array(
						array(
							'type'  => 'context',
							'value' => 'admin/field',
						),
					),
				);
				$processed_settings = Styles_Settings::get_processed_settings( $parsed_attributes, $args );
				$default_attributes[ $field_type ][ $input_type ] = $processed_settings->get_attributes();
			}
		}
		return $default_attributes;
	}

	/**
	 * Get attributes by type / input type combination.
	 *
	 * @param string $type The field type.
	 * @param string $input_type The input type.
	 * @return array
	 */
	public function get_attributes_by_type( $type, $input_type ) {
		if ( ! isset( $this->attributes[ $type ] ) ) {
			return array();
		}
		if ( ! isset( $this->attributes[ $type ][ $input_type ] ) ) {
			return array();
		}
		return $this->attributes[ $type ][ $input_type ];
	}

	/**
	 * Parses the attributes into CSS styles (variables).
	 *
	 * @since   3.0.0
	 *
	 * @param array $attributes  The saved style attributes.
	 *
	 * @return string The generated CSS.
	 */
	public static function create_attributes_css( $attributes ) {

		$style_settings = Styles_Settings::get();
		$css            = '';

		foreach ( $style_settings as $setting_name => $setting ) {

			$setting_style = $setting->get_prop( 'style' );
			if ( ! $setting_style ) {
				continue;
			}
			if ( ! isset( $setting_style['variables'] ) ) {
				continue;
			}

			$style_variables = $setting_style['variables'];
			$style_value     = isset( $setting_style['value'] ) ? $setting_style['value'] : '';

			if ( ! isset( $attributes[ $setting_name ] ) ) {
				continue;
			}

			$value = $attributes[ $setting_name ];

			if ( empty( $value ) ) {
				continue;
			}

			// Setup the setting CSS variables.
			$css .= Generate::css_setting_variables( $style_variables, $style_value, $value );
		}

		// Special case of input margins and manually setting the left and right values.
		if ( isset( $attributes['inputMargin'] ) ) {
			$css .= '--search-filter-input-margin-left:' . Generate::get_single_dimension_value( $attributes['inputMargin'], 'left' ) . ';';
			$css .= '--search-filter-input-margin-right:' . Generate::get_single_dimension_value( $attributes['inputMargin'], 'right' ) . ';';
		}

		// Add the count position styles - special cases since they're not considered style/design tokens.
		if ( isset( $attributes['showCountPosition'] ) ) {
			$is_inline = $attributes['showCountPosition'] !== 'space-between';
			$css      .= '--search-filter-input-label-display:' . ( $is_inline ? 'block' : 'flex' ) . ';';
			$css      .= '--search-filter-input-label-width:' . ( $is_inline ? 'auto' : '100%' ) . ';';
			$css      .= '--search-filter-count-justification:' . ( $is_inline ? 'unset' : 'space-between' ) . ';';
		}

		$css = apply_filters( 'search-filter/styles/style/create_attributes_css', $css, $attributes );
		return $css;
	}



	/**
	 * Gets the attributes of the style.
	 *
	 * @since 3.0.0
	 *
	 * @param boolean $unfiltered Whether to return the unfiltered attributes.
	 *
	 * @return array The attributes of the style.
	 */
	public function get_attributes( $unfiltered = false ) {
		$attributes = parent::get_attributes( $unfiltered );
		if ( ! $unfiltered ) {
			$attributes = apply_filters( 'search-filter/styles/style/get_attributes', $attributes, $this );
		}
		return $attributes;
	}

	/**
	 * Gets an attribute
	 *
	 * @param string $attribute_name   The attribute name to get.
	 * @param bool   $unfiltered       Whether to return the unfiltered attribute.
	 *
	 * @return mixed The attribute value or false if no attribute found.
	 */
	public function get_attribute( $attribute_name, $unfiltered = false ) {
		$attribute = parent::get_attribute( $attribute_name, $unfiltered );
		if ( ! $unfiltered ) {
			$attribute = apply_filters( 'search-filter/styles/style/get_attribute', $attribute, $attribute_name, $this );
		}

		return $attribute;
	}
}
