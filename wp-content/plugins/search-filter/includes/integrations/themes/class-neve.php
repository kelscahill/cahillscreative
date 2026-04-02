<?php
/**
 * Neve Theme Integration.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Integrations\Themes;

/**
 * Class for handling the Neve theme integration with Search & Filter.
 *
 * @since 3.0.0
 */
class Neve {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/core/asset-loader/register', array( $this, 'register_styles_asset' ) );
	}
	/**
	 * Add wp-edit-blocks as a dependency so that the Neve CSS gets loaded in our admin screens.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $registered_assets Styles to register.
	 * @return array The updated registered styles.
	 */
	public function register_styles_asset( array $registered_assets ) {
		if ( ! isset( $registered_assets['search-filter-admin'] ) ) {
			return $registered_assets;
		}
		if ( ! isset( $registered_assets['search-filter-admin']['style'] ) ) {
			return $registered_assets;
		}
		// Add wp-edit-blocks to our dependency as this is what Neve uses to add its inline CSS.
		$admin_deps   = $registered_assets['search-filter-admin']['style']['dependencies'];
		$admin_deps[] = 'wp-edit-blocks';
		$admin_deps   = array_unique( $admin_deps );
		$registered_assets['search-filter-admin']['style']['dependencies'] = $admin_deps;

		return $registered_assets;
	}
}
