<?php

namespace WPForms\Pro\Integrations\Gutenberg;

use WPForms\Helpers\File;
use WPForms\Integrations\Gutenberg\ThemesData as ThemesDataBase;

/**
 * Themes data for Gutenberg block for Pro.
 *
 * @since 1.8.8
 */
class ThemesData extends ThemesDataBase {

	/**
	 * WPForms themes JSON file path.
	 *
	 * Relative to WPForms plugin directory.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const THEMES_WPFORMS_JSON_PATH = 'assets/pro/js/integrations/gutenberg/themes.json';

	/**
	 * Stock photos class instance.
	 *
	 * @since 1.8.8
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;

	/**
	 * Initialize class.
	 *
	 * @since 1.8.8
	 *
	 * @param StockPhotos|mixed $stock_photos_obj StockPhotos object.
	 */
	public function __construct( $stock_photos_obj ) {

		$this->stock_photos_obj = $stock_photos_obj;
	}

	/**
	 * Check if the license is active.
	 *
	 * Delegates to the canonical wpforms_is_license_valid() helper.
	 *
	 * @since 1.8.8
	 *
	 * @return bool
	 */
	private function is_license_active(): bool {

		return wpforms_is_license_valid();
	}

	/**
	 * Return WPForms themes.
	 *
	 * @since 1.8.8
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
