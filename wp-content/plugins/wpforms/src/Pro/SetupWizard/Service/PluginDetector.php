<?php

namespace WPForms\Pro\SetupWizard\Service;

use WPForms\Helpers\Plugin;
use WPForms\SetupWizard\Service\PluginDetector as BasePluginDetector;

/**
 * Setup Wizard plugin detector (Pro).
 *
 * Extends the shared detector with WPForms addon reporting. Lite ships no
 * addons, so enumerating the active ones is a Pro-only concern the wizard
 * surfaces on its hydrate payload.
 *
 * @since 2.0.0
 */
class PluginDetector extends BasePluginDetector {

	/**
	 * List the slugs of every installed and active WPForms addon.
	 *
	 * Detection is live and cache-independent (`Plugin::is_wpforms_addon()`
	 * checks the `wpforms-` prefix and the `WPForms` author header), so it stays
	 * correct in the wizard's front-end REST context where the addons data
	 * handler is not loaded.
	 *
	 * @since 2.0.0
	 *
	 * @return string[] Addon plugin files, e.g. `wpforms-stripe/wpforms-stripe.php`.
	 */
	public function active_addons(): array {

		Plugin::ensure_plugin_functions();

		$plugins = [];

		foreach ( array_keys( get_plugins() ) as $plugin ) {
			if ( Plugin::is_wpforms_addon( $plugin ) && is_plugin_active( $plugin ) ) {
				$plugins[] = $plugin;
			}
		}

		return $plugins;
	}
}
