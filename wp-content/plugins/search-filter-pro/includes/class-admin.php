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
use Search_Filter\Core\Asset_Loader;
use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Core\Plugin_Installer;
use Search_Filter_Pro\Core\Upgrader\Upgrade_Status;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all Admin facing functionality
 */
class Admin {

	/**
	 * Init
	 */
	public static function init() {
		\Search_Filter_Pro\Fields::init();
		\Search_Filter_Pro\Queries::init();

		// Add actions on heartbeat.
		Heartbeat::init();

		// Scripts & css.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ), 11 ); // After Search & Filter plugin at 10.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 11 ); // After Search & Filter plugin at 10.

		// TODO - move this into the Gutenberg integration class.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );

		add_filter( 'search-filter/admin/screens/get_pages', array( __CLASS__, 'menu_pages' ) );
	}

	/**
	 * Register the assets for the admin area.
	 *
	 * @since 3.2.0
	 */
	public static function register_assets() {

		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		// Register the admin assets.
		$asset_configs = array(
			array(
				'name'   => 'search-filter-pro-admin',
				'script' => array(
					'src'          => SEARCH_FILTER_PRO_URL . 'assets/admin/app.js',
					'asset_path'   => SEARCH_FILTER_PRO_PATH . 'assets/admin/app.asset.php',
					'dependencies' => array( 'search-filter-frontend', 'search-filter-admin' ), // Additional dependencies.
					'footer'       => true,
				),
				'style'  => array(
					'src'          => SEARCH_FILTER_PRO_URL . 'assets/admin/app.css',
					'dependencies' => array( 'search-filter-frontend', 'search-filter-admin' ),
				),
			),
		);

		$assets = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );
	}

	/**
	 * Enqueue the assets for the admin area.
	 *
	 * @since 3.2.0
	 */
	public static function enqueue_assets() {
		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		// Enqueue the admin assets.
		Asset_Loader::enqueue( array( 'search-filter-pro-admin' ) );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * This is fired on our admin screens (parent plugin fires a do_action()) and our own screens.
	 *
	 * @since 3.0.0
	 */
	public static function enqueue_block_editor_assets() {
		$screens = new \Search_Filter\Admin\Screens();
		if ( $screens->is_search_filter_screen() ) {
			return;
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
	public static function menu_pages( $pages ) {
		// Remove the page entry referencing "pro".
		$pages = array_filter(
			$pages,
			function ( $page ) {
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

		/*
		$pages[] = (object) array(
			'title'   => __( 'Wizard', 'search-filter-pro' ),
			'section' => 'wizard',
		);
		*/
		return $pages;
	}

	/**
	 * Handle search filter actions.
	 *
	 * @since 3.0.0
	 */
	public static function search_filter_actions() {

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
				// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
				// $result = $plugin_installer->install_package_from_wp_org( 'search-filter' );
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
	public static function search_filter_is_legacy_version() {

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
					printf(
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
	public static function search_filter_missing_notice() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}

		$is_search_filter_installed = Dependencies::is_search_filter_installed();
		$is_search_filter_enabled   = Dependencies::is_search_filter_enabled();

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
					printf(
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
	public static function search_filter_outdated_notice() {

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
	public static function search_filter_outdated_recommended_notice() {

		// Only show on the dashboard and plugins screen.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;
		if ( $screen_name !== 'dashboard' && $screen_name !== 'plugins' ) {
			return;
		}

		$is_search_filter_enabled             = Dependencies::is_search_filter_enabled();
		$is_search_filter_recommended_version = Dependencies::is_search_filter_recommended_version();

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

	/**
	 * Display a notice if upgrades have failed.
	 *
	 * @since 3.2.0
	 */
	public static function upgrade_failure_notice() {
		$has_failures  = Upgrade_Status::has_failures();
		$has_suspended = Upgrade_Status::has_suspended();

		if ( ! $has_failures && ! $has_suspended ) {
			return;
		}

		// Only show on the dashboard, plugins screen, and S&F screens.
		$current_screen = \get_current_screen();
		$screen_name    = $current_screen->id;

		$allowed_screens = array( 'dashboard', 'plugins' );
		$is_sf_screen    = strpos( $screen_name, 'search-filter' ) !== false;

		if ( ! in_array( $screen_name, $allowed_screens, true ) && ! $is_sf_screen ) {
			return;
		}

		// Handle suspended upgrades (max retries reached).
		if ( $has_suspended ) {
			$suspended = Upgrade_Status::get_suspended();
			$versions  = array_keys( $suspended );
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %1$s: Search & Filter Pro plugin name, %2$s: comma-separated list of suspended versions */
						esc_html__( '%1$s: Database upgrade(s) suspended after maximum retry attempts for version(s): %2$s.', 'search-filter-pro' ),
						'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-pro' ) . '</strong>',
						'<strong>' . esc_html( implode( ', ', $versions ) ) . '</strong>'
					);
					?>
				</p>
				<p>
					<?php foreach ( $versions as $version ) : ?>
						<button type="button" class="button sf-retry-upgrade" data-version="<?php echo esc_attr( $version ); ?>">
							<?php
							printf(
								/* translators: %s: version number */
								esc_html__( 'Retry upgrade to %s', 'search-filter-pro' ),
								esc_html( $version )
							);
							?>
						</button>
					<?php endforeach; ?>
				</p>
			</div>
			<script>
			(function() {
				document.querySelectorAll('.sf-retry-upgrade').forEach(function(button) {
					button.addEventListener('click', function() {
						var version = this.getAttribute('data-version');
						this.disabled = true;
						this.textContent = '<?php echo esc_js( __( 'Retrying...', 'search-filter-pro' ) ); ?>';
						fetch('<?php echo esc_url( rest_url( 'search-filter-pro/v1/upgrader/retry' ) ); ?>', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
							},
							body: JSON.stringify({ version: version })
						}).then(function(response) {
							if (response.ok) {
								window.location.reload();
							} else {
								alert('<?php echo esc_js( __( 'Failed to reset upgrade status. Please try again.', 'search-filter-pro' ) ); ?>');
								button.disabled = false;
							}
						}).catch(function() {
							alert('<?php echo esc_js( __( 'Failed to reset upgrade status. Please try again.', 'search-filter-pro' ) ); ?>');
							button.disabled = false;
						});
					});
				});
			})();
			</script>
			<?php
			return;
		}

		// Handle regular failures (not yet suspended).
		$failed   = Upgrade_Status::get_failed();
		$versions = array_keys( $failed );
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %1$s: Search & Filter Pro plugin name, %2$s: comma-separated list of failed versions */
					esc_html__( '%1$s: Database upgrade(s) failed for version(s): %2$s. Please check the error logs or contact support.', 'search-filter-pro' ),
					'<strong>' . esc_html__( 'Search & Filter Pro', 'search-filter-pro' ) . '</strong>',
					'<strong>' . esc_html( implode( ', ', $versions ) ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
