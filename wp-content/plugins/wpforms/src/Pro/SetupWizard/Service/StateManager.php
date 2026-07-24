<?php

namespace WPForms\Pro\SetupWizard\Service;

use WPForms\Helpers\Transient;
use WPForms\Integrations\LiteConnect\Integration;
use WPForms\Pro\Integrations\LiteConnect\ImportEntriesTask;
use WPForms\SetupWizard\Service\StateManager as BaseStateManager;

/**
 * Setup Wizard state manager (Pro).
 *
 * Adds the Lite Connect entries restore on top of the shared state handling:
 * a truthy `lite_connect.consent` consent flag sent by the SPA through
 * `/update` schedules the background import of entries collected on Lite.
 *
 * @since 2.0.0
 */
class StateManager extends BaseStateManager {

	/**
	 * Replace the wizard_settings blob with the SPA's latest snapshot.
	 *
	 * On top of the base persistence, the one key the Pro manager does read is
	 * `lite_connect.consent`: a truthy value asynchronously kicks off
	 * the Lite Connect entries restore
	 * (see {@see self::trigger_lite_connect_restore()}).
	 *
	 * @since 2.0.0
	 *
	 * @param array $wizard_settings Wizard-owned state to persist verbatim.
	 *
	 * @return array Refreshed full state.
	 */
	public function save_wizard_settings( array $wizard_settings ): array {

		$state = parent::save_wizard_settings( $wizard_settings );

		if ( ! empty( $wizard_settings['lite_connect']['consent'] ) ) {
			$this->trigger_lite_connect_restore();
		}

		return $state;
	}

	/**
	 * Hydrate payload for the SPA's `/hydrate` call.
	 *
	 * Adds the `addons` key on top of the shared payload: the plugin file of
	 * every installed and active WPForms addon. Addons are a Pro-only concept,
	 * so the base manager omits the key entirely.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_hydrate_payload(): array {

		$payload = parent::get_hydrate_payload();

		$payload['addons'] = ( new PluginDetector() )->active_addons();

		return $payload;
	}

	/**
	 * Install queued plugins, guaranteeing fresh addon download URLs first.
	 *
	 * Addon download URLs are resolved from the license-tier transient cache,
	 * which holds an empty placeholder for up to 10 minutes after a transient
	 * license-API hiccup (see {@see \WPForms_License::get_addons()}). Left alone,
	 * that placeholder would make a perfectly valid addon install fail until it
	 * expires. Before handing off to the shared installer, drop the tier-keyed
	 * caches whenever a requested addon has no cached URL, so the installer's
	 * first lookup refetches them live, exactly once, for this request.
	 *
	 * @since 2.0.0
	 *
	 * @param array $plugins Plugin files the SPA asked to install.
	 *
	 * @return array{installed: array, failed: array}
	 */
	public function install_plugins( array $plugins ): array {

		$this->refresh_addon_urls_if_missing( $plugins );

		return parent::install_plugins( $plugins );
	}

	/**
	 * Drop the tier-keyed addon URL caches when a requested addon URL is missing.
	 *
	 * Reads the transient directly rather than through the Addons handler: a call
	 * into the handler would warm its per-request `static` memo with the stale
	 * value, after which deleting the transient no longer forces a refetch within
	 * this request.
	 *
	 * @since 2.0.0
	 *
	 * @param array $plugins Plugin files the SPA asked to install.
	 */
	private function refresh_addon_urls_if_missing( array $plugins ): void {

		$addon_slugs = [];

		foreach ( $plugins as $plugin_file ) {
			$slug = dirname( (string) $plugin_file );

			// Addons live in a `wpforms-{slug}` directory; only they use the URL
			// cache. The prefix check is inlined rather than delegated to the
			// catalog/Plugin helper for the same reason this method reads the
			// transient directly: that path would warm the Addons handler's memo.
			if ( strpos( $slug, 'wpforms-' ) === 0 ) {
				$addon_slugs[] = $slug;
			}
		}

		if ( ! $addon_slugs ) {
			return;
		}

		$urls = Transient::get( 'addons_urls' );

		if ( is_array( $urls ) && $this->has_all_addon_urls( $addon_slugs, $urls ) ) {
			return;
		}

		Transient::delete( 'addons' );
		Transient::delete( 'addons_urls' );
	}

