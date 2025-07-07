<?php

namespace WPForms\Pro\LicenseApi;

/**
 * License api plugin update cache.
 *
 * @see LicenseApiCache
 *
 * @since 1.8.7
 */
class PluginUpdateCache extends LicenseApiCache {

	/**
	 * Encrypt cached file.
	 *
	 * @since 1.8.7
	 */
	protected const ENCRYPT = true;

	/**
	 * Expirable URL key.
	 *
	 * @since 1.8.7
	 *
	 * @var string|bool
	 */
	protected $expirable_url_key = 'package';

	/**
	 * Constructor.
	 *
	 * @since 1.8.7
	 */
	public function __construct() {

		$this->plugin_slug = 'wpforms-pro';

		/**
		 * Filter to unify updates or not.
		 *
		 * This filter allows customization of the unification behavior of updates.
		 * By default, updates are unified.
		 *
		 * @since 1.9.4
		 *
		 * @param bool $unify_updates Whether to unify updates or not. Default is true.
		 */
		$this->type = apply_filters( 'wpforms_pro_license_api_update_cache_unify_updates', true ) ? 'plugin-updates' : 'plugin-update';
	}

	/**
	 * Get data from cache or from API call by slug.
	 *
	 * @since 1.9.4
	 *
	 * @param string $slug Plugin/addon slug.
	 *
	 * @return array
	 */
	public function get_by_slug( string $slug ): array {

		$result = $this->get();

		if ( $this->type === 'plugin-updates' ) {
			return $result[ $slug ] ?? [];
		}

		return $slug === 'wpforms' ? $result : [];
	}

	/**
	 * Setup the query arguments for the updater plugins.
	 *
	 * This method extends the parent setup method and adds the list of installed plugins
	 * to the query arguments for the TGM updater.
	 *
	 * @since 1.9.4
	 *
	 * @return array The setup array with the added query arguments.
	 */
	protected function setup(): array {

		$setup = parent::setup();

		if ( $this->type === 'plugin-updates' ) {
			$setup['query_args']['tgm-updater-plugins'] = $this->get_installed_plugins_list();

			unset( $setup['query_args']['tgm-updater-plugin'] );
		}

		return $setup;
	}

	/**
	 * Retrieve updates for WPForms and its add-ons.
	 *
	 * Checks if updates are already fetched,
	 * or if a valid license exists, fetches them from the remote server.
	 *
	 * @since 1.9.4
	 *
	 * @return object|false An object with update details, or an empty array if no updates are found.
	 */
	private function get_installed_plugins_list(): string {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array_keys( get_plugins() );

		$plugins = array_filter( $plugins, [ $this, 'is_wpforms_addon' ] );

		$plugin_slugs = array_map(
			function ( $plugin_file ) {
				return explode( '/', $plugin_file, 2 )[0];
			},
			$plugins
		);

		$plugin_slugs[] = 'wpforms';

		return implode( ',', $plugin_slugs );
	}

	/**
	 * Check whether a plugin is a wpforms addon.
	 *
	 * @since 1.9.4
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins' directory.
	 *
	 * @return bool
	 */
	private function is_wpforms_addon( string $plugin ): bool {

		if ( strpos( $plugin, 'wpforms-' ) !== 0 ) {
			// No more actions for general plugin.
			return false;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/**
		 * There are some forks of our plugins having the 'wpforms-' prefix.
		 * We have to check the Author name in the plugin header.
		 */
		$plugin_data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		$plugin_author = isset( $plugin_data['Author'] ) ? strtolower( $plugin_data['AuthorName'] ) : '';

		// No more actions on forks.
		return $plugin_author === 'wpforms';
	}

	/**
	 * Initialize.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		parent::init();

		// If this is GET force-check=1 set, then invalidate the cache.
		// We do not check nonce here, as this GET request should be available from the frontend by design.
		// This request is needed for support purposes also and does not lead to any security issues.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$force_check = isset( $_GET['force-check'] ) ? absint( $_GET['force-check'] ) : 0;

		if ( $force_check === 1 && current_user_can( 'update_plugins' ) ) {
			$this->invalidate_cache();
		}
	}
}
