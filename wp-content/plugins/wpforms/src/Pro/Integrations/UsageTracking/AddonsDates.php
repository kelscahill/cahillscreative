<?php

namespace WPForms\Pro\Integrations\UsageTracking;

/**
 * Addons Dates tracking functionality.
 *
 * Tracks installation, activation, and deactivation dates for WPForms addons.
 *
 * @since 1.10.0
 */
class AddonsDates {

	/**
	 * The option name used to store addon dates data.
	 *
	 * @since 1.10.0
	 */
	private const OPTION_NAME = 'wpforms_addons_dates';

	/**
	 * Cache for plugin data to avoid multiple get_plugin_data() calls.
	 *
	 * @since 1.10.0
	 *
	 * @var array
	 */
	private static $plugin_data_cache = [];

	/**
	 * Load an integration.
	 *
	 * @since 1.10.0
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.10.0
	 */
	private function hooks(): void {

		add_action( 'upgrader_process_complete', [ $this, 'track_installation' ], 10, 2 );
		add_action( 'activated_plugin', [ $this, 'track_activation' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'track_deactivation' ], 10, 2 );
		add_filter( 'wpforms_integrations_usage_tracking_usage_tracking_get_addons_dates', [ $this, 'filter_addons_dates' ] );
	}

	/**
	 * Filter addons dates data for usage tracking.
	 *
	 * @since 1.10.0
	 *
	 * @param array $addons_dates Addons dates data.
	 *
	 * @return array
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function filter_addons_dates( $addons_dates ): array {

		$addons_dates = (array) $addons_dates;

		return array_merge( $addons_dates, self::get_all_addons_dates() );
	}

	/**
	 * Track plugin installation via upgrader.
	 *
	 * @since 1.10.0
	 *
	 * @param object $upgrader Upgrader instance.
	 * @param array  $options  Installation options.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function track_installation( $upgrader, $options ): void {

		$options = (array) $options;

		// Only track plugin installations.
		if ( ! isset( $options['type'] ) || $options['type'] !== 'plugin' ) {
			return;
		}

		// Only track new installations, not updates.
		if ( ! isset( $options['action'] ) || $options['action'] !== 'install' ) {
			return;
		}

		$upgrader    = (object) $upgrader;
		$plugin_file = $this->get_plugin_file_from_upgrader( $upgrader );

		if ( empty( $plugin_file ) ) {
			return;
		}

		$this->track_addon_installation( $plugin_file );
	}

	/**
	 * Track plugin activation.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin       Plugin basename.
	 * @param bool   $network_wide Whether the plugin is being network-activated.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function track_activation( $plugin, $network_wide = false ): void {

		$plugin       = (string) $plugin;
		$network_wide = (bool) $network_wide;

		$this->track_addon_status_change( $plugin, $network_wide, 'active' );
	}

	/**
	 * Track plugin deactivation.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin       Plugin basename.
	 * @param bool   $network_wide Whether the plugin is being network-deactivated.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function track_deactivation( $plugin, $network_wide = false ): void {

		$plugin       = (string) $plugin;
		$network_wide = (bool) $network_wide;

		$this->track_addon_status_change( $plugin, $network_wide, 'inactive' );
	}

	/**
	 * Get a plugin file from an upgrader result.
	 *
	 * @since 1.10.0
	 *
	 * @param object $upgrader Upgrader instance.
	 *
	 * @return string Plugin file basename or empty string.
	 */
	private function get_plugin_file_from_upgrader( object $upgrader ): string {

		if ( ! isset( $upgrader->result ) || ! is_array( $upgrader->result ) ) {
			return '';
		}

		// Get the plugin directory name from an upgrader result.
		$plugin = $upgrader->result['destination_name'] ?? '';

		if ( empty( $plugin ) ) {
			return '';
		}

		// All WPForms addons follow the "addon-name / addon-name.php" structure.
		return $plugin . '/' . $plugin . '.php';
	}

	/**
	 * Track addon installation.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin Plugin basename.
	 */
	private function track_addon_installation( string $plugin ): void {

		$addon_data = $this->validate_and_get_addon_data( $plugin );

		if ( empty( $addon_data ) ) {
			return;
		}

		$addons_dates = self::get_all_addons_dates();
		$addon_slug   = $addon_data['slug'];

		// Only track if addon doesn't exist yet.
		if ( isset( $addons_dates[ $addon_slug ] ) ) {
			return;
		}

		$addons_dates[ $addon_slug ] = [
			'name'             => $addon_data['name'],
			'version'          => $addon_data['version'],
			'installed_date'   => time(),
			'activated_date'   => null,
			'deactivated_date' => null,
			'network_wide'     => 'no',
			'status'           => 'inactive',
		];

		self::save_addons_dates( $addons_dates );
	}

