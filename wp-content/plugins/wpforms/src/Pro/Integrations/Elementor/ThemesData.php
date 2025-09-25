<?php

namespace WPForms\Pro\Integrations\Elementor;

use WPForms\Helpers\File;
use WPForms\Integrations\Elementor\ThemesData as ThemesDataBase;

/**
 * Themes data for Elementor widget for Pro.
 *
 * @since 1.9.6
 */
class ThemesData extends ThemesDataBase {

	/**
	 * WPForms themes JSON file path.
	 *
	 * Relative to the WPForms plugin directory.
	 *
	 * @since 1.9.6
	 *
	 * @var string
	 */
	protected const THEMES_WPFORMS_JSON_PATH = 'assets/pro/js/integrations/elementor/themes.json';

	/**
	 * Stock photos class instance.
	 *
	 * @since 1.9.6
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;

	/**
	 * Initialize class.
	 *
	 * @since 1.9.6
	 *
	 * @param StockPhotos|mixed $stock_photos_obj StockPhotos object.
	 */
	public function __construct( $stock_photos_obj ) {

		$this->stock_photos_obj = $stock_photos_obj;
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

	/**
	 * Return WPForms themes.
	 *
	 * @since 1.9.6
	 *
	 * @return array
	 */
	public function get_wpforms_themes(): array {

		if ( $this->wpforms_themes !== null ) {
			return $this->wpforms_themes;
		}

		$path        = static::THEMES_WPFORMS_JSON_PATH;
		$themes_json = File::get_contents( WPFORMS_PLUGIN_DIR . $path ) ?? '{}';
		$themes      = json_decode( $themes_json, true );

		$this->wpforms_themes = ! empty( $themes ) ? $themes : [];
		$is_license_active    = $this->is_license_active();

		foreach ( $this->wpforms_themes as $slug => $theme ) {
			if ( ! $is_license_active && ! in_array( $slug, [ 'classic', 'default' ], true ) ) {
				$this->wpforms_themes[ $slug ]['disabled'] = 1;
			}

			if (
				empty( $theme['settings']['backgroundUrl'] ) ||
				$theme['settings']['backgroundUrl'] === 'url()'
			) {
				continue;
			}

			// Replace the background image filename with the stock photo URL.
			$this->wpforms_themes[ $slug ]['settings']['backgroundUrl'] = sprintf(
				'url( %1$s%2$s )',
				$this->stock_photos_obj->get_url_path(),
				$theme['settings']['backgroundUrl']
			);
		}

		return $this->wpforms_themes;
	}
}