	/**
	 * Whether every requested addon already has a non-empty cached URL.
	 *
	 * @since 2.0.0
	 *
	 * @param array $addon_slugs Requested addon slugs (`wpforms-{slug}`).
	 * @param array $urls        Cached slug => URL map.
	 *
	 * @return bool
	 */
	private function has_all_addon_urls( array $addon_slugs, array $urls ): bool {

		foreach ( $addon_slugs as $slug ) {
			if ( empty( $urls[ $slug ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Schedule the Lite Connect entries restore via Action Scheduler.
	 *
	 * Fired when the SPA sends a truthy Lite Connect consent flag through `/update`,
	 * right after the wizard upgraded the site to Pro. The import runs in the
	 * background, so with many entries it will likely still be in progress when
	 * the customer finishes the wizard.
	 *
	 * When Action Scheduler hasn't finished its own migration yet or the Lite
	 * entries counter has not caught up, the consent is persisted on the option
	 * so a later admin request can pick it up (see
	 * {@see \WPForms\Pro\Integrations\LiteConnect\Admin::maybe_run_pending_restore()}).
	 *
	 * On a re-upgrade, a `done` status left by a previous Pro period makes this
	 * a no-op by design: the consent is dropped, and the customer restores the
	 * new entries via the "Restore Your Form Entries" admin notice instead.
	 *
	 * @since 2.0.0
	 */
	private function trigger_lite_connect_restore(): void {

		// Stay defensive: License Activation should already have gated this,
		// but a direct call from elsewhere must not silently import without a license.
		if ( ! wpforms_is_license_valid() ) {
			return;
		}

		// Already queued, running, or finished. Re-triggering would either be a no-op
		// inside process() or risk double-importing.
		$active_statuses = [
			ImportEntriesTask::STATUS_SCHEDULED,
			ImportEntriesTask::STATUS_RUNNING,
			ImportEntriesTask::STATUS_DONE,
		];

		if ( in_array( $this->get_restore_status(), $active_statuses, true ) ) {
			return;
		}

		// Soft gates: Action Scheduler may still be migrating and the Lite-side
		// entries counter may still be catching up (see SendEntryTask's random
		// 10-60 minute delay). Persist the consent and let a later admin
		// request pick the schedule up once these preconditions clear.
		if ( ! ImportEntriesTask::can_schedule() ) {
			self::mark_restore_pending();

			return;
		}

		( new ImportEntriesTask() )->create();
	}

	/**
	 * Persist the user's consent so a later admin request can pick up the schedule.
	 *
	 * Only called from {@see self::trigger_lite_connect_restore()} when a soft
	 * gate (Action Scheduler not usable yet, or the entries counter is still zero)
	 * blocks the immediate schedule. The retry lives in the Pro Lite Connect
	 * Admin class and runs on `wp_loaded`.
	 *
	 * @since 2.0.0
	 */
	private static function mark_restore_pending(): void {

		$settings = get_option( Integration::get_option_name(), [] );

		$settings['import']['pending_consent'] = true;

		update_option( Integration::get_option_name(), $settings );
	}

	/**
	 * Read the current Lite Connect entries restore status.
	 *
	 * Mirrors the `import.status` flag the Lite Connect integration writes as the
	 * background restore progresses. Empty string when no restore has run yet;
	 * otherwise one of `scheduled`, `running`, or `done`.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_restore_status(): string {

		return (string) ( wpforms_setting( 'import', [], Integration::get_option_name() )['status'] ?? '' );
	}

	/**
	 * Detect the customer-site context exposed to the SPA.
	 *
	 * Extends the shared snapshot with the number of Lite Connect entries
	 * available for restoration, so the SPA can offer the restore step and
	 * show how many entries it would bring back.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 * @noinspection PhpCastIsUnnecessaryInspection
	 */
	protected function detect_settings(): array {

		$settings = parent::detect_settings();

		$settings['lite_connect_pending_entries'] = (int) Integration::get_new_entries_count();
		$settings['lite_connect_restore_status']  = $this->get_restore_status();

		return $settings;
	}
}
