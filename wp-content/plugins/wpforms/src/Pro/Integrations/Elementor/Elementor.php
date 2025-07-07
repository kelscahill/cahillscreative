<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace WPForms\Pro\Integrations\Elementor;

use Elementor\Plugin as ElementorPlugin;

/**
 * Improve Elementor Compatibility.
 *
 * @since 1.7.0
 */
class Elementor extends \WPForms\Integrations\Elementor\Elementor {


	/**
	 * Stock photos class instance.
	 *
	 * @since 1.9.6
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;


	/**
	 * Load a Pro integration.
	 *
	 * @since 1.9.6
	 */
	public function load() {

		$this->stock_photos_obj = new StockPhotos();
		$this->themes_data_obj  = new ThemesData( $this->stock_photos_obj );

		$this->hooks();
	}

	/**
	 * Integration hooks.
	 *
	 * @since 1.9.6
	 */
	protected function hooks() {

		parent::hooks();

		add_filter( 'wpforms_integrations_elementor_editor_strings', [ $this, 'add_editor_strings' ] );
		add_filter( 'wpforms_integrations_elementor_editor_vars', [ $this, 'add_editor_vars' ] );
	}

	/**
	 * Add editor strings.
	 *
	 * @since 1.9.6
	 *
	 * @param array|mixed $strings Strings.
	 */
	public function add_editor_strings( $strings ): array {

		$strings = (array) $strings;

		$pro_strings = [
			'stockInstallTheme' => esc_html__( 'The theme you’ve selected has a background image.', 'wpforms' ),
			'stockInstallBg'    => esc_html__( 'In order to use Stock Photos, an image library must be downloaded and installed.', 'wpforms' ),
			'stockInstall'      => esc_html__( 'It\'s quick and easy, and you\'ll only have to do this once.', 'wpforms' ),
			'continue'          => esc_html__( 'Continue', 'wpforms' ),
			'cancel'            => esc_html__( 'Cancel', 'wpforms' ),
			'uhoh'              => esc_html__( 'Uh oh!', 'wpforms' ),
			'installing'        => esc_html__( 'Installing', 'wpforms' ),
			'close'             => esc_html__( 'Close', 'wpforms' ),
			'commonError'       => esc_html__( 'Something went wrong while performing an AJAX request.', 'wpforms' ),
			'picturesTitle'     => esc_html__( 'Choose a Stock Photo', 'wpforms' ),
			'picturesSubTitle'  => esc_html__( 'Browse for the perfect image for your form background.', 'wpforms' ),
		];

		return array_merge( $strings, $pro_strings );
	}

	/**
	 * Add editor vars.
	 *
	 * @since 1.9.6
	 *
	 * @param array|mixed $vars Strings.
	 */
	public function add_editor_vars( $vars ): array {

		$vars = (array) $vars;

		$vars['stockPhotos'] = [
			'urlPath'  => $this->stock_photos_obj->get_url_path(),
			'pictures' => $this->stock_photos_obj->get_pictures(),
		];

		$vars['isLicenseActive'] = wpforms()->is_pro() && $this->is_license_active();

		return $vars;
	}

	/**
	 * Load assets in the preview panel.
	 *
	 * @since 1.7.0
	 */
	public function preview_assets() {

		if ( ! ElementorPlugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		parent::preview_assets();

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-pro-integrations',
			WPFORMS_PLUGIN_URL . "assets/pro/css/admin-integrations{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Load assets in the elementor document.
	 *
	 * @since 1.7.0
	 */
	public function editor_assets() {

		if ( empty( $_GET['action'] ) || $_GET['action'] !== 'elementor' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		parent::editor_assets();

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-pro-integrations',
			WPFORMS_PLUGIN_URL . "assets/pro/css/admin-integrations{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Check if the license is active.
	 *
	 * The code runs before wpforms() is ready, so we need to have here the same implementation as in WPForms_License::is_active.
	 * Different from in WPForms_License::is_active, we check also if the key is empty.
	 *
	 * @since 1.9.6
	 *
	 * @return bool
	 */
	private function is_license_active(): bool {

		$license = get_option( 'wpforms_license', false );

		return (
		! (
			empty( $license ) ||
			empty( $license['key'] ) ||
			! empty( $license['is_expired'] ) ||
			! empty( $license['is_disabled'] ) ||
			! empty( $license['is_invalid'] )
		)
		);
	}
}
