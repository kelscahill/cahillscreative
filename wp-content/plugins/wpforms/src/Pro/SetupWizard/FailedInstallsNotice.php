<?php

namespace WPForms\Pro\SetupWizard;

use WPForms\SetupWizard\FailedInstallsNotice as BaseFailedInstallsNotice;

/**
 * Failed addon installs notice for the Setup Wizard (Pro).
 *
 * Extends the shared plugins notice with a second dismissible notice naming the
 * WPForms addons that failed to install during the wizard run. Addon names are
 * listed plainly with links to download them from wpforms.com, the manual
 * installation guide, and support.
 *
 * @since 2.0.0
 */
class FailedInstallsNotice extends BaseFailedInstallsNotice {

	/**
	 * Dismissible notice slug for failed addons.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private const NOTICE_SLUG_ADDONS = 'setup_wizard_failed_installs_addons';

	/**
	 * Register the failure notices for an installation run.
	 *
	 * Adds a second dismissible notice listing the failed addons on top of the
	 * base regular-plugins notice, so each is dismissible on its own.
	 *
	 * @since 2.0.0
	 *
	 * @param array $failed Failed installs as `plugin file => error message`.
	 */
	protected function display_notices( array $failed ): void {

		parent::display_notices( $failed );

		$addons = [];

		foreach ( array_keys( $failed ) as $plugin ) {
			if ( ! $this->catalog->is_addon( $plugin ) ) {
				continue;
			}

			$addons[] = esc_html( $this->format_label( $plugin ) );
		}

		$this->add_notice( $this->addons_message( $addons ), self::NOTICE_SLUG_ADDONS );
	}

	/**
	 * Message listing the failed addons with download, manual-install, and support links.
	 *
	 * Mirrors the `addon_error` message shown on the Addons page, adapted to
	 * name the addons that failed during the wizard run.
	 *
	 * @since 2.0.0
	 *
	 * @param array $addons Addon labels.
	 *
	 * @return string Empty string when no addons failed.
	 */
	private function addons_message( array $addons ): string {

		if ( empty( $addons ) ) {
			return '';
		}

		return sprintf(
			'<p>%s</p>',
			sprintf(
				wp_kses( /* translators: %1$s - comma-separated list of addon names, %2$s - addon download URL, %3$s - link to manual installation guide, %4$s - link to contact support. */
					__( 'Could not install the following addons during setup: %1$s. Please <a href="%2$s" target="_blank" rel="noopener noreferrer">download them from wpforms.com</a> and <a href="%3$s" target="_blank" rel="noopener noreferrer">install them manually</a>, or <a href="%4$s" target="_blank" rel="noopener noreferrer">contact support</a> for assistance.', 'wpforms' ),
					[
						'a' => [
							'href'   => true,
							'target' => true,
							'rel'    => true,
						],
					]
				),
				implode( ', ', $addons ),
				esc_url( wpforms_utm_link( 'https://wpforms.com/account/licenses/', 'Setup Wizard Addons Error', 'download them from wpforms.com' ) ),
				esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-manually-install-addons-in-wpforms/', 'Setup Wizard Addons Error', 'install them manually' ) ),
				esc_url( wpforms_utm_link( 'https://wpforms.com/contact/', 'Setup Wizard Addons Error', 'contact support' ) )
			)
		);
	}
}
