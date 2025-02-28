<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Admin\Screens;
use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Core\Plugin_Installer;
use Search_Filter_Pro\Core\Scripts;
use Search_Filter\Util;
use Search_Filter_Pro\Core\Update_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all Admin facing functionality
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param  string $plugin_name The name of this plugin.
	 * @param  string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version /*, $screens */ ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Note: we should not attach hooks here or any other init methods
		// because we still load the admin class even when S&F Free is not installed.
	}
	/**
	 * Init
	 */
	public function init() {
		\Search_Filter_Pro\Fields::init();
		\Search_Filter_Pro\Queries::init();

		// Add actions on heartbeat.
		Heartbeat::init();

		// TODO - move this into the Gutenberg integration class.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Scripts & css.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_screen_assets' ) );

		add_filter( 'search-filter/admin/screens/get_pages', array( $this, 'menu_pages' ) );

		// Plugin updater.
		Update_Manager::add(
			array(
				'file'    => SEARCH_FILTER_PRO_BASE_FILE,
				'id'      => 526297,
				'version' => $this->version,
				'license' => 'search-filter-extension-free',
			)
		);

		// Handle base plugin updates via the update manager.
		if ( version_compare( SEARCH_FILTER_VERSION, '3.0.6-beta', '>=' ) ) {
			// Unhook the existing plugin updater.
			remove_action( 'admin_init', array( \Search_Filter\Admin::class, 'update_plugin' ), 20 );
			// Add it to the update manager instead.
			Update_Manager::add(
				array(
					'file'    => SEARCH_FILTER_BASE_FILE,
					'id'      => 514539,
					'version' => SEARCH_FILTER_VERSION,
					'license' => 'search-filter-extension-free',
				)
			);

		}
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * This is fired on our admin screens (parent plugin fires a do_action()) and our own screens.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_block_editor_assets() {
		$screens = new \Search_Filter\Admin\Screens();
		if ( $screens->is_search_filter_screen() ) {
			return;
		}

		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue admin screen assets.
	 *
	 * This is fired on our admin screens (parent plugin fires a do_action()) and our own screens.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_admin_screen_assets() {
		$screens = new \Search_Filter\Admin\Screens();
		if ( ! $screens->is_search_filter_screen() ) {
			return;
		}

		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {

		// Currently does not need 'search-filter-admin' because we're loading the admin script on block editor and admin screens.
		// Need seperate dependencies if we build a seperate admin script.

		$registered_styles = array(
			$this->plugin_name . '-admin' => array(
				'src'     => Scripts::get_admin_assets_url() . 'css/admin/app.css',
				// 'deps'    => array( 'search-filter-admin', 'wp-components' ),
				'deps'    => array( 'search-filter', 'wp-components' ),
				'version' => $this->version,
				'media'   => 'all',
			),
		);

		$registered_styles = apply_filters( 'search-filter-pro/admin/register_styles', $registered_styles );

		foreach ( $registered_styles as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
		}

		$enqueued_styles = array();

		foreach ( $registered_styles as $handle => $args ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
				$enqueued_styles[] = $handle;
			}
		}

		$enqueued_styles = apply_filters( 'search-filter-pro/admin/enqueue_styles', $enqueued_styles );

		foreach ( $enqueued_styles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {
		
		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		$registered_scripts = array(
			'search-filter-pro-admin' => array(
				'src'     => Scripts::get_admin_assets_url() . 'js/admin/app.js',
				// Currently does not need 'search-filter-admin' because we're loading the admin script on block editor and admin screens.
				// Need seperate dependencies if we build a seperate admin script.
				'deps'    => array( 'search-filter', 'wp-element', 'wp-components', 'wp-date', 'wp-compose', 'wp-data', 'wp-editor', 'wp-api-fetch', 'wp-dom-ready' ),
				'version' => $this->version,
				'footer'  => true,
			),
		);
		$registered_scripts = apply_filters( 'search-filter-pro/admin/register_scripts', $registered_scripts );

		foreach ( $registered_scripts as $handle => $args ) {
			wp_register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
		}

		$enqueued_scripts = array_keys( $registered_scripts );
		$enqueued_scripts = apply_filters( 'search-filter-pro/admin/enqueue_scripts', $enqueued_scripts );

		foreach ( $enqueued_scripts as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_enqueue_script( $handle );
			}
		}
	}

	/**
	 * Add the menu pages.
	 *
	 * @since 3.0.0
	 *
	 * @param array $pages    The pages to add.
	 * @return array    The pages.
	 */
	public function menu_pages( $pages ) {
		// Remove the page entry referencing "pro".
		$pages = array_filter(
			$pages,
			function( $page ) {
				// Not all pages will have a section.
				if ( ! property_exists( $page, 'section' ) ) {
					return true;
				}
				// If there is a section, remove the one that is "pro".
				if ( $page->section === 'pro' ) {
					return false;
				}
				return true;
			}
		);
		return $pages;
	}

	/**
	 * Handle search filter actions.
	 *
	 * @since 3.0.0
	 */
	public function search_filter_actions() {

		if ( ! isset( $_REQUEST['search_filter_action'] ) ) {
			return;
		}
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$action  = sanitize_key( wp_unslash( $_REQUEST['search_filter_action'] ) );
		$nonce   = sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) );
		$referer = wp_get_referer() ? wp_get_referer() : admin_url();

		$plugin_file = 'search-filter/search-filter.php';

		if ( $action === 'install-plugin' ) {
			if ( current_user_can( 'install_plugins' ) && wp_verify_nonce( $nonce, 'install-plugin_search-filter/search-filter.php' ) ) {
				$plugin_installer = new Plugin_Installer();

				// Free S&F from searchandfilter.com.
				$result = $plugin_installer->install_package_from_api( 514539 );

				// TODO - change to .org version once the free version goes live on .org.
				// Installing from wp.org.
				// $result      = $plugin_installer->install_package_from_wp_org( 'search-filter' );
				if ( $result['status'] === 'success' && current_user_can( 'activate_plugins' ) ) {
					activate_plugin( $plugin_file );
				}
			}
		} elseif ( $action === 'activate-plugin' ) {
			if ( current_user_can( 'activate_plugins' ) && wp_verify_nonce( $nonce, 'activate-plugin_search-filter/search-filter.php' ) ) {
				activate_plugin( $plugin_file );
			}
		}
		// Redirect back to the page that initiated the action.
		wp_safe_redirect( $referer );
		exit;
	}

	/**
	 * Display a notice if the free version is outdated or missing or not activated.
	 *
	 * @since 3.0.0
	 */
	public function search_filter_is_legacy_version() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
					echo sprintf(
						// Translators: 1: Search & Filter.
						esc_html__( 'The %1$s base plugin needs to be updated.', 'search-filter-pro' ),
						'<strong>' . esc_html__( 'Search & Filter', 'search-filter-pro' ) . '</strong>'
					);
				?>
				
			</p>
			<p>
				<a href="https://searchandfilter.com/version-3/" target="_blank">
					<?php
						echo esc_html__( 'Download it here.', 'search-filter-pro' );
					?>
				</a>
			</p>
		</div>
		<?php
	}
	/**
	 * Display a notice if the free version is outdated or missing or not activated.
	 *
	 * @since 3.0.0
	 */
	public function search_filter_missing_notice() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}

		$is_search_filter_installed        = Dependencies::is_search_filter_installed();
		$is_search_filter_enabled          = Dependencies::is_search_filter_enabled();

		global $pagenow;
		$actions_url = admin_url( 'index.php' );
		// We want to do 3 things here.
		// - If the Search & Filter plugin is not installed, we want to show an install link.
		// - If its installed but not activated, we want to show an activate link.
		// - If the verion is outdated, show a notice about which version is required.
		?>
		<div class="notice notice-warning">
			<p>
				<?php
					echo sprintf(
						// Translators: 1: Search & Filter Pro 2: Search & Filter.
						esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'search-filter-pro' ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html__( 'Search & Filter', 'search-filter-pro' ) . '</strong>'
					);
				?>
			</p>
			<?php
			if ( ! $is_search_filter_installed ) {
				if ( current_user_can( 'install_plugins' ) ) {
					?>
					<p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'search_filter_action', 'install-plugin', $actions_url ), 'install-plugin_search-filter/search-filter.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Install and activate Search & Filter', 'search-filter-pro' ); ?></a></p>
					<?php
				} else {
					?>
					<p><em><?php esc_html_e( 'Please contact your site administrator to install Search & Filter.', 'search-filter-pro' ); ?></em></p>
					<?php
				}
			} elseif ( ! $is_search_filter_enabled ) {
				if ( current_user_can( 'activate_plugins' ) ) {
					?>
					<p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'search_filter_action', 'activate-plugin', $actions_url ), 'activate-plugin_search-filter/search-filter.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Activate Search & Filter', 'search-filter-pro' ); ?></a></p>
					<?php
				} else {
					?>
					<p><em><?php esc_html_e( 'Please contact your site administrator to activate Search & Filter.', 'search-filter-pro' ); ?></em></p>
					<?php
				}
			}
			?>
		</div>
		<?php
	}
	/**
	 * Display a notice if the free version is outdated or missing or not activated.
	 *
	 * @since 3.0.0
	 */
	public function search_filter_outdated_notice() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}

		$is_search_filter_enabled          = Dependencies::is_search_filter_enabled();
		$is_search_filter_required_version = Dependencies::is_search_filter_required_version();

		global $pagenow;
		// If the verion is outdated, show a notice about which version is required.
		if ( $is_search_filter_enabled && ! $is_search_filter_required_version ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %1$s: Search & Filter Pro plugin name, %2$s: Search & Filter Pro plugin name, %3$s: Search & Filter plugin version */
						esc_html__( 'The %1$s plugin requires %2$s version %3$s or higher.', 'search-filter-pro' ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html__( 'Search & Filter', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html( SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION ) . '</strong>'
					);
					?>
				</p>
				<?php
				if ( $pagenow !== 'plugins.php' ) {
					?>
					<p><a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to plugins screen', 'search-filter-pro' ); ?></a></p>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
	/**
	 * Display a notice if the free version is outdated or missing or not activated.
	 *
	 * @since 3.0.0
	 */
	public function search_filter_outdated_recommended_notice() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}

		$is_search_filter_enabled          = Dependencies::is_search_filter_enabled();
		$is_search_filter_recommended_version = Dependencies::is_search_filter_recommended_version();

		global $pagenow;
		// If the verion is outdated, show a notice about which version is required.
		if ( $is_search_filter_enabled && ! $is_search_filter_recommended_version ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %1$s: Search & Filter Pro plugin name, %2$s: Search & Filter Pro plugin version */
						esc_html__( 'The %1$s plugin recommends %2$s version %3$s or higher.', 'search-filter-pro' ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html__( 'Search & Filter', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html( SEARCH_FILTER_PRO_RECOMMENDED_BASE_VERSION ) . '</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
