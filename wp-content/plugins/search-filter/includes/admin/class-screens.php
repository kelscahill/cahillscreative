<?php
/**
 * Admin Screen class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Admin
 */

namespace Search_Filter\Admin;

use Search_Filter\Core\SVG_Loader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles most things to do with displaying our admin menus
 * and screens (and augmenting existing ones).
 */
class Screens {
	/**
	 * Stores the current screen name, -1 if not set yet.
	 *
	 * @var int/string
	 */
	private static $screen_name = -1;

	/**
	 * Stores the status of if we're in one of our admin screens.
	 *
	 * @var int/string
	 */
	private static $is_search_filter_screen = -1;

	/**
	 * Stores the pages as an array so we can also pass to our JS app.
	 *
	 * @var int/string
	 */
	private static $pages = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Init the pages.
		self::$pages = array(
			(object) array(
				'title' => __( 'Dashboard', 'search-filter' ),
			),
			(object) array(
				'title'   => __( 'Queries', 'search-filter' ),
				'section' => 'queries',
			),
			(object) array(
				'title'   => __( 'Fields', 'search-filter' ),
				'section' => 'fields',
			),
			(object) array(
				'title'   => __( 'Styles', 'search-filter' ),
				'section' => 'styles',
			),

			(object) array(
				'title'   => __( 'Settings', 'search-filter' ),
				'section' => 'settings',
			),
			(object) array(
				'title'   => __( 'Integrations', 'search-filter' ),
				'section' => 'integrations',
			),
			(object) array(
				'title'   => __( 'Help', 'search-filter' ),
				'section' => 'help',
			),
			(object) array(
				'title'   => __( 'Upgrade', 'search-filter' ),
				'section' => 'pro',
			),
		);
	}

	/**
	 * Get a list of the admin pages (for rebuilding the menu)
	 *
	 * @since    3.0.0
	 */
	public static function get_pages() {
		return apply_filters( 'search-filter/admin/screens/get_pages', self::$pages );
	}

	/**
	 * Add the admin pages
	 *
	 * @since    3.0.0
	 */
	public static function admin_pages() {
		add_menu_page( __( 'Dashboard', 'search-filter' ), __( 'Search & Filter', 'search-filter' ), 'manage_options', 'search-filter', array( __CLASS__, 'search_filter_screen_main' ), self::get_icon(), '100.23243' );
	}

	/**
	 * Add the admin sub pages
	 *
	 * @since    3.0.0
	 */
	public static function admin_pages_more_menu_items() {

		foreach ( self::get_pages() as $page ) {

			$page_menu_slug = 'search-filter';
			if ( isset( $page->section ) ) {
				if ( $page->section !== '' ) {
					$page_menu_slug .= '&section=' . $page->section;
				}
			}
			add_submenu_page(
				'search-filter',
				$page->title,
				$page->title,
				'manage_options',
				$page_menu_slug,
				'__return_null'
			);
		}
	}

	/**
	 * Modify the active submenu
	 *
	 * @since    3.0.0
	 *
	 * @param    string $submenu_file   The submenu file.
	 * @param    string $parent_file    The parent file.
	 *
	 * @return   string The new submenu file.
	 */
	public static function modify_active_submenu( $submenu_file, $parent_file ) {
		if ( ! self::is_search_filter_screen() ) {
			return $submenu_file;
		}

		if ( 'search-filter' === $parent_file ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['section'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$submenu_file = 'search-filter&section=' . sanitize_key( $_GET['section'] );
			}
		}

		return $submenu_file;
	}

	/**
	 * The main S&F page
	 *
	 * @since    3.0.0
	 */
	public static function search_filter_screen_main() {
		echo "<div id='sfa-screen-main' class='search-filter-admin'></div>";
	}

	/**
	 * Admin header actions.
	 *
	 * @since    3.0.0
	 */
	public static function admin_head() {
	}
	/**
	 * Load any SVGs required in our admin pages
	 *
	 * @since    3.0.0
	 */
	public static function admin_footer() {
		if ( self::is_search_filter_screen() ) {
			SVG_Loader::output();
		}
	}

	/**
	 * Get an inline SVG for our menu icon
	 *
	 * @return string    The data:image, use inside the `src` attribute of an <img>
	 */
	public static function get_icon() {
		ob_start();
		?>
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 20 20" version="1.1">
				<g id="surface1">
					<path style=" stroke:none;fill-rule:nonzero;fill:#9ca2a7;fill-opacity:1;" d="M 7.601562 7.601562 C 6.988281 8.214844 6.609375 9.0625 6.609375 10 C 6.609375 10.9375 6.988281 11.785156 7.601562 12.394531 C 8.214844 13.007812 9.0625 13.390625 10 13.390625 C 10.933594 13.390625 11.78125 13.007812 12.394531 12.394531 C 13.007812 11.785156 13.386719 10.9375 13.386719 10 C 13.386719 9.0625 13.007812 8.214844 12.394531 7.601562 C 11.78125 6.988281 10.933594 6.609375 10 6.609375 C 9.0625 6.609375 8.214844 6.988281 7.601562 7.601562 "/>
					<path style=" stroke:none;fill-rule:nonzero;fill:#9ca2a7;fill-opacity:1;" d="M 9.097656 1.277344 L 5.996094 3.066406 L 2.898438 4.859375 C 2.324219 5.1875 1.996094 5.757812 1.996094 6.421875 L 1.996094 13.578125 C 1.996094 14.242188 2.324219 14.8125 2.898438 15.140625 L 5.996094 16.929688 L 9.097656 18.71875 C 9.671875 19.050781 10.328125 19.050781 10.902344 18.71875 L 14 16.929688 L 16.621094 15.417969 C 16.6875 15.378906 16.726562 15.3125 16.726562 15.234375 C 16.726562 15.160156 16.6875 15.09375 16.621094 15.054688 L 15.3125 14.304688 C 15.210938 14.246094 15.175781 14.117188 15.234375 14.015625 L 15.316406 13.871094 L 14.472656 13.386719 C 14.316406 13.59375 14.148438 13.785156 13.964844 13.964844 C 12.953125 14.984375 11.546875 15.609375 9.996094 15.609375 C 8.449219 15.609375 7.046875 14.984375 6.027344 13.964844 C 5.015625 12.949219 4.386719 11.546875 4.386719 9.996094 C 4.386719 8.449219 5.015625 7.046875 6.027344 6.027344 C 7.042969 5.011719 8.449219 4.382812 9.996094 4.382812 C 11.546875 4.382812 12.949219 5.011719 13.964844 6.03125 C 14.984375 7.042969 15.609375 8.449219 15.609375 9.996094 C 15.609375 10.746094 15.464844 11.460938 15.195312 12.117188 L 16.042969 12.597656 L 16.125 12.449219 C 16.183594 12.347656 16.3125 12.3125 16.414062 12.371094 C 16.839844 12.613281 17.261719 12.851562 17.6875 13.097656 C 17.753906 13.132812 17.828125 13.132812 17.898438 13.09375 C 17.964844 13.054688 18.003906 12.988281 18.003906 12.914062 L 18.003906 6.417969 C 18.003906 5.757812 17.675781 5.1875 17.097656 4.855469 L 10.902344 1.277344 C 10.617188 1.113281 10.308594 1.03125 10 1.03125 C 9.691406 1.03125 9.382812 1.113281 9.097656 1.277344 "/>
				</g>
			</svg>
		<?php
		$svg  = trim( ob_get_clean() );
		$icon = 'data:image/svg+xml;base64,' . base64_encode( $svg );
		return $icon;
	}

	/**
	 * Removes admin notices from our screens (we will implement our own, plus, they break our layouts)
	 *
	 * Note: block editor does not support these for editing posts, so maybe we don't have to? Feels
	 * like we're breaking the rules a bit...
	 *
	 * @return void
	 */
	public static function remove_admin_notices() {
		if ( self::is_search_filter_screen() ) {
			remove_all_actions( 'admin_notices' );
		}
	}

	/**
	 * Checks if we are on an admin screen (belonging to S&F)
	 *
	 * @return boolean
	 */
	public static function is_search_filter_screen() {
		if ( -1 === self::$is_search_filter_screen ) {
			$screen_names = array(
				'toplevel_page_search-filter',
				'edit-search-filter-query',
				'search-filter-query',
				'search-filter',
				'search-filter_page_search-filter-query',
				'search-filter_page_search-filter-styles',
			);

			self::$is_search_filter_screen = false;
			if ( in_array( self::get_screen_name(), $screen_names, true ) ) {
				self::$is_search_filter_screen = true;
			}
		}
		return self::$is_search_filter_screen;
	}

	/**
	 * Returns the current screen name, if not already stored locally it will retrieve it.
	 *
	 * @return string
	 */
	public static function get_screen_name() {
		if ( -1 === self::$screen_name ) {
			$current_screen    = \get_current_screen();
			self::$screen_name = $current_screen->id;
		}
		return self::$screen_name;
	}

	/**
	 * Outputs the CSS for the admin menu.
	 */
	public static function menu_css() {
		?>
		<style>
			#toplevel_page_search-filter>ul>li:nth-child(6)::before,
			#toplevel_page_search-filter>ul>li:nth-child(3)::before {
				border-top: 1px solid #444444;
				content: "";
				height: 0;
				margin: 0;
				padding: 0;
				display: block;
				width: 100%;
				margin: 6px 0;
				overflow: hidden;
			}
		</style>
		<?php
	}

	/**
	 * Get the admin screen options.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_admin_screen_options() {
		$screen_options = get_user_meta( get_current_user_id(), 'search_filter_screen_options', true );
		if ( ! $screen_options || $screen_options === '' ) {
			$screen_options = array(
				'fields'                   => array(
					'itemsPerPage' => '10',
					'columns'      => array( 'name', 'type', 'query', 'status', 'date', 'actions' ),
				),
				'queryEditConnectedFields' => array(
					'itemsPerPage' => '10',
					'columns'      => array( 'name', 'type', 'status', 'date', 'actions' ),
				),
				'queries'                  => array(
					'itemsPerPage' => '10',
					'columns'      => array( 'name', 'fields', 'status', 'date', 'actions' ),
				),
				'styles'                   => array(
					'itemsPerPage' => '10',
					// 'columns' => array( 'name', 'status', 'date' ),
				),
			);
		}
		// Ensure 'name' and 'actions' are present in all columns.
		foreach ( $screen_options as $key => $value ) {
			if ( isset( $value['columns'] ) ) {
				if ( ! in_array( 'name', $value['columns'], true ) ) {
					array_unshift( $screen_options[ $key ]['columns'], 'name' );
				}
				if ( ! in_array( 'actions', $value['columns'], true ) ) {
					array_push( $screen_options[ $key ]['columns'], 'actions' );
				}
			}
		}
		return $screen_options;
	}

}


