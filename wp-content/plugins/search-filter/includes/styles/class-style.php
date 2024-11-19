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

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Sanitize;
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
	// public static $settings_class = 'Search_Filter\\Settings\\Style';
	/**
	 * Context for the styles preset, eg, "theme"
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $context = '';
	/**
	 * The CSS string for this style preset.
	 *
	 * @var string
	 */
	private $css = '';

	/**
	 * The spacing sizes used for margins and paddings.
	 *
	 * @var array
	 */
	private static $spacing_sizes = array(
		'20' => '0.44rem',
		'30' => '0.67rem',
		'40' => '1rem',
		'50' => '1.5rem',
		'60' => '2.25rem',
		'70' => '3.38rem',
		'80' => '5.06rem',
	);

	/**
	 * Overridable function for setting default attributes.
	 *
	 * @return array New default attributes.
	 */
	public function get_default_attributes() {
		// It would be good to use Processed_Settings here, but its not ready
		// until wp `init` has fired... maybe we can refactor this still.
		// For now, `generate_default_attributes` does the job but it must be
		// called later, when we need it.
		return array();
	}
	/**
	 * Sets the styles context (theme)
	 *
	 * @param string $context The context to set
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
	 * @param [type] $item Database record.
	 */
	public function load_record( $item ) {
		parent::load_record( $item );
		$this->set_context( $item->get_context() );
		$this->set_css( $item->get_css() );
	}

	public function save( $args = array() ) {
		// Update the CSS.

		$has_id = false;

		if ( $this->get_id() ) {
			$has_id = true;
			$this->regenerate_css();
		}
		$extra_args = array(
			'context' => $this->get_context(),
			'css'     => $this->get_css(),
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
		$this->css = $this->generate_css();
	}

	/**
	 * Generates the CSS for the style preset.
	 *
	 * @return string The generated CSS.
	 */
	public function generate_css() {
		$css = '';

		$attributes        = $this->get_attributes();
		$input_type_matrix = Field_Factory::get_field_input_types();
		$cleaned_matrix    = array();
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
							'value' => 'admin/field/' . $field_type,
						),
					),
				);
				// Get the settings applicable to this field type and input type.
				$processed_settings = Styles_Settings::get_processed_settings( $parsed_attributes, $args );
				// Ensure any missing settings are setup so we can generate valid CSS.
				$resolved_input_type_attributes = wp_parse_args( $attributes[ $field_type ][ $input_type ], $processed_settings->get_attributes() );

				// Get the base styles class for the ID.
				$styles_class  = '.search-filter-style--id-' . intval( $this->get_id() );
				$styles_class .= '.search-filter-style--' . sanitize_key( $field_type ) . '-' . sanitize_key( $input_type );

				$css .= $styles_class . '{';

				$css .= CSS_Loader::clean_css( self::create_attributes_css( $resolved_input_type_attributes ) );
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
							'value' => 'admin/field/' . $field_type,
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
	 * @param string $type
	 * @param string $input_type
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
	 * Get a singe attribute by type / input type combination.
	 *
	 * @param string $type
	 * @param string $input_type
	 * @param string $attribute_name
	 * @return array
	 */
	public function get_attribute_by_type( $type, $input_type, $attribute_name ) {
		if ( ! isset( $this->attributes[ $type ] ) ) {
			return false;
		}
		if ( ! isset( $this->attributes[ $type ][ $input_type ] ) ) {
			return false;
		}
		if ( ! isset( $this->attributes[ $type ][ $input_type ][ $attribute_name ] ) ) {
			return false;
		}
		return $this->attributes[ $type ][ $input_type ][ $attribute_name ];
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

		// Handle colors first:
		$mapped_css_vars = array(
			'--search-filter-label-color'                  => 'labelColor',
			'--search-filter-label-background-color'       => 'labelBackgroundColor',
			'--search-filter-description-color'            => 'descriptionColor',
			'--search-filter-description-background-color' => 'descriptionBackgroundColor',
			'--search-filter-input-color'                  => 'inputColor',
			'--search-filter-input-background-color'       => 'inputBackgroundColor',
			'--search-filter-input-border-color'           => 'inputBorderColor',
			'--search-filter-input-border-hover-color'     => 'inputBorderHoverColor',
			'--search-filter-input-border-focus-color'     => 'inputBorderFocusColor',
			'--search-filter-input-icon-color'             => 'inputIconColor',
			'--search-filter-input-clear-color'            => 'inputClearColor',
			'--search-filter-input-clear-hover-color'      => 'inputClearHoverColor',
			'--search-filter-input-selected-color'         => 'inputSelectedColor',
			'--search-filter-input-selected-background-color' => 'inputSelectedBackgroundColor',
			'--search-filter-input-selection-color'        => 'inputSelectedColor',
			'--search-filter-input-active-icon-color'      => 'inputActiveIconColor',
			'--search-filter-input-inactive-icon-color'    => 'inputInactiveIconColor',
			'--search-filter-input-interactive-color'      => 'inputInteractiveColor',
			'--search-filter-input-interactive-hover-color' => 'inputInteractiveHoverColor',
		);
		$modified_colors = array(
			// Use colormix to add transparency to the existing css variable color...
			'--search-filter-input-placeholder-color'   => array(
				'rule'       => 'color-mix(in srgb, var(--search-filter-input-color) 67%, transparent)',
				'depends_on' => '--search-filter-input-color',
			),
			'--search-filter-input-border-accent-color' => array(
				'rule'       => 'color-mix(in srgb, var(--search-filter-input-border-focus-color) 47%, transparent)',
				'depends_on' => '--search-filter-input-border-focus-color',
			),
			'--search-filter-input-selection-background-color' => array(
				'rule'       => 'color-mix(in srgb, var(--search-filter-input-selected-background-color) 80%, transparent)',
				'depends_on' => '--search-filter-input-selected-background-color',
			),
		);

		$css              = '';
		$found_color_vars = array();
		foreach ( $mapped_css_vars as $css_var => $attribute_key ) {
			if ( ! isset( $attributes[ $attribute_key ] ) ) {
				continue;
			}
			$css               .= safecss_filter_attr( $css_var . ':' . $attributes[ $attribute_key ] );
			$css               .= ';';
			$found_color_vars[] = $css_var;
		}

		foreach ( $modified_colors as $css_var => $css_config ) {
			// Don't add the modifier colors if the source var wasn't found based on the attributes.
			if ( ! in_array( $css_config['depends_on'], $found_color_vars, true ) ) {
				continue;
			}
			// safecss_filter_attr doesn't support color-mix, but considering our rule is not coming
			// from user input, it should be fine not to sanitize here. It's possible to add a custom
			// filter to allow it though - https://core.trac.wordpress.org/ticket/62353 .
			$css .= $css_var . ':' . $css_config['rule'];
			$css .= ';';
		}

		// Handle the rest of the attributes.
		$mapped_css_vars = array(
			'--search-filter-label-scale'       => 'labelScale',
			'--search-filter-description-scale' => 'descriptionScale',
			'--search-filter-input-scale'       => 'inputScale',
		);

		foreach ( $mapped_css_vars as $css_var => $attribute_key ) {
			if ( isset( $attributes[ $attribute_key ] ) && $attributes[ $attribute_key ] !== '' ) {
				$css .= $css_var . ':' . sanitize_key( $attributes[ $attribute_key ] );
				$css .= ';';
			}
		}

		// Now handle the field padding and margins which are handled completely differently.
		$mapped_css_vars = array(
			'--search-filter-field-padding'       => 'fieldPadding',
			'--search-filter-field-margin'        => 'fieldMargin',
			'--search-filter-input-margin'        => 'inputMargin',
			'--search-filter-label-padding'       => 'labelPadding',
			'--search-filter-label-margin'        => 'labelMargin',
			'--search-filter-description-padding' => 'descriptionPadding',
			'--search-filter-description-margin'  => 'descriptionMargin',
		);
		foreach ( $mapped_css_vars as $css_var => $attribute_key ) {
			if ( isset( $attributes[ $attribute_key ] ) && $attributes[ $attribute_key ] !== '' ) {
				$css .= $css_var . ':' . self::get_dimension_value( $attributes[ $attribute_key ] );
				$css .= ';';
			}
		}

		// We also want to calculate the horizontal margin for input fields, so we can
		// do a CSS calc to get the correct width.
		if ( isset( $attributes['inputMargin'] ) ) {
			$css .= '--search-filter-input-margin-left:' . self::get_single_dimension_value( $attributes['inputMargin'], 'left' ) . ';';
			$css .= '--search-filter-input-margin-right:' . self::get_single_dimension_value( $attributes['inputMargin'], 'right' ) . ';';
		}

		// And add the count justification.
		if ( isset( $attributes['showCountPosition'] ) ) {
			$value = $attributes['showCountPosition'] === 'inline' ? 'flex-start' : 'space-between';
			$css  .= '--search-filter-count-justification:' . $value . ';';
		}

		$css = apply_filters( 'search-filter/styles/style/create_attributes_css', $css, $attributes );
		return $css;
	}

	public static function get_dimension_value( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		// Make sure we have all 4 values.
		$sides           = array( 'top', 'right', 'bottom', 'left' );
		$css_value       = '';
		$processed_value = '';
		// Now we can go through and build the CSS value.
		foreach ( $sides as $side ) {
			if ( ! isset( $value[ $side ] ) ) {
				$processed_value = '0';
			} else {
				$side_value = $value[ $side ];
				// Get spacing CSS variable from preset value if provided.
				// TODO - we need to know if the value is coming from a GB preset, or if we supplied it
				// as a default.
				if ( is_string( $side_value ) && str_contains( $side_value, 'var:preset|spacing|' ) ) {
					$index_to_splice = strrpos( $side_value, '|' ) + 1;
					$source_slug     = substr( $side_value, $index_to_splice );

					// if the source slug starts with `search-filter-`, then we need to remove it.
					if ( strpos( $source_slug, 'search-filter-' ) === 0 ) {
						$spacing_size = substr( $source_slug, strlen( 'search-filter-' ) );
						if ( isset( self::$spacing_sizes[ $spacing_size ] ) ) {
							$processed_value = self::$spacing_sizes[ $spacing_size ];
						}
					} else {
						$slug            = \_wp_to_kebab_case( $source_slug );
						$processed_value = "var(--wp--preset--spacing--$slug)";
					}
				} else {
					$processed_value = $side_value;
				}
			}
			$css_value .= "$processed_value ";
		}
		return trim( $css_value );
	}
	public static function get_single_dimension_value( $value, $side ) {
		if ( ! is_array( $value ) ) {
			return '0px';
		}
		// Make sure we have all 4 values.
		$sides           = array( 'right', 'left' );
		$css_value       = '';
		$processed_value = '0px';
		// Now we can go through and build the CSS value.
		if ( ! isset( $value[ $side ] ) ) {
			$processed_value = '0px';
		} else {
			$side_value = $value[ $side ];

			// Get spacing CSS variable from preset value if provided.
			// TODO - we need to know if the value is coming from a GB preset, or if we supplied it
			// as a default.
			if ( is_string( $side_value ) && str_contains( $side_value, 'var:preset|spacing|' ) ) {
				$index_to_splice = strrpos( $side_value, '|' ) + 1;
				$source_slug     = substr( $side_value, $index_to_splice );

				// if the source slug starts with `search-filter-`, then we need to remove it.
				if ( strpos( $source_slug, 'search-filter-' ) === 0 ) {
					$spacing_size = substr( $source_slug, strlen( 'search-filter-' ) );
					if ( isset( self::$spacing_sizes[ $spacing_size ] ) ) {
						$processed_value = self::$spacing_sizes[ $spacing_size ];
					}
				} else {
					$slug            = \_wp_to_kebab_case( $source_slug );
					$processed_value = "var(--wp--preset--spacing--$slug)";
				}
			} elseif ( intval( $side_value ) === 0 ) {
					$processed_value = '0px';
			} else {
				$processed_value = $side_value;
			}
		}
		$css_value .= "$processed_value";
		return trim( $css_value );
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
