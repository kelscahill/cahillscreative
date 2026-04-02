<?php
/**
 * Style generation utilities.
 *
 * @package Search_Filter\Styles
 */

namespace Search_Filter\Styles;

use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates CSS from style settings.
 */
class Generate {

	/**
	 * CSS filter overrides to allow for additional CSS values.
	 *
	 * @var array
	 */
	private static $css_filter_overrides = array(
		'box-shadow' => 'safecss_filter_attr_allow_box_shadow_css',
	);

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
	 * Parses a value based on type.
	 *
	 * @param mixed  $value The value to parse.
	 * @param string $type  The type of value.
	 * @return string The parsed CSS value.
	 */
	public static function parse_value( $value, $type ) {

		// If value is already a CSS function (var, calc, etc), return as-is.
		if ( is_string( $value ) && preg_match( '/^(var|calc|min|max|clamp)\s*\(/', $value ) ) {
			return $value;
		}

		$css_value = '';
		if ( $type === 'spacing' ) {
			$css_value = self::get_dimension_value( $value );
		} elseif ( $type === 'border-radius' ) {
			$css_value = self::get_border_property_value( $value, 'radius' );
		} elseif ( $type === 'border-style' ) {
			$css_value = self::get_border_property_value( $value, 'style' );
		} elseif ( $type === 'border-width' ) {
			$css_value = self::get_border_property_value( $value, 'width' );
		} elseif ( $type === 'border-color' ) {
			$css_value = self::get_border_property_value( $value, 'color' );
		} elseif ( is_scalar( $value ) ) {
			$css_value = (string) $value;
		}

		return $css_value;
	}


	/**
	 * Checks if the value is a token.
	 *
	 * @param string $value The value to check.
	 * @return bool
	 */
	private static function is_value_token( $value ) {
		return strpos( $value, 'token:' ) === 0;
	}

	/**
	 * Gets the token name from the value.
	 *
	 * @param string $value The value to get the token name from.
	 * @return string
	 */
	private static function get_value_token( $value ) {
		return str_replace( 'token:', '', $value );
	}

	/**
	 * Checks if the value is a variable.
	 *
	 * @param string $value The value to check.
	 * @return bool
	 */
	private static function is_value_variable( $value ) {
		return strpos( $value, 'var:' ) === 0;
	}

	/**
	 * Gets the variable name from the value.
	 *
	 * @param string $value The value to get the variable name from.
	 * @return string
	 */
	private static function get_value_variable( $value ) {
		return str_replace( 'var:', '', $value );
	}



	/**
	 * Gets the CSS value from the value.
	 *
	 * @param string $value The value to get the CSS value from.
	 * @return string
	 */
	public static function variable_value( $value ) {
		if ( self::is_value_token( $value ) ) {
			return 'var(--search-filter-token-' . self::get_value_token( $value ) . ')';
		} elseif ( self::is_value_variable( $value ) ) {
			return 'var(--search-filter-' . self::get_value_variable( $value ) . ')';
		}
		return $value;
	}
	/**
	 * Create a CSS declaration.
	 *
	 * @param string $property The property name.
	 * @param string $value The value.
	 * @param string $type The type of value.
	 * @param string $spacer The spacer to use between the property and value.
	 * @return string The CSS declaration.
	 */
	public static function create_declaration( $property, $value, $type = 'string', $spacer = '' ) {

		// Parse the value if its not already a string (ie, an object coming from the database).
		$css_value = self::parse_value( $value, $type );

		// Strip all tags from the value.
		$filtered_value = wp_strip_all_tags( $css_value, true );

		if ( '' !== $filtered_value ) {
			// Add CSS filter overrides based on type if needed.
			if ( isset( self::$css_filter_overrides[ $type ] ) ) {
				add_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, self::$css_filter_overrides[ $type ] ), 10, 2 );
			}

			$css = self::safecss_filter_attr( "{$property}:{$spacer}{$filtered_value}" );

