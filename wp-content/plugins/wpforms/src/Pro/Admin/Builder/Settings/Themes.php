<?php

namespace WPForms\Pro\Admin\Builder\Settings;

use WPForms\Admin\Builder\Settings\Themes as ThemesBase;
use WPForms\Pro\Integrations\Gutenberg\StockPhotos;
use WPForms\Pro\Integrations\Gutenberg\ThemesData;

/**
 * Themes panel.
 *
 * @since 1.9.7
 */
class Themes extends ThemesBase {

	/**
	 * Stock photos class instance.
	 *
	 * @since 1.9.7
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;

	/**
	 * Init class.
	 *
	 * @since 1.9.7
	 */
	public function init(): void {

		$this->stock_photos_obj = new StockPhotos();
		$this->themes_data_obj  = new ThemesData( $this->stock_photos_obj );

		parent::init();
	}

	/**
	 * Get localize data for PRO.
	 *
	 * @since 1.9.7
	 *
	 * @return array
	 */
	protected function get_localize_data(): array {

		$data = parent::get_localize_data();

		$strings = [
			'picturesTitle'     => esc_html__( 'Choose a Stock Photo', 'wpforms' ),
			'picturesSubTitle'  => esc_html__( 'Browse for the perfect image for your form background.', 'wpforms' ),
			'stockInstallTheme' => esc_html__( 'The theme you’ve selected has a background image.', 'wpforms' ),
			'stockInstallBg'    => esc_html__( 'In order to use Stock Photos, an image library must be downloaded and installed.', 'wpforms' ),
			'stockInstall'      => esc_html__( 'It\'s quick and easy, and you\'ll only have to do this once.', 'wpforms' ),
			'continue'          => esc_html__( 'Continue', 'wpforms' ),
			'cancel'            => esc_html__( 'Cancel', 'wpforms' ),
			'installing'        => esc_html__( 'Installing', 'wpforms' ),
			'uhoh'              => esc_html__( 'Uh oh!', 'wpforms' ),
			'close'             => esc_html__( 'Close', 'wpforms' ),
			'commonError'       => esc_html__( 'Something went wrong while performing an AJAX request.', 'wpforms' ),
		];

		$data['strings'] = array_merge( $data['strings'], $strings );

		$data['stockPhotos'] = [
			'urlPath'  => $this->stock_photos_obj->get_url_path(),
			'pictures' => $this->stock_photos_obj->get_pictures(),
		];

		$license_obj                   = wpforms()->obj( 'license' );
		$data['isLicenseActive']       = wpforms()->is_pro() && wpforms_get_license_key() && $license_obj && $license_obj->is_active();
		$data['isLowFormPagesVersion'] = $this->is_low_fp_version();

		return $data;
	}

	/**
	 * Check if the Form Pages version is supported.
	 *
	 * @since 1.9.7
	 *
	 * @return bool Returns false if the Form Pages version is lower than 1.11.0, true otherwise.
	 */
	private function is_low_fp_version(): bool {

		if ( ! defined( 'WPFORMS_FORM_PAGES_VERSION' ) ) {
			return false;
		}

		return version_compare( WPFORMS_FORM_PAGES_VERSION, '1.11.0', '<' );
	}
}
