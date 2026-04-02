<?php
/**
 * GeneratePress Theme Integration.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Integrations\Themes;

/**
 * Class for handling the GeneratePress theme integration with Search & Filter.
 *
 * @since 3.0.0
 */
class Generate_Press {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/admin/editor/settings', array( $this, 'editor_settings' ) );
	}

	/**
	 * Generate Press admin / block editor styles use the base class `.block-editor__container`
	 * which is around the .styles-editor class - so we need to add this to our own
	 * editor containers.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $settings Editor settings to update.
	 * @return array The updated editor settings.
	 */
	public function editor_settings( array $settings ) {
		$settings['previewContainerClasses'][] = 'block-editor__container';
		return $settings;
	}
}
