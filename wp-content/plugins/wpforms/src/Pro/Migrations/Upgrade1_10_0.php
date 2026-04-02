<?php

namespace WPForms\Pro\Migrations;

use WPForms\Pro\Integrations\UsageTracking\AddonsDates;
use WPForms\Integrations\PayPalCommerce\Helpers;
use WPForms\Integrations\PayPalCommerce\Connection;
use WPForms\Integrations\PayPalCommerce\PaymentMethods\ApplePay\ApplePay;
use WPForms\Migrations\UpgradeBase;

/**
 * Class upgrade for the 1.10.0 release.
 *
 * @since 1.10.0
 *
 * @noinspection PhpUnused
 */
class Upgrade1_10_0 extends UpgradeBase {

	/**
	 * Release date.
	 *
	 * @since 1.10.0
	 */
	private const RELEASE_DATE = '2026-03-17';

	/**
	 * Run upgrade.
	 *
	 * @since 1.10.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null - upgrade started but not yet finished (background task).
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function run() {

		$this->run_addons_dates_migration();
		$this->run_apple_pay_domain_migration();

		return true;
	}

	/**
	 * Populate the wpforms_addons_dates option with data from existing addons.
	 *
	 * @since 1.10.0
	 */
	private function run_addons_dates_migration(): void {

		// Check if the option has already had data to avoid overwriting.
		$existing_data = AddonsDates::get_all_addons_dates();

		if ( ! empty( $existing_data ) ) {
			return;
		}

		// Ensure WordPress plugin functions are available.
		AddonsDates::ensure_plugin_functions();

		// Get all installed plugins.
		$all_plugins = get_plugins();

		if ( empty( $all_plugins ) ) {
			return;
		}

		$addons_dates = [];

		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			$addon_data = $this->process_plugin( $plugin_path, $plugin_data );

			if ( ! empty( $addon_data ) ) {
				$addons_dates[ $addon_data['slug'] ] = $addon_data['data'];
			}
		}

		if ( ! empty( $addons_dates ) ) {
			AddonsDates::save_addons_dates( $addons_dates );
		}
	}

	/**
	 * Process a single plugin and return addon data if it's a WPForms addon.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin_path Plugin path (e.g., "wpforms-stripe/wpforms-stripe.php").
	 * @param array  $plugin_data Plugin data from get_plugins().
	 *
	 * @return array Addon slug and data, or empty array if invalid.
	 */
	private function process_plugin( string $plugin_path, array $plugin_data ): array {

		// Check if it's a WPForms addon.
		if ( ! AddonsDates::is_wpforms_addon( $plugin_path ) ) {
			return [];
		}

		// Get addon slug from the plugin path.
		$addon_slug = dirname( $plugin_path );

		// Get installed date from the versions option.
		// Use a release date as a fallback in case the versions option is missing.
		// In this case, we will know that the addon was installed before this migration was introduced.
		$installed_date = AddonsDates::get_addon_installed_date( $addon_slug, strtotime( self::RELEASE_DATE ) );

		if ( ! $installed_date ) {
			return [];
		}

		// Check if addon is currently active.
		$is_active = $this->is_plugin_active( $plugin_path );

		return [
			'slug' => $addon_slug,
			'data' => [
				'name'             => $plugin_data['Name'] ?? '',
				'version'          => $plugin_data['Version'] ?? '',
				'installed_date'   => $installed_date,
				'activated_date'   => $is_active ? $installed_date : null,
				'deactivated_date' => null,
				'network_wide'     => is_multisite() && is_plugin_active_for_network( $plugin_path ) ? 'yes' : 'no',
				'status'           => $is_active ? 'active' : 'inactive',
			],
		];
	}

	/**
	 * Check if a plugin is currently active.
	 *
	 * @since 1.10.0
	 *
	 * @param string $plugin_path Plugin path (e.g., "wpforms-stripe/wpforms-stripe.php").
	 *
	 * @return bool True if active, false otherwise.
	 */
	private function is_plugin_active( string $plugin_path ): bool {

		AddonsDates::ensure_plugin_functions();

		return is_plugin_active( $plugin_path );
	}

	/**
	 * Run migration for Apple Pay domain registration.
	 *
	 * Creates the apple-developer-merchantid-domain-association file in the .well-known
	 * directory if Apple Pay is allowed to load and the PayPal Commerce integration is active.
	 * Automatically registers the domain on PayPal.
	 *
	 * @since 1.10.0
	 */
	private function run_apple_pay_domain_migration(): void {

		$apple_pay = new ApplePay();

		if ( ! $apple_pay->is_allowed_load() ) {
			return;
		}

		$connection = Connection::get();
		$mode       = Helpers::get_mode();

		$apple_pay->get_domain_manager()->after_admin_connect( $connection, $mode );
	}
}
