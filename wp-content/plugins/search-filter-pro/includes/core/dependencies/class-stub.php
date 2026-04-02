<?php
/**
 * Base stub field class for dependency compatibility.
 *
 * NOTE: This class intentionally overrides non-static properties/methods with static versions
 * for backward compatibility with beta versions. This causes PHPStan errors that cannot be
 * suppressed with inline directives. Consider adding this file to PHPStan baseline or
 * excluding it from analysis if these errors are problematic.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Core/Dependencies
 */

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base stub field to prevent fatal errors when upgrading between beta versions.
 */
class Stub extends \Search_Filter\Fields\Field {

	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array();

	/**
	 * Processed setting support cache.
	 *
	 * @var array|null
	 */
	protected static $processed_setting_support = null;

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public static $input_type = 'submit';

	/**
	 * Get the setting support configuration.
	 *
	 * @return array
	 */
	public static function get_setting_support() {
		return self::$setting_support;
	}

	/**
	 * Supported styles.
	 *
	 * @var array
	 */
	public static $styles = array();

	/**
	 * Processed styles cache.
	 *
	 * @var array|null
	 */
	protected static $processed_styles = null;

	/**
	 * Get the styles support configuration.
	 *
	 * @return array
	 */
	public static function get_styles_support() {
		return self::$styles;
	}

	/**
	 * Get the field label.
	 *
	 * @return string
	 */
	public static function get_label() {
		return 'rand_' . wp_rand( 1, 100000 );
	}

	/**
	 * Get the field description.
	 *
	 * @return string
	 */
	public static function get_description() {
		return '';
	}

	/**
	 * Field icons.
	 *
	 * @var array
	 */
	public $icons = array();

	/**
	 * Get the field icons.
	 *
	 * Note: Static override of non-static method is intentional for backward compatibility.
	 *
	 * @return array
	 */
	public static function get_icons() {
		return self::$icons;
	}
}
