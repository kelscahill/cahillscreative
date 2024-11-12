<?php
namespace Search_Filter\Integrations\Themes;

/**
 * Class for handling the Neve theme integration with Search & Filter
 */
class Neve {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/admin/register_styles', array( $this, 'register_styles' ) );
	}
	/**
	 * Add wp-edit-blocks as a dependency so that the Neve CSS gets loaded in our admin screens.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $registered_styles Styles to register.
	 */
	public function register_styles( $registered_styles ) {
		if ( ! isset( $registered_styles['search-filter-admin'] ) ) {
			return $registered_styles;
		}
		// Add wp-edit-blocks to our dependency as this is what Kadence uses to add its inline CSS.
		$admin_deps   = $registered_styles['search-filter-admin']['deps'];
		$admin_deps[] = 'wp-edit-blocks';
		array_unique( $admin_deps );
		$registered_styles['search-filter-admin']['deps'] = $admin_deps;

		return $registered_styles;
	}
}
