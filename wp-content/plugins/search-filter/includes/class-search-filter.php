<?php
/**
 * The file that defines the core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Search_Filter\Debug_Bar;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Rest_API;
use Search_Filter\Admin\Screens;
use Search_Filter\Core\Scripts;

/**
 * The main entry point for the plugin
 *
 * Loads all hooks and instantiates the classes required
 * to both frontend and admin.
 *
 * @since 3.0.0
 */
class Search_Filter {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Search_Filter_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version
	 */
	protected $version;
	/**
	 * Instance of the rest API
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $rest_api
	 */
	protected $rest_api;
	/**
	 * Instance of the integrations class
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $integrations
	 */
	protected $integrations;
	/**
	 * Instance of the Features class
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $features
	 */
	protected $features;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'search-filter';
		$this->version     = SEARCH_FILTER_VERSION;

		$this->load_dependencies();
		$this->set_locale(); // Needs to go after load_dependencies.
		$this->load_rest_api();

		add_action( 'plugins_loaded', array( $this, 'init_dependencies' ), 1 );

		// Correctly load public / admin classes & hooks.
		if ( ( ! is_admin() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->define_frontend_hooks();
		} elseif ( is_admin() ) {
			$this->define_admin_hooks();
		}
	}
	public function init_dependencies() {

		/**
		 * ORDER IS IMPORTANT !
		 */
		$schema = new Search_Filter\Core\Schema();
		$schema->init();

		// Fields must be registered before settings (so they can register their own settings).
		Field_Factory::register_types();
		Debug_Bar::init();
		\Search_Filter\Theme_Styles::init();
		\Search_Filter\Core\CSS_Loader::init();
		\Search_Filter\Core\Upgrader::init();

		/**
		 * Setup the CSS_Loader.
		 *
		 * Important, until we seperate out the CSS files, styles must be loaded
		 * before fields, so the field classes have priority over the styles.
		 * CSS is generated based on order registered with the CSS_Loader (we could
		 * also add a priority to the CSS_Loader but we'll need to break out the
		 * the files at some stage anyway).
		 */
		\Search_Filter\Styles::register_css_handler();
		\Search_Filter\Fields::register_css_handler();
		\Search_Filter\Queries::register_css_handler();

		/**
		 * These classes all register settings on `init` with a priority of `1`
		 * So we must be inside `init` `0` or lower here to ensure the hooks are
		 * attached and fired.
		 *
		 * The reason for settings being registered on `init` inside each class is
		 * to ensure that each of these classes can potentially modify the settings
		 * of another class.
		 *
		 * For example, an integration could add features to the features list and
		 * we would want to do this immediately after the features settings have been
		 * registered.
		 *
		 * TODO - we need to split the admin UI settings from the settings data so
		 * the UI settings info is only loaded in admin.
		 */
		\Search_Filter\Features::init();
		\Search_Filter\Integrations::init();
		/**
		 * Important, Styles need to be loaded after the fields, because their settings
		 * depend on the fields settings being loaded already (styles options are extracted
		 * from the fields settings).
		 */
		\Search_Filter\Fields::init();
		\Search_Filter\Styles::init();
		\Search_Filter\Queries::init();

		// This matches the settings init hooks found in the above classes, so it
		// should be fired close to the last one (in Styles) has been fired.
		add_action( 'init', array( __CLASS__, 'after_register_settings' ), 1 );
	}

	/**
	 * After the settings have been registered, emit our own init event.
	 *
	 * @return void
	 */
	public static function after_register_settings() {
		// TODO - this hook doesn't match our naming convention.
		do_action( 'search-filter/settings/init' );
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Search_Filter\Core\Loader. Orchestrates the hooks of the plugin.
	 * - Search_Filter\Core\I18n. Defines internationalization functionality.
	 * - Search_Filter\Admin. Defines all hooks for the admin area.
	 * - Search_Filter\Frontend. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and fields of the
		 * core plugin.
		 */
		$this->loader = new Search_Filter\Core\Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Search_Filter_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new \Search_Filter\Core\I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		Screens::init();
		$plugin_admin = new \Search_Filter\Admin( $this->get_plugin_name(), $this->get_version() );

		// Scripts & css.
		add_action( 'admin_print_scripts', array( Scripts::class, 'output_init_js' ), 10 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Plugin updater.
		add_action( 'admin_init', array( \Search_Filter\Admin::class, 'update_plugin' ), 20 );

		// Data.

		// Setup Admin Screens.
		$this->loader->add_filter( 'submenu_file', Screens::class, 'modify_active_submenu', 20, 2 );
		$this->loader->add_action( 'admin_menu', Screens::class, 'admin_pages', 9 );
		$this->loader->add_action( 'admin_menu', Screens::class, 'admin_pages_more_menu_items', 10 );
		$this->loader->add_action( 'admin_footer', Screens::class, 'admin_footer', 20 );
		$this->loader->add_action( 'admin_head', Screens::class, 'menu_css', 20 );

		// We want to get in as early as possible, and remove all admin notices
		// This is the closest action before admin_notices that I can find.
		$this->loader->add_action( 'in_admin_header', Screens::class, 'remove_admin_notices', 20 );
	}
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_frontend_hooks() {

		$plugin_frontend = new Search_Filter\Frontend( $this->get_plugin_name(), $this->get_version() );
		$plugin_frontend->init();

		// Scripts & css.
		add_action( 'wp_print_scripts', array( Scripts::class, 'output_init_js' ), 0 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'enqueue_styles', 20 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'enqueue_scripts', 20 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'add_js_data', 21 );

		// Use a really low priority so that we load after other plugins, eg Elementor loads popups in
		// `wp_footer` and we want to load after them just in case they have S&F fields.
		add_action( 'wp_footer', 'Search_Filter\\Core\\SVG_Loader::output', 100 );
		add_action( 'wp_footer', array( $plugin_frontend, 'data' ), 100 );
	}

	/**
	 * Init the rest api class
	 *
	 * @return void
	 */
	private function load_rest_api() {
		$this->rest_api = new Rest_API();
	}

	/**
	 * Init plugin schema class (post types, taxonomies etc)
	 *
	 * @since    3.0.0
	 *
	 * @return void
	 */
	private function init_universal_classes() {
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     3.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     3.0.0
	 * @return    Search_Filter_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
