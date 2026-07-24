<?php

namespace WPForms\Pro\SetupWizard;

use WPForms\SetupWizard\SetupWizard as BaseSetupWizard;

/**
 * Setup Wizard orchestrator (Pro).
 *
 * Adds the one-shot wizard relaunch after a Lite-to-Pro upgrade: customers who
 * completed the wizard on Lite never saw its Pro-only steps (entries restore,
 * addons, integrations), so the first Pro admin pageview re-arms the standard
 * auto-launch flow.
 *
 * @since 2.0.0
 */
class SetupWizard extends BaseSetupWizard {

	/**
	 * Initialize the orchestrator.
	 *
	 * @since 2.0.0
	 */
	public function init(): void {

		parent::init();

		$this->hooks();
	}

	/**
	 * Register the Pro-only hooks.
	 *
	 * Private on both the base and this class: the parent `init()` keeps calling
	 * its own implementation, so the shared hooks are never re-registered here.
	 *
	 * @since 2.0.0
	 */
	private function hooks(): void {

		add_action( 'admin_init', [ $this, 'maybe_relaunch_after_pro_upgrade' ] );
	}

	/**
	 * Re-arm the wizard once after a Lite-to-Pro upgrade.
	 *
	 * The state manager stamps every completion with the running edition (see
	 * {@see \WPForms\SetupWizard\Service\StateManager::complete()}). When Pro
	 * runs against a wizard completed on Lite, the completed marker is dropped
	 * and the one-shot first-run signal is set again, so the standard
	 * auto-launch flow shows the Pro wizard exactly once.
	 *
	 * @since 2.0.0
	 */
	public function maybe_relaunch_after_pro_upgrade(): void {

		// Mirror the launch-side gates: the one-shot re-arm must start its 1h
		// window on a request where the launch can actually happen, not on an
		// AJAX/REST call or a pageview of a user who would never see the wizard.
		if ( wp_doing_ajax() || wpforms_is_rest() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$completed = get_option( self::OPTION_COMPLETED );

		// Only a wizard completed in Lite qualifies. The pre-edition format
		// (a plain version string) cannot tell the edition and is left alone.
		if ( ! is_array( $completed ) || ( $completed['edition'] ?? '' ) !== 'lite' ) {
			return;
		}

		delete_option( self::OPTION_COMPLETED );
		set_transient( self::TRANSIENT_FIRST_RUN, 1, HOUR_IN_SECONDS );
	}
}
