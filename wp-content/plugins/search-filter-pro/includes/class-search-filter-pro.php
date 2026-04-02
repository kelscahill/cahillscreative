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
use Search_Filter_Pro\Core\Schema;
use Search_Filter_Pro\Core\Update_Manager;
use Search_Filter_Pro\Core\Upgrader;
use Search_Filter_Pro\Database\Query_Optimizer;
use Search_Filter_Pro\Features;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Integrations;
use Search_Filter_Pro\Rest_API;
use Search_Filter_Pro\Task_Runner;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Cache;

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
			'outdated_version'             => 'Pro features cannot be enabled because the free version is outdated.',
			'outdated_recommended_version' => 'The Search & Filter plugin is outdated, upgrade to the latest version for full functionality.',
		);

		// Needs priority of 0 to load before the free plugin, so we can register certain hooks in time.
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		/*
		 * Always register updates outside of the normal setup flow to ensures Pro + Free
		 * updates are available even when plugin loading is blocked due to version incompatibility.
		 *
		 * Must be priority 2 to run after free plugin hook (priority 1).
		 */
		add_action( 'plugins_loaded', array( $this, 'init_updates' ), 2 );

		// Our extensions init their updates on priority 9 so we need to override them on 10.
		add_action( 'plugins_loaded', array( $this, 'init_legacy_extension_updates' ), 10 );
	}

	/**
	 * Init the plugin on plugins_loaded.
	 */
	public function init(): void {

		$this->set_locale();

		Dependencies::init();

		// Check to see if S&F legacy version from .org is installed - bail otherwise we'll get
		// a fatal error.
		if ( Dependencies::has_legacy_base_plugin() ) {
			// Add notice to WP admin screens.
			add_action( 'admin_notices', array( \Search_Filter_Pro\Admin::class, 'search_filter_is_legacy_version' ) );
			return;
		}

		if ( ! Dependencies::is_search_filter_enabled() ) {
			if ( Util::is_admin_only() ) {

				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( \Search_Filter_Pro\Admin::class, 'search_filter_missing_notice' ) );

				// Add actions to update / activate the plugin.
				add_action( 'admin_init', array( \Search_Filter_Pro\Admin::class, 'search_filter_actions' ) );
			}
			// Return early if S&F base plugin does not meet the criteria.
			return;
		}

		Compatibility::init();

		// Check for the existence of the main plugin, and return early if it doesn't exist or match the required version.
		if ( ! Dependencies::is_search_filter_required_version() ) {

			// Add admin notices to notify the user that the main plugin is missing or outdated.
			if ( Util::is_admin_only() ) {

				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( \Search_Filter_Pro\Admin::class, 'search_filter_outdated_notice' ) );

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
				// Add notice to WP admin screens.
				add_action( 'admin_notices', array( \Search_Filter_Pro\Admin::class, 'search_filter_outdated_recommended_notice' ) );

				// Display admin notice on our own screens.
				add_action( 'search-filter/core/notices/get_notices', array( $this, 'add_outdated_recommended_notice' ) );
			}

			// Log the error.
			Util::error_log( $this->strings['outdated_recommended_version'], 'error' );
		}

		$this->init_dependencies();

		// Correctly load public / admin classes & hooks.
		if ( ( ! is_admin() ) || wp_doing_ajax() ) {
			\Search_Filter_Pro\Frontend::init();
		} elseif ( is_admin() ) {
			\Search_Filter_Pro\Admin::init();

			// Show notice if any upgrades have failed.
			add_action( 'admin_notices', array( \Search_Filter_Pro\Admin::class, 'upgrade_failure_notice' ) );
		}

		// Hooks that are shared across both frontend and backend.
		$this->define_global_hooks();
		$this->load_rest_api();
	}

	/**
	 * Init the legacy extension updates.
	 *
	 * @return void
	 */
	public function init_legacy_extension_updates() {
		Update_Manager::add_legacy_extension_updates();
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

		// Run any pre-registration setup here.
		Schema::init();

		Integrations::init();
		Features::init();
		Task_Runner::init(); // Register the test background process endpoint, the cron check, and the tables.
		Indexer::init();
		Cache::init();

		// Initialize the query optimizer for large post__in queries.
		$query_optimizer = Query_Optimizer::get_instance();
		$query_optimizer->init();

		Upgrader::init();
		License_Server::init();
		Extensions::init();
		Remote_Notices::init();

		// Classes register  their own tables so fire our Schema register hook
		// after all classes have had a chance to hook into it.
		Schema::register();

		// Although the register_assets belongs to the frontend, we load the frontend in
		// admin so filter this everywhere.
		add_filter( 'search-filter/frontend/register_assets', array( Search_Filter_Pro\Frontend::class, 'update_assets' ), 2 );

		// Register the Pro components.
		add_action( 'search-filter/components/init', array( Search_Filter_Pro\Components::class, 'init' ), 2 );
	}

	/**
	 * Define hooks to be triggered in both frontend and admin.
	 *
	 * @since    3.0.0
	 * @return void
	 */
	private function define_global_hooks() {
	}

	/**
	 * Initialize all plugin updates.
	 *
	 * Called before any blocking checks to ensure updates are always visible
	 * even when Pro or Free is blocked due to version incompatibility.
	 *
	 * @since 3.2.0
	 */
	public function init_updates() {

		// Init the update manager.
		Update_Manager::init();

		$subscribe_to_beta_versions = $this->get_option_direct( 'advanced-features', 'subscribeToBetaVersions' );
		$beta                       = $subscribe_to_beta_versions === 'yes';

		// Always add Pro.
		Update_Manager::add(
			array(
				'file'    => SEARCH_FILTER_PRO_BASE_FILE,
				'id'      => 526297,
				'version' => SEARCH_FILTER_PRO_VERSION,
				'license' => 'search-filter-extension-free',
				'beta'    => $beta,
			)
		);

		// Check to see if the base v3 plugin is enable, then swap out the plugin updating strategy.
		if ( ! Dependencies::has_legacy_base_plugin() && Dependencies::is_search_filter_enabled() ) {
			// Remove Free's native plugin updater hook since we handle it via Update_Manager.
			if ( version_compare( SEARCH_FILTER_VERSION, '3.0.6-beta', '>=' ) ) {

				// Remove from pre 3.2.0 - uses the `admin_init` hook.
				if ( method_exists( \Search_Filter\Admin::class, 'update_plugin' ) ) {
					remove_action( 'admin_init', array( \Search_Filter\Admin::class, 'update_plugin' ), 20 );
				}

				// Remove from 3.2.0 and higher - uses init hook.
				// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- phpstan-ignore syntax requires parenthetical reason.
				// @phpstan-ignore function.alreadyNarrowedType (base plugin could be outdated during upgrade)
				if ( method_exists( \Search_Filter::class, 'init_updates' ) ) {
					remove_action( 'init', array( \Search_Filter::class, 'init_updates' ), 20 );
				}

				// Now handle via the update manager instead.
				Update_Manager::add(
					array(
						'file'    => SEARCH_FILTER_BASE_FILE,
						'id'      => 514539,
						'version' => SEARCH_FILTER_VERSION,
						'license' => 'search-filter-extension-free',
						'beta'    => $beta,
					)
				);
			}
		}
	}

	/**
	 * Get an option value directly from database.
	 *
	 * Uses raw DB query to avoid dependency on Free's Options class,
	 * which may not be available when Pro is blocked.
	 *
	 * @since 3.2.0
	 *
	 * @param string $option_group_name The option group name.
	 * @param string $option_name       The option name within the group.
	 * @return mixed The option value, or null if not found/table missing.
	 */
	private function get_option_direct( $option_group_name, $option_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'search_filter_options';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return null;
		}

		// Check advancedFeatures enabled.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option = $wpdb->get_var( $wpdb->prepare( 'SELECT value FROM %i WHERE name = %s', $table_name, $option_group_name ) );
		if ( ! $option ) {
			return null;
		}
		$option = json_decode( $option, true );
		if ( ! isset( $option[ $option_name ] ) ) {
			return null;
		}

		return $option[ $option_name ];
	}
}
