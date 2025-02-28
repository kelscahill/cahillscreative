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
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Search_Filter_Pro\Compatibility;
use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Core\Dependencies\Stubs;
use Search_Filter_Pro\Core\Extensions;
use Search_Filter_Pro\Core\License_Server;
use Search_Filter_Pro\Core\Remote_Notices;
use Search_Filter_Pro\Core\Update_Manager;
use Search_Filter_Pro\Core\Upgrader;
use Search_Filter_Pro\Features;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Integrations;
use Search_Filter_Pro\Rest_API;
use Search_Filter_Pro\Task_Runner;
use Search_Filter_Pro\Util;

/**
 * The main entry point for the plugin
 *
 * Loads all hooks and instantiates the classes required
 * to both frontend and admin.
 *
 * @since 3.0.0
 */
class Search_Filter_Pro {
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
	 * Strings for messaging.
	 *
	 * @since    3.0.0
	 *
	 * @var      array    $strings
	 */
	private $strings = array();

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'search-filter-pro';
		$this->version     = SEARCH_FILTER_PRO_VERSION;
		$this->strings     = array(
			'outdated_version' => 'Pro features cannot be enabled because the free version is outdated.',
			'outdated_recommended_version' => 'The Search & Filter plugin is outdated, upgrade to the latest version for full functionality.',
		);

		// Needs priority of 0 to load before the free plugin, so we can registers
		// certain hooks in time.
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
	}

	/**
	 * Init the plugin on plugins_loaded.
	 */
	public function init() {

		$this->set_locale();
		Dependencies::init();
		// Check to see if S&F legacy version from .org is installed - bail otherwise we'll get
		// a fatal error.
		if ( Dependencies::has_legacy_base_plugin() ) {
			// Add notice to WP admin screens.
			$plugin_admin = new \Search_Filter_Pro\Admin( $this->get_plugin_name(), $this->get_version() );
			add_action( 'admin_notices', array( $plugin_admin, 'search_filter_is_legacy_version' ) );
			return;
		}

		if ( ! Dependencies::is_search_filter_enabled() ) {
			if ( Util::is_admin_only() ) {
				$plugin_admin = new \Search_Filter_Pro\Admin( $this->get_plugin_name(), $this->get_version() );

				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( $plugin_admin, 'search_filter_missing_notice' ) );

				// Add actions to update / activate the plugin.
				add_action( 'admin_init', array( $plugin_admin, 'search_filter_actions' ) );
			}
			// Return early if S&F base plugin does not meet the criteria.
			return;
		}

		Compatibility::init();

		// Check for the existence of the main plugin, and return early if it doesn't exist or match the required version.
		if ( ! Dependencies::is_search_filter_required_version() ) {

			// Add admin notices to notify the user that the main plugin is missing or outdated.
			if ( Util::is_admin_only() ) {
				$plugin_admin = new \Search_Filter_Pro\Admin( $this->get_plugin_name(), $this->get_version() );
				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( $plugin_admin, 'search_filter_outdated_notice' ) );
				
				// Display admin notice on our own screens.
				add_action( 'search-filter/core/notices/get_notices', array( $this, 'add_outdated_recommended_notice' ) );
			}

			// Log the error.
			Util::error_log( $this->strings['outdated_version'], 'error' );

			// Load the stubs to prevent fatal errors when upgrading and the base version is still beta.
			// TODO - remove after 3.1.0 release.
			if ( version_compare( SEARCH_FILTER_VERSION, '3.0.2', '<' ) ) {
				Stubs::init();
			}
			// Return early if S&F base plugin does not meet the criteria.
			return;
		}
		if ( ! Dependencies::is_search_filter_recommended_version() ) {

			// Add admin notices to notify the user that the main plugin is missing or outdated.
			if ( Util::is_admin_only() ) {
				$plugin_admin = new \Search_Filter_Pro\Admin( $this->get_plugin_name(), $this->get_version() );
				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( $plugin_admin, 'search_filter_outdated_recommended_notice' ) );
				
				// Display admin notice on our own screens.
				add_action( 'search-filter/core/notices/get_notices', array( $this, 'add_outdated_recommended_notice' ) );
			}

			// Log the error.
			Util::error_log( $this->strings['outdated_recommended_version'], 'error' );
		}

		$this->init_dependencies();

		// Correctly load public / admin classes & hooks.
		if ( ( ! is_admin() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->init_frontend();
		} elseif ( is_admin() ) {
			$this->init_admin();
		}

		// Hooks that are shared across both frontend and backend.
		$this->define_global_hooks();
		$this->load_rest_api();

	}

	/**
	 * Add a notice to the admin screen if the free version is outdated.
	 */
	public function add_outdated_notice() {
		// Add notice to our admin screen.
		// Note: do not add this class via a user directive, because the base plugin might not be enabled.
		$manage_plugins_link = sprintf( '<a href="%s">%s</a>.', esc_url( admin_url( 'update-core.php?force-check=1' ) ), esc_html__( 'Check for updates', 'search-filter-pro' ) );
		\Search_Filter\Core\Notices::add_notice( $this->strings['outdated_version'] . ' ' . $manage_plugins_link, 'warning', 'search-filter-pro-missing-required-version' );

	}
	/**
	 * Add a notice to the admin screen if the free version recommendation is outdated.
	 */
	public function add_outdated_recommended_notice() {
		// Add notice to our admin screen.
		// Note: do not add this class via a user directive, because the base plugin might not be enabled.
		$manage_plugins_link = sprintf( '<a href="%s">%s</a>.', esc_url( admin_url( 'update-core.php?force-check=1' ) ), esc_html__( 'Check for updates', 'search-filter-pro' ) );
		\Search_Filter\Core\Notices::add_notice( $this->strings['outdated_recommended_version'] . ' ' . $manage_plugins_link, 'warning', 'search-filter-pro-missing-recommended-version' );

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
		$plugin_i18n = new \Search_Filter_Pro\Core\I18n();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function init_admin() {
		$plugin_admin = new \Search_Filter_Pro\Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin->init();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function init_frontend() {
		// Stub: once we load assets seperately, we'll need to load the frontend assets here.
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
	 */
	private function init_dependencies() {
		$schema = new Search_Filter_Pro\Core\Schema();
		$schema->init();

		Integrations::init();
		Features::init();
		Task_Runner::init(); // Only used to register the test endpoint.
		Indexer::init();

		Upgrader::init();
		Update_Manager::init();
		License_Server::init();
		Extensions::init();
		Remote_Notices::init();
	}

	/**
	 * Define hooks to be triggered in both frontend and admin.
	 *
	 * @since    3.0.0
	 */
	private function define_global_hooks() {
		// Although the register_scripts belong to the frontend, we load the frontend in admin so
		// need to attach this in admin screens too.
		$plugin_frontend = new Search_Filter_Pro\Frontend();
		add_filter( 'search-filter/frontend/register_scripts', array( $plugin_frontend, 'update_scripts' ), 10 );
		add_filter( 'search-filter/frontend/register_styles', array( $plugin_frontend, 'update_styles' ), 10 );
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
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
