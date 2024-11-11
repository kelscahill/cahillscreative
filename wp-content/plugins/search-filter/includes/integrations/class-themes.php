<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter\Integrations;

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

		if ( $template_name === 'neve' ) {
			new \Search_Filter\Integrations\Themes\Neve();
		} elseif ( $template_name === 'kadence' ) {
			new \Search_Filter\Integrations\Themes\Kadence();
		} elseif ( $template_name === 'generatepress' ) {
			new \Search_Filter\Integrations\Themes\Generate_Press();
		} elseif ( $template_name === 'astra' ) {
			new \Search_Filter\Integrations\Themes\Astra();
		} elseif ( $template_name === 'twentytwentyone' ) {
			new \Search_Filter\Integrations\Themes\Twentytwentyone();
		} elseif ( $template_name === 'blocksy' ) {
			// TODO: Not working well, leave out for now.
			// new \Search_Filter\Integrations\Themes\Blocksy();
		}
	}
}
