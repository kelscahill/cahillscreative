<?php

namespace WPForms\Pro\Admin;

/**
 * PluginListDisabler class.
 *
 * @since 1.9.5
 */
class PluginListDisabler {

	/**
	 * Whether the license is valid.
	 *
	 * @since 1.9.5
	 *
	 * @var bool
	 */
	private $is_valid_license;

	/**
	 * Init.
	 *
	 * @since 1.9.5
	 *
	 * @param bool $is_valid_license Whether the license is valid.
	 */
	public function init( bool $is_valid_license ): void {

		$this->is_valid_license = $is_valid_license;

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.5
	 */
	private function hooks(): void {

		global $pagenow;

		if ( $this->is_valid_license ) {
			return;
		}

		if (
			empty( $pagenow ) ||
			! in_array( $pagenow, [ 'plugins.php', 'update-core.php', 'plugin-install.php' ], true ) ) {
			return;
		}

		add_action( 'admin_print_footer_scripts', [ $this, 'hide_update_now_button' ] );
		add_action( 'admin_footer-plugins.php', [ $this, 'disable_plugin_checkbox' ] );
		add_action( 'admin_footer-update-core.php', [ $this, 'disable_plugin_checkbox' ] );
	}

	/**
	 * Disable addons checkboxes if the license is not valid.
	 *
	 * @since 1.9.5
	 */
	public function disable_plugin_checkbox(): void {

		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				// Disable checkbox on the Plugins and the Updates page.
				$('tr.update[data-slug^="wpforms-"] .check-column input[type="checkbox"], #update-plugins-table .check-column input[type="checkbox"][value^="wpforms-"]')
					.prop('disabled', true)
					.attr('title', '<?php esc_html_e( 'WPForms license is not valid.', 'wpforms' ); ?>');
			});
		</script>
		<?php
	}

	/**
	 * Hide update now button in the plugin info modal.
	 *
	 * @since 1.9.5
	 */
	public function hide_update_now_button(): void {
		?>
		<script type="text/javascript">
			jQuery( '#plugin_install_from_iframe[data-slug*="wpforms"]' ).hide();
		</script>
		<?php
	}
}
