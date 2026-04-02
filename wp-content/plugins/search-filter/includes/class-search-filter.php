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

use Search_Filter\Fields\Field_Factory;
use Search_Filter\Rest_API;
use Search_Filter\Core\Dependants;
use Search_Filter\Options;

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
	 * @var      Rest_API    $rest_api
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

		$this->set_locale(); // Needs to go after load_dependencies.
		$this->load_rest_api();

		\Search_Filter\Compatibility::init();

		add_action( 'plugins_loaded', array( $this, 'init_dependencies' ), 1 );
	}

	/**
	 * Initialize plugin dependencies in the correct order.
	 */
	public function init_dependencies() {

		/**
		 * ORDER IS IMPORTANT.
		 */
		\Search_Filter\Core\Schema::init();

		// Init options to setup the table.
		\Search_Filter\Options::init();

		// Always hook update check in admin (before any blocking checks).
		// This ensures Free updates are visible even when blocked due to Pro version incompatibility.
		add_action( 'init', array( __CLASS__, 'init_updates' ), 20 );

		// Since 3.2.0, we need to check for if the pro plugin is installed and at the right version otherwise
		// some of the class changes will cause PHP errors.  We need to do this after the DB has init, but before
		// the field factory is registered.
		if ( Dependants::is_search_filter_pro_enabled() && ! Dependants::min_pro_version_supported() ) {
			// Add admin notice.
			add_action(
				'admin_notices',
				function () {

					echo '<div class="notice notice-error"><p>';
					printf(
						// translators: 1: Plugin name, 2: Search & Filter version, 3: Plugin name, 4: Minimum supported Pro version.
						esc_html__( 'Important: Your version of %1$s is is not compatible with Search & Filter %2$s. Please update %3$s to version %4$s or deactivate the pro plugin.', 'search-filter' ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter' ) . '</strong>',
						esc_html( SEARCH_FILTER_VERSION ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter' ) . '</strong>',
						esc_html( SEARCH_FILTER_MIN_PRO_VERSION_SUPPORTED )
					);
					echo '</p></div>';
				}
			);
			add_filter( 'search-filter/frontend/should_init', '__return_false' );
			return;
		}

		// Fields must be registered before settings (so they can register their own settings).
		Field_Factory::init();
		\Search_Filter\Theme_Styles::init();
		\Search_Filter\Core\CSS_Loader::init();
		\Search_Filter\Core\Upgrader::init();
		\Search_Filter\Components::init();

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

		\Search_Filter\Core\Cron::init();
		\Search_Filter\Debugger::init();
		\Search_Filter\Compatibility::register();

		\Search_Filter\Core\Schema::register();
		\Search_Filter\Core\Asset_Loader::init();

		// Preload commonly used options now that tables are set up.
		// This must happen after Schema::register() (which sets up tables) but before
		// the 'init' hook fires (when Features/Integrations access these options).
		if ( ! is_admin() ) {
			Options::preload();
		}

		// This matches the settings init hooks found in the above classes, so it
		// should be fired close to the last one (in Styles) has been fired.
		add_action( 'init', array( __CLASS__, 'after_register_settings' ), 2 );

		// Correctly load public / admin classes & hooks.
		if ( ( ! is_admin() ) || wp_doing_ajax() ) {
			// Init frontend class and hooks.
			\Search_Filter\Frontend::init();
		} elseif ( is_admin() ) {
			\Search_Filter\Admin::init();
		}
	}

	/**
	 * After the settings have been registered, emit our own init event.
	 *
	 * @return void
	 */
	public static function after_register_settings() {
		// Then trigger a generic ready hook for all settings.
		// TODO - this hook doesn't match our naming convention.
		do_action( 'search-filter/settings/init' );
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
	 * Check for plugin updates.
	 *
	 * @since 3.0.0
	 */
	public static function init_updates() {
		// Setup the updater.
		$edd_updater = new \Search_Filter\Core\Plugin_Updater(
			'https://license.searchandfilter.com',
			SEARCH_FILTER_BASE_FILE,
			array(
				'version' => SEARCH_FILTER_VERSION,
				'license' => 'search-filter-extension-free',
				'item_id' => 514539,
				'author'  => 'Search & Filter',
				'beta'    => false,
			)
		);
	}
}
