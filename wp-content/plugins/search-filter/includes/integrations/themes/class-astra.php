<?php
namespace Search_Filter\Integrations\Themes;

/**
 * Class for handling the Astra theme integration with Search & Filter
 */
class Astra {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/admin/editor/settings', array( $this, 'editor_settings' ) );
	}
	/**
	 * Astra admin / block editor styles use the base class `.edit-post-visual-editor`
	 * which is around the .styles-editor class - so we need to add this to our own
	 * editor containers.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $settings The settings to filter.
	 */
	public function editor_settings( $settings ) {
		$settings['previewContainerClasses'][] = 'edit-post-visual-editor';
		return $settings;
	}
}
