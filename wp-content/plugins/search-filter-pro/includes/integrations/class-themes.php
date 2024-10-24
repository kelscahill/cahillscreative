<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter_Pro\Integrations;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the theme integrations.
 */
class Themes {
	/**
	 * Load the theme integrations.
	 */
	public static function init() {
		$template_name = get_template();
	}
}
