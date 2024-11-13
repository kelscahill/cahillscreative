<?php
namespace Search_Filter;

class Debug_Bar {

	private static $template_name = '';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_debug_menu_item' ) );
	}
	public static function add_debug_menu_item() {
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action( 'admin_bar_menu', array( __CLASS__, 'add_debug_menu_item_to_admin_bar' ), 100 );
		// Add the debug data to the frontend.
		add_action( 'search-filter/frontend/data', array( __CLASS__, 'add_frontend_debug_data' ), 100 );

		// Set the template name earlier when WP loads. For some reason calling `get_page_template` in the
		// footer with formidable forms, it causes the "Edit Site" link not to work in FSE themes (points to
		// the wrong template).
		add_action( 'init', array( __CLASS__, 'set_template_name' ), 100 );

		// Load scripts late - we want to load after S&F scripts but not have
		// to set S&F frontend as a dependency.
		if ( is_admin_bar_showing() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 100 );
		}
	}

	public static function set_template_name() {
		self::$template_name = basename( get_page_template() );
	}

	public static function enqueue_scripts() {
		// Load script + styles for menu functionality.
		wp_enqueue_style( 'search-filter-debug', \Search_Filter\Core\Scripts::get_frontend_assets_url() . 'css/frontend/debug.css', array(), SEARCH_FILTER_VERSION );

		wp_enqueue_script( 'search-filter-debug', \Search_Filter\Core\Scripts::get_frontend_assets_url() . 'js/frontend/debug.js', array( 'wp-hooks' ), SEARCH_FILTER_VERSION, true );

		wp_localize_script(
			'search-filter-debug',
			'searchFilterDebug',
			Util::get_js_data()
		);
	}
	public static function add_debug_menu_item_to_admin_bar( $wp_admin_bar ) {
		$menu_id = 'search-filter-debug';
		$args    = array(
			'id'    => $menu_id,
			'title' => 'Search & Filter',
			'href'  => '#',
			'meta'  => array(
				'class' => 'search-filter-debug',
			),
		);
		$wp_admin_bar->add_node( $args );

		// We need to add a blank menu item so that
		// the container html is generated.  It will be
		// replaced with the JS app.
		$wp_admin_bar->add_menu(
			array(
				'parent' => $menu_id,
				'id'     => 'search-filter-placeholder',
				// 'href'   => '#',
				// 'meta'   => array( 'target' => '_blank' ),
			)
		);
	}

	public static function add_frontend_debug_data( $data ) {

		$template_data = array(
			'template'                          => self::$template_name,
			'queried_object_id'                 => get_queried_object_id(),
			'is_archive'                        => is_archive() ? 'true' : 'false',
			'is_search'                         => is_search() ? 'true' : 'false',
			'is_home (blog)'                    => is_home() ? 'true' : 'false',
			'is_front_page'                     => is_front_page() ? 'true' : 'false',
			'is_singular'                       => is_singular() ? 'true' : 'false',
			'is_page'                           => is_page() ? 'true' : 'false',
			'is_single'                         => is_single() ? 'true' : 'false',
			'is_404'                            => is_404() ? 'true' : 'false',
			'is_attachment'                     => is_attachment() ? 'true' : 'false',
			'is_author'                         => is_author() ? 'true' : 'false',
			'is_category'                       => is_category() ? 'true' : 'false',
			'is_tag'                            => is_tag() ? 'true' : 'false',
			'is_tax'                            => is_tax() ? 'true' : 'false',
			'is_singular_taxonomy_term_archive' => \Search_Filter\Query\Template_Data::is_singular_taxonomy_term_archive() ? 'true' : 'false',
			'taxonomy_term_archive_has_multiple_post_types' => \Search_Filter\Query\Template_Data::taxonomy_term_archive_has_multiple_post_types() ? 'true' : 'false',
			'is_date'                           => is_date() ? 'true' : 'false',
			'is_post_type_archive'              => is_post_type_archive() ? 'true' : 'false',
			'is_paged'                          => is_paged() ? 'true' : 'false',
			'is_preview'                        => is_preview() ? 'true' : 'false',
			'is_admin'                          => is_admin() ? 'true' : 'false',
			'is_customize_preview'              => is_customize_preview() ? 'true' : 'false',
			'is_rtl'                            => is_rtl() ? 'true' : 'false',
			'is_ssl'                            => is_ssl() ? 'true' : 'false',
			'is_user_logged_in'                 => is_user_logged_in() ? 'true' : 'false',
			'is_main_query'                     => is_main_query() ? 'true' : 'false',
			'wp_doing_ajax'                     => wp_doing_ajax() ? 'true' : 'false',
			// 'is_day'               => is_day() ? 'true' : 'false',
			// 'is_month'             => is_month() ? 'true' : 'false',
			// 'is_year'              => is_year() ? 'true' : 'false',
			// 'is_new_day'           => is_new_day() ? 'true' : 'false',

		);
		$data['template'] = $template_data;
		return $data;
	}
}
