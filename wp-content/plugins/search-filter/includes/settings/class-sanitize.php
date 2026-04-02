<?php
/**
 * Sanitizes setting values according the settings sanitization config.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter\Settings;

use Search_Filter\Core\Sanitize as Core_Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base class for all settings sections.
 *
 * @since      3.0.0
 */
class Sanitize {


	/**
	 * Sanitizes a value according to the settings sanitization config.
	 *
	 * @param mixed   $var_to_clean The value to sanitize.
	 * @param Setting $setting The setting to sanitize the value for.
	 * @return mixed The sanitized value.
	 */
	public static function clean( $var_to_clean, Setting $setting ) {

		$defaults        = array(
			'whitespace' => 'trim',
		);
		$sanitize_config = $defaults;
		if ( ! empty( $setting->get_data( 'sanitize' ) ) ) {
			$sanitize_config = wp_parse_args( $setting->get_data( 'sanitize' ), $defaults );
		}

		$keep_whitespace = false;
		if ( $sanitize_config['whitespace'] === 'trim' ) {
			$keep_whitespace = false;
		} elseif ( $sanitize_config['whitespace'] === 'keep' ) {
			$keep_whitespace = true;
		}
		return Core_Sanitize::deep_clean( $var_to_clean, $keep_whitespace );
	}

	/**
	 * Sanitize attributes
	 *
	 * @param array $setting_values The attributes to sanitize.
	 * @param array $settings The settings to sanitize the attributes for.
	 * @return array The sanitized attributes.
	 */
	public static function settings( array $setting_values, array $settings ) {
		$cleaned_attributes = array();
		foreach ( $setting_values as $attribute => $value ) {
			if ( isset( $settings[ $attribute ] ) ) {
				$cleaned_attributes[ $attribute ] = self::clean( $value, $settings[ $attribute ] );
			} else {
				$cleaned_attributes[ $attribute ] = Core_Sanitize::deep_clean( $value, true );
			}
		}
		return $cleaned_attributes;
	}
}