			// Remove override.
			if ( isset( self::$css_filter_overrides[ $type ] ) ) {
				remove_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, self::$css_filter_overrides[ $type ] ), 10 );
			}
			return $css;
		}
		return '';
	}

	/**
	 * Gets a mapped variable value from style and attribute values.
	 *
	 * The aim is to find out where in the attribute value the variable is located.
	 *
	 * Ie, if a style value is { top: var:input-padding-top, right: ..., bottom: ..., left: ... }
	 * and the attribute value is { top: 10px, right: 20px, bottom: 30px, left: 40px }
	 * then we need to figure out (from the style_value) which property is mapped to the variable.
	 *
	 * If the current variable name is `input-padding-top` then we need to parse the style value,
	 * figure out that the sub property is `top`, and then use the `top` property of attribute value
	 * to generate the CSS.
	 *
	 * In this case, the `$mapped_value` value should `10px` and we should pass that into
	 * create_declaration.
	 *
	 * @param string $variable_name   The variable name.
	 * @param mixed  $style_value     The style value.
	 * @param mixed  $attribute_value The attribute value.
	 * @return mixed The mapped value.
	 */
	private static function get_mapped_variable_value( $variable_name, $style_value, $attribute_value ) {
		$mapped_value = $attribute_value;
		// The style setting value determines mapping of variables to values.
		if ( empty( $style_value ) ) {
			$mapped_value = $attribute_value;
		} elseif ( is_scalar( $style_value ) ) {
			$mapped_value = $attribute_value;
		} elseif ( Util::is_assoc_array( $style_value ) ) {
			// Then we need to find the object value which matches  `var:$variable_name` in the `$style_value` object property value.
			foreach ( $style_value as $key => $value ) {
				if ( $value === "var:{$variable_name}" ) {
					// Now we've found the property where this variable is mapped to
					// its key to find the actual property value in the $setting_value.
					if ( isset( $attribute_value[ $key ] ) ) {
						$mapped_value = $attribute_value[ $key ];
					}
					break;
				}
			}
		} elseif ( is_array( $style_value ) ) {
			foreach ( $style_value as $key => $value ) {
				if ( $value === "var:{$variable_name}" ) {
					// Now we've found the property where this variable is mapped to
					// its key to find the actual property value in the $setting_value.
					if ( isset( $attribute_value[ $key ] ) ) {
						$mapped_value = $attribute_value[ $key ];
					}
					break;
				}
			}
		}
		return $mapped_value;
	}
	/**
	 * Create the CSS variables for a setting.
	 *
	 * @param array  $style_variables The variables from the settings style.
	 * @param string $style_value The value from the settings style.
	 * @param string $attribute_value The corresponding stored attribute value.
	 * @return string The CSS variables.
	 */
	public static function css_setting_variables( $style_variables, $style_value, $attribute_value = '' ) {

		$variables_output = '';

		foreach ( $style_variables as $variable_name => $variable_config ) {
			// Then the setting is mapped to a single CSS variable.
			$css_variable_name = '--search-filter-' . $variable_name;

			// Figure out which part of the value to use for creating the declaration.
			$mapped_value = self::get_mapped_variable_value( $variable_name, $style_value, $attribute_value );

			$declaration = self::create_declaration( $css_variable_name, $mapped_value, $variable_config['type'] );
			if ( ! empty( $declaration ) ) {
				$variables_output .= $declaration . ';';
			}
		}
		return $variables_output;
	}

	/**
	 * Generates a CSS rule from a selector and declarations.
	 *
	 * @param string $css_selector     The CSS selector.
	 * @param array  $css_declarations The CSS declarations.
	 * @return string The CSS rule.
	 */
	public static function css_rule( $css_selector, $css_declarations ) {

		$css_rule = new \WP_Style_Engine_CSS_Rule( $css_selector, $css_declarations );
		$css      = $css_rule->get_css( true );
		return $css;
	}

	/**
	 * Generates CSS declarations string.
	 *
	 * @param array $css_declarations The CSS declarations.
	 * @return string The CSS declarations string.
	 */
	public static function css_declarations( $css_declarations ) {

		$css_rule = new \WP_Style_Engine_CSS_Declarations( $css_declarations );
		$css      = $css_rule->get_declarations_string( true );
		return $css;
	}

	/**
	 * Filter the `safecss_filter_attr` function used when generating the CSS
	 * to allow for specific types.
	 *
	 * @param bool   $allow_css Whether the CSS is allowed.
	 * @param string $css_test_string The CSS to test.
	 * @return bool Whether the CSS is allowed.
	 */
	public static function safecss_filter_attr_allow_box_shadow_css( $allow_css, $css_test_string ) {

		// If it's already allowed, don't interfere.
		if ( $allow_css ) {
			return $allow_css;
		}

		// Extract the property value from the CSS string.
		// Match any property name (including CSS variables) followed by a colon and value.
		if ( ! preg_match( '/^\s*([^:]+)\s*:\s*(.+)$/i', $css_test_string, $matches ) ) {
			return $allow_css;
		}
		$property_value = trim( $matches[2] );

		// Remove any trailing semicolon for validation.
		$property_value = rtrim( $property_value, ';' );

		// Validate the box-shadow value.
		if ( self::validate_box_shadow_value( $property_value ) ) {
			return true;
		}

		return $allow_css;
	}

	/**
	 * Validate a box-shadow value for safety.
	 *
	 * @param string $value The box-shadow value to validate.
	 * @return bool Whether the value is safe.
	 */
	private static function validate_box_shadow_value( $value ) {
		// Handle 'none' value.
		if ( $value === 'none' ) {
			return true;
		}

		// Split multiple shadows by comma (but not commas inside parentheses).
		$shadows = preg_split( '/,(?![^()]*\))/', $value );

		foreach ( $shadows as $shadow ) {
			$shadow = trim( $shadow );

			// Validate individual shadow.
			if ( ! self::validate_single_box_shadow( $shadow ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a single box-shadow value.
	 *
	 * @param string $shadow Single shadow value.
	 * @return bool Whether the shadow is valid.
	 */
	private static function validate_single_box_shadow( $shadow ) {
		// Define allowed components.
		$length_pattern = '-?(?:\d*\.?\d+)(?:px|em|rem|%|vh|vw|vmin|vmax|ex|ch|cm|mm|in|pt|pc)';
		$number_pattern = '-?(?:\d*\.?\d+)';

		// Color patterns.
		// Allow any alphabetic string as a color keyword (future-proof for new CSS color names).
		$color_keyword_pattern = '[a-zA-Z]+';
		$hex_pattern           = '#(?:[0-9a-fA-F]{3}){1,2}(?:[0-9a-fA-F]{2})?'; // Supports #RGB, #RRGGBB, #RRGGBBAA.

		// Modern color functions with flexible patterns.
		$rgb_pattern = 'rgba?\s*\(\s*(?:' . $number_pattern . '%?)\s*[,\s]\s*(?:' . $number_pattern . '%?)\s*[,\s]\s*(?:' . $number_pattern . '%?)\s*(?:[,\/]\s*' . $number_pattern . '%?)?\s*\)';
		$hsl_pattern = 'hsla?\s*\(\s*' . $number_pattern . '(?:deg|grad|rad|turn)?\s*[,\s]\s*' . $number_pattern . '%\s*[,\s]\s*' . $number_pattern . '%\s*(?:[,\/]\s*' . $number_pattern . '%?)?\s*\)';

		// Support newer color functions too (hwb, lab, lch, oklab, oklch, color).
		$modern_color_pattern = '(?:hwb|lab|lch|oklab|oklch|color)\s*\([^)]+\)';

		// Combined color pattern.
		$color_pattern = '(?:' . $color_keyword_pattern . '|' . $hex_pattern . '|' . $rgb_pattern . '|' . $hsl_pattern . '|' . $modern_color_pattern . ')';

		// Remove 'inset' keyword if present.
		$has_inset = false;
		if ( preg_match( '/^inset\s+/i', $shadow ) ) {
			$has_inset = true;
			$shadow    = preg_replace( '/^inset\s+/i', '', $shadow );
		} elseif ( preg_match( '/\s+inset$/i', $shadow ) ) {
			$has_inset = true;
			$shadow    = preg_replace( '/\s+inset$/i', '', $shadow );
		}

		// Try to match the pattern: <offset-x> <offset-y> [blur] [spread] [color]
		// All components are optional except offset-x and offset-y.

		// Pattern for 2-4 length values followed by optional color.
		$shadow_pattern = '/^' .
			'(' . $length_pattern . ')\s+' . // offset-x (required).
			'(' . $length_pattern . ')' . // offset-y (required).
			'(?:\s+(' . $length_pattern . '))?' . // blur (optional).
			'(?:\s+(' . $length_pattern . '))?' . // spread (optional).
			'(?:\s+(' . $color_pattern . '))?' . // color (optional).
			'$/i';

		// Also allow color to come first.
		$shadow_pattern_color_first = '/^' .
			'(' . $color_pattern . ')\s+' . // color.
			'(' . $length_pattern . ')\s+' . // offset-x.
			'(' . $length_pattern . ')' . // offset-y.
			'(?:\s+(' . $length_pattern . '))?' . // blur (optional).
			'(?:\s+(' . $length_pattern . '))?' . // spread (optional).
			'$/i';

		if ( preg_match( $shadow_pattern, trim( $shadow ) ) || preg_match( $shadow_pattern_color_first, trim( $shadow ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets a CSS dimension value from an array of sides.
	 *
	 * @param array $value The dimension value array with top, right, bottom, left.
	 * @return string The CSS dimension value.
	 */
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

	/**
	 * Gets a single dimension value for a specific side.
	 *
	 * @param array  $value The dimension value array.
	 * @param string $side  The side to get (top, right, bottom, or left).
	 * @return string The CSS dimension value for the side.
	 */
	public static function get_single_dimension_value( $value, $side ) {
		if ( ! is_array( $value ) ) {
			return '0px';
		}
		// Make sure we have all 4 values.
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
	 * Gets border style values for all sides.
	 *
	 * @param array $value The border value array.
	 * @return string The CSS border style value.
	 */
	public static function get_border_style_value( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$sides          = array( 'top', 'right', 'bottom', 'left' );
		$css_value      = '';
		$values_ordered = array();

		// Loop through each side and get the style value.
		foreach ( $sides as $side ) {
			if ( ! isset( $value[ $side ] ) ) {
				return 'none';
			}
			if ( ! isset( $value[ $side ]['style'] ) ) {
				return 'none';
			}
			$values_ordered[] = $value[ $side ]['style'];
		}
		$css_value = implode( ' ', $values_ordered );
		return trim( $css_value );
	}

	/**
	 * Gets a border property value (radius, style, width, or color).
	 *
	 * @param array  $value         The border value array.
	 * @param string $property_name The property name to extract.
	 * @return string The CSS border property value.
	 */
	public static function get_border_property_value( $value, $property_name ) {
		if ( ! is_array( $value ) ) {
			return '';
		}

		// If the borders are in sync, then we'll have the property we need directly.
		if ( isset( $value[ $property_name ] ) ) {
			return $value[ $property_name ];
		}

		$sides          = array( 'top', 'right', 'bottom', 'left' );
		$css_value      = '';
		$values_ordered = array();

		// Loop through each side and get the style value.
		foreach ( $sides as $side ) {
			if ( ! isset( $value[ $side ] ) ) {
				return '';
			}
			if ( ! isset( $value[ $side ][ $property_name ] ) ) {
				return '';
			}
			$values_ordered[] = $value[ $side ][ $property_name ];
		}
		$css_value = implode( ' ', $values_ordered );
		return trim( $css_value );
	}

	/**
	 * Get the value of a single border style property.
	 *
	 * @param array $value The value of the border style property which is an array of width, style, and color.
	 * @return string The value of the border style property.
	 */
	public static function get_single_border_style_value( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		// Make sure we have all 3 properties - order is important.
		$required_properties = array( 'width', 'style', 'color' );
		$css_value           = '';
		$properties_ordered  = array();

		foreach ( $required_properties as $property ) {
			// If any property is missing, return 'none'.
			if ( ! isset( $value[ $property ] ) ) {
				return 'none';
			}

			$properties_ordered[] = trim( $value[ $property ] );
		}
		$css_value = implode( ' ', $properties_ordered );
		return trim( $css_value );
	}

	/**
	 * Custom safecss_filter_attr with modern CSS color function support.
	 *
	 * Based on WordPress core's safecss_filter_attr() from WP 6.9.
	 *
	 * MODIFICATION: Line ~822 adds support for modern CSS color functions:
	 * color-mix(), oklch(), oklab(), lch(), lab(), hwb(), light-dark(), color()
	 *
	 * Why: WP core strips var() inside color-mix() before our filter runs,
	 * breaking values like: color-mix(in srgb, var(--some-color) 50%, transparent)
	 *
	 * @param string $css A string of CSS rules.
	 * @return string Filtered string of CSS rules.
	 */
	private static function safecss_filter_attr( $css ) {
		$css = wp_kses_no_null( $css );
		$css = str_replace( array( "\n", "\r", "\t" ), '', $css );

		$allowed_protocols = wp_allowed_protocols();

		$css_array = explode( ';', trim( $css ) );

		/**
		 * Filters the list of allowed CSS attributes.
		 */
		$allowed_attr = apply_filters(
			'safe_style_css',
			array(
				'background',
				'background-color',
				'background-image',
				'background-position',
				'background-repeat',
				'background-size',
				'background-attachment',
				'background-blend-mode',

				'border',
				'border-radius',
				'border-width',
				'border-color',
				'border-style',
				'border-right',
				'border-right-color',
				'border-right-style',
				'border-right-width',
				'border-bottom',
				'border-bottom-color',
				'border-bottom-style',
				'border-bottom-width',
				'border-left',
				'border-left-color',
				'border-left-style',
				'border-left-width',
				'border-top',
				'border-top-color',
				'border-top-style',
				'border-top-width',
				'border-spacing',
				'border-collapse',

				'caption-side',

				'columns',
				'column-count',
				'column-fill',
				'column-gap',
				'column-rule',
				'column-span',
				'column-width',

				'color',
				'filter',
				'font',
				'font-family',
				'font-size',
				'font-style',
				'font-variant',
				'font-weight',
				'letter-spacing',
				'line-height',
				'text-align',
				'text-decoration',
				'text-indent',
				'text-transform',

				'height',
				'min-height',
				'max-height',

				'width',
				'min-width',
				'max-width',

				'margin',
				'margin-right',
				'margin-bottom',
				'margin-left',
				'margin-top',
				'margin-block-start',
				'margin-block-end',
				'margin-inline-start',
				'margin-inline-end',

				'padding',
				'padding-right',
				'padding-bottom',
				'padding-left',
				'padding-top',
				'padding-block-start',
				'padding-block-end',
				'padding-inline-start',
				'padding-inline-end',

				'flex',
				'flex-basis',
				'flex-direction',
				'flex-flow',
				'flex-grow',
				'flex-shrink',
				'flex-wrap',

				'gap',
				'column-gap',
				'row-gap',

				'grid',
				'grid-area',
				'grid-auto-columns',
				'grid-auto-flow',
				'grid-auto-rows',
				'grid-column',
				'grid-column-end',
				'grid-column-gap',
				'grid-column-start',
				'grid-gap',
				'grid-row',
				'grid-row-end',
				'grid-row-gap',
				'grid-row-start',
				'grid-template',
				'grid-template-areas',
				'grid-template-columns',
				'grid-template-rows',

				'justify-content',
				'justify-items',
				'justify-self',
				'align-content',
				'align-items',
				'align-self',

				'clear',
				'cursor',
				'direction',
				'float',
				'list-style-type',
				'object-fit',
				'object-position',
				'overflow',
				'overflow-x',
				'overflow-y',
				'vertical-align',
				'writing-mode',

				'position',
				'top',
				'right',
				'bottom',
				'left',
				'z-index',

				'aspect-ratio',
				'display',
				'opacity',
				'visibility',

				// Custom properties (CSS variables).
				'--*',
			)
		);

		$css = '';

		foreach ( $css_array as $css_item ) {
			if ( '' === $css_item ) {
				continue;
			}

			$css_item        = trim( $css_item );
			$css_test_string = $css_item;
			$found           = false;
			$url_attr        = false;
			$gradient_attr   = false;

			if ( ! str_contains( $css_item, ':' ) ) {
				$found = true;
			} else {
				$parts        = explode( ':', $css_item, 2 );
				$css_selector = trim( $parts[0] );

				if ( in_array( $css_selector, $allowed_attr, true ) ) {
					$found         = true;
					$url_attr      = in_array( $css_selector, array( 'background', 'background-image' ), true );
					$gradient_attr = in_array( $css_selector, array( 'background', 'background-image' ), true );
				} elseif ( str_starts_with( $css_selector, '--' ) && in_array( '--*', $allowed_attr, true ) ) {
					// Allow custom properties (CSS variables).
					$found = true;
				}

				if ( $found && $url_attr ) {
					$css_value = trim( $parts[1] );
					// Handle url().
					if ( preg_match_all( '/url\s*\(\s*[\'"]?\s*([^\'"\)]+)[\'"]?\s*\)/', $css_value, $matches, PREG_SET_ORDER ) ) {
						foreach ( $matches as $match ) {
							$url_match = $match[0];
							$url       = $match[1];
							// Clean up the URL.
							$url = trim( $url );
							// Check the URL against allowed protocols.
							if ( wp_kses_bad_protocol( $url, $allowed_protocols ) !== $url ) {
								$found = false;
								break;
							}
							// Remove the url() bit from the test string.
							$css_test_string = str_replace( $url_match, '', $css_test_string );
						}
					}
				}

				if ( $found && $gradient_attr ) {
					$css_value = trim( $parts[1] );
					if ( preg_match( '/^(repeating-)?(linear|radial|conic)-gradient\(([^()]|rgb[a]?\([^()]*\))*\)$/', $css_value ) ) {
						// Remove the whole `gradient` bit that was matched above from the CSS.
						$css_test_string = str_replace( $css_value, '', $css_test_string );
					}
				}
			}

			if ( $found ) {
				/*
				* Allow CSS functions like var(), calc(), etc. by removing them from the test string.
				* Nested functions and parentheses are also removed, so long as the parentheses are balanced.
				*
				* MODIFIED: Added color-mix, oklch, oklab, lch, lab, hwb, light-dark, color
				*/
				$css_test_string = preg_replace(
					'/\b(?:var|calc|min|max|minmax|clamp|repeat|color-mix|oklch|oklab|lch|lab|hwb|light-dark|color)(\((?:[^()]|(?1))*\))/i',
					'',
					$css_test_string
				);

				/*
				* Disallow CSS containing \ ( & } = or comments, except for within url(), var(), calc(), etc.
				* which were removed from the test string above.
				*/
				$allow_css = ! preg_match( '%[\\\(&=}]|/\*%', $css_test_string );

				/**
				 * Filters the check for unsafe CSS in `safecss_filter_attr`.
				 */
				$allow_css = apply_filters( 'safecss_filter_attr_allow_css', $allow_css, $css_test_string );

				if ( $allow_css ) {
					if ( '' !== $css ) {
						$css .= ';';
					}
					$css .= $css_item;
				}
			}
		}

		return $css;
	}
}
