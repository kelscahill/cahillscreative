<?php
/**
 * Blocksy Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */

namespace Search_Filter\Integrations\Themes;

use Search_Filter\Admin\Screens;

/**
 * Class for handling the Blocksy theme integration with Search & Filter
 */
class Blocksy {

	/**
	 * Tracks if hte styles have been loaded already.
	 *
	 * @var boolean
	 */
	private $has_enqueued_backend_styles = false;
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'current_screen', array( $this, 'current_screen' ), 21 );
		// add_filter( 'wp_print_scripts', array( $this, 'load_backend_dynamic_css' ), 21 );
	}

	/**
	 * Set is block editor back to false as this throws an error.
	 */
	public function current_screen() {
		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}
		// Blocksy throws an error if block editor is true (when adding body classes as it assumes $post is set).
		$screen = get_current_screen();
		$screen->is_block_editor( false );
	}

	/**
	 * [unused] Looks like this duplicates what Blocksy already does, but its missing
	 * some styles that Blocksy seems to output in the block editor.
	 */
	public function load_backend_dynamic_css() {
		if ( ! is_admin() ) {
			return;
		}

		if ( $this->has_enqueued_backend_styles ) {
			return;
		}

		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		$this->has_enqueued_backend_styles = true;

		if ( ! class_exists( '\Blocksy_Dynamic_Css' ) ) {
			return;
		}
		$blocksy_css = new \Blocksy_Dynamic_Css();
		$blocksy_css->load_backend_dynamic_css();
	}
}