	/**
	 * Track addon status change (activation or deactivation).
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin       Plugin basename.
	 * @param bool   $network_wide Whether the plugin is being network-activated/deactivated.
	 * @param string $status       New status ('active' or 'inactive').
	 */
	private function track_addon_status_change( string $plugin, bool $network_wide, string $status ): void {

		$addon_data = $this->validate_and_get_addon_data( $plugin );

		if ( empty( $addon_data ) ) {
			return;
		}

		$addons_dates = self::get_all_addons_dates();
		$addon_slug   = $addon_data['slug'];
		$current_time = time();
		$existing     = $addons_dates[ $addon_slug ] ?? [];

		// Set installed_date only if not already set.
		$installed_date = $existing['installed_date'] ?? null;

		if ( empty( $installed_date ) && $status === 'active' ) {
			$installed_date = self::get_addon_installed_date( $addon_slug, $current_time );
		}

		$activated_date = $existing['activated_date'] ?? null;

		if ( $status === 'active' ) {
			$activated_date = $current_time;
		}

		$deactivated_date = $existing['deactivated_date'] ?? null;

		if ( $status === 'inactive' ) {
			$deactivated_date = $current_time;
		}

		$addons_dates[ $addon_slug ] = [
			'name'             => $addon_data['name'],
			'version'          => $addon_data['version'],
			'installed_date'   => $installed_date,
			'activated_date'   => $activated_date,
			'deactivated_date' => $deactivated_date,
			'network_wide'     => $network_wide ? 'yes' : 'no',
			'status'           => $status,
		];

		self::save_addons_dates( $addons_dates );
	}

	/**
	 * Validate plugin and get addon data.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin Plugin basename.
	 *
	 * @return array Addon data or empty array if validation fails.
	 */
	private function validate_and_get_addon_data( string $plugin ): array {

		if ( ! self::is_wpforms_addon( $plugin ) ) {
			return [];
		}

		return $this->get_addon_data_from_plugin( $plugin );
	}

	/**
	 * Get all addons dates data.
	 *
	 * @since 1.10.0
	 *
	 * @return array Addons dates data.
	 */
	public static function get_all_addons_dates(): array {

		$addons_dates = get_option( self::OPTION_NAME, [] );

		return is_array( $addons_dates ) ? $addons_dates : [];
	}

	/**
	 * Save addons dates data.
	 *
	 * @since 1.10.0
	 *
	 * @param array $addons_dates Addons dates data.
	 */
	public static function save_addons_dates( array $addons_dates ): void {

		update_option( self::OPTION_NAME, $addons_dates, false );
	}

	/**
	 * Check if a plugin is a WPForms addon.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin Plugin basename.
	 *
	 * @return bool True if WPForms addon, false otherwise.
	 */
	public static function is_wpforms_addon( string $plugin ): bool {

		// Check if a plugin starts with the 'wpforms-' prefix and is not the core plugin.
		if ( $plugin === 'wpforms/wpforms.php' || strpos( $plugin, 'wpforms-' ) !== 0 ) {
			return false;
		}

		// Verify the author is WPForms to exclude forks.
		$plugin_data = self::get_plugin_data( $plugin );

		return isset( $plugin_data['Author'] ) && $plugin_data['Author'] === 'WPForms';
	}

	/**
	 * Get addon data from plugin basename.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin Plugin basename (e.g., "wpforms-stripe/wpforms-stripe.php").
	 *
	 * @return array Addon data with slug, name, and version.
	 */
	private function get_addon_data_from_plugin( string $plugin ): array {

		$plugin_data = self::get_plugin_data( $plugin );

		if ( empty( $plugin_data ) ) {
			return [];
		}

		// Extract slug from the plugin basename (e.g., "wpforms-stripe/wpforms-stripe.php" -> "wpforms-stripe").
		$slug = dirname( $plugin );

		return [
			'slug'    => $slug,
			'name'    => $plugin_data['Name'] ?? '',
			'version' => $plugin_data['Version'] ?? '',
		];
	}

	/**
	 * Get plugin data for the given plugin basename.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin Plugin basename (e.g., "wpforms-stripe/wpforms-stripe.php").
	 *
	 * @return array Plugin data or empty array if a file doesn't exist.
	 */
	private static function get_plugin_data( string $plugin ): array {

		// Return cached data if available.
		if ( isset( self::$plugin_data_cache[ $plugin ] ) ) {
			return self::$plugin_data_cache[ $plugin ];
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;

		if ( ! file_exists( $plugin_file ) ) {
			self::$plugin_data_cache[ $plugin ] = [];

			return [];
		}

		self::ensure_plugin_functions();

		self::$plugin_data_cache[ $plugin ] = get_plugin_data( $plugin_file, false, false );

		return self::$plugin_data_cache[ $plugin ];
	}

	/**
	 * Get addon installed date from the versions option or fallback to the first release date.
	 *
	 * @since 1.10.0
	 *
	 * @param string $addon_slug    Addon slug (e.g., "wpforms-save-resume").
	 * @param int    $default_value Default value to return if the installed date is not found.
	 *
	 * @return int Installed date timestamp.
	 */
	public static function get_addon_installed_date( string $addon_slug, int $default_value ): int {

		// Convert slug from 'wpforms-addon-name' to 'wpforms_addon_name'.
		$addon_key       = str_replace( '-', '_', $addon_slug );
		$versions_option = $addon_key . '_versions';
		$addon_versions  = get_option( $versions_option, [] );

		if ( ! empty( $addon_versions ) && is_array( $addon_versions ) ) {
			// Filter out non-numeric and zero values explicitly.
			$valid_versions = array_filter(
				$addon_versions,
				static function ( $value ) {

					return is_numeric( $value ) && $value > 0;
				}
			);

			if ( ! empty( $valid_versions ) ) {
				return (int) min( $valid_versions );
			}
		}

		return $default_value;
	}

	/**
	 * Ensure WordPress plugin functions are available.
	 *
	 * @since 1.10.0
	 */
	public static function ensure_plugin_functions(): void {

		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
