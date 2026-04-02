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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settingsfor styles
 */
class Tokens {

	/**
	 * The default tokens.
	 *
	 * @var array
	 */
	private static $tokens = array(

		/**
		 * Semantic tokens, to be used for controlling properties
		 * across multiple fields at the same time.
		 */
		'label-padding'         => array(
			'label'   => 'Label Padding',
			'var'     => 'label-padding',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			),
		),
		'label-margin'          => array(
			'label'   => 'Label Margin',
			'var'     => 'label-margin',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '8px',
				'left'   => '0',
			),
		),
		'description-padding'   => array(
			'label'   => 'Description Padding',
			'var'     => 'description-padding',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			),
		),
		'description-margin'    => array(
			'label'   => 'Description Margin',
			'var'     => 'description-margin',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '8px',
				'left'   => '0',
			),
		),
		'field-padding'         => array(
			'label'   => 'Field Padding',
			'var'     => 'field-padding',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			),
		),
		'field-margin'          => array(
			'label'   => 'Field Margin',
			'var'     => 'field-margin',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			),
		),
		'input-margin'          => array(
			'label'   => 'Input Margin',
			'var'     => 'input-margin',
			'type'    => 'spacing',
			'default' => array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			),
		),
		'label-scale'           => array(
			'label'   => 'Label Scale',
			'var'     => 'label-scale',
			'type'    => 'number',
			'default' => 2,
		),
		'description-scale'     => array(
			'label'   => 'Description Scale',
			'var'     => 'description-scale',
			'type'    => 'number',
			'default' => 2,
		),
		'input-scale'           => array(
			'label'   => 'Input Scale',
			'var'     => 'input-scale',
			'type'    => 'number',
			'default' => 2,
		),


		/**
		 * Abstract tokens - presets for colors, border radius, etc.
		 */
		'color-transparent'     => array(
			'label'   => 'Transparent',
			'var'     => 'color-transparent',
			'type'    => 'color',
			'default' => '#00000000',
		),
		'color-base-1'          => array(
			'label'   => 'Base 1',
			'var'     => 'color-base-1',
			'type'    => 'color',
			'default' => '#ffffff',
		),
		'color-base-2'          => array(
			'label'   => 'Base 2',
			'var'     => 'color-base-2',
			'type'    => 'color',
			'default' => '#bbbbbb',
		),
		'color-base-3'          => array(
			'label'   => 'Base 3',
			'var'     => 'color-base-3',
			'type'    => 'color',
			'default' => '#888888',
		),
		'color-base-accent'     => array(
			'label'   => 'Base Accent',
			'var'     => 'color-base-accent',
			'type'    => 'color',
			'default' => '#167de4',
		),
		'color-contrast-1'      => array(
			'label'   => 'Contrast 1',
			'var'     => 'color-contrast-1',
			'type'    => 'color',
			'default' => '#333333',
		),
		'color-contrast-2'      => array(
			'label'   => 'Contrast 2',
			'var'     => 'color-contrast-2',
			'type'    => 'color',
			'default' => '#3c434a',
		),
		'color-contrast-accent' => array(
			'label'   => 'Contrast Accent',
			'var'     => 'color-contrast-accent',
			'type'    => 'color',
			'default' => '#ffffff',
		),

		// Border radius tokens.
		'border-radius-square'  => array(
			'label'   => 'Border Radius Square',
			'var'     => 'border-radius-square',
			'type'    => 'unit',
			'default' => '0',
		),
		'border-radius-soft'    => array(
			'label'   => 'Border Radius Soft',
			'var'     => 'border-radius-soft',
			'type'    => 'unit',
			'default' => 'calc(0.25 * var(--search-filter-scale-base-size))',
		),
		'border-radius-round'   => array(
			'label'   => 'Border Radius Round',
			'var'     => 'border-radius-round',
			'type'    => 'unit',
			'default' => 'var(--search-filter-scale-base-size)',
		),
	);

	/**
	 * Gets the default tokens.
	 *
	 * @return array
	 */
	public static function get() {
		return self::$tokens;
	}

	/**
	 * Gets a property from a token.
	 *
	 * @param string $token_name The name of the token.
	 * @param string $property_name The name of the property.
	 * @return mixed
	 */
	public static function get_prop( $token_name, $property_name ) {
		if ( ! isset( self::$tokens[ $token_name ] ) ) {
			return null;
		}

		if ( ! isset( self::$tokens[ $token_name ][ $property_name ] ) ) {
			return null;
		}
		return self::$tokens[ $token_name ][ $property_name ];
	}

	/**
	 * Gets the default tokens.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$defaults = array();
		foreach ( self::$tokens as $token_name => $token ) {
			if ( ! isset( $token['default'] ) ) {
				continue;
			}
			$defaults[ $token_name ] = $token['default'];
		}
		return $defaults;
	}
}
