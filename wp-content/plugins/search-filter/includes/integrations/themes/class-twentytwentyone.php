<?php
/**
 * Twenty Twenty-One Theme Integration.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Integrations\Themes;

use Search_Filter\Util;

/**
 * Class for handling the Twenty Twenty-One theme integration with Search & Filter.
 *
 * @since 3.0.0
 */
class Twentytwentyone {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/core/asset-loader/register', array( $this, 'register_assets' ) );
		add_filter( 'search-filter/core/asset-loader/enqueue', array( $this, 'enqueue_styles' ) );
	}
	/**
	 * Add twentytwentyone styles to the registered assets
	 *
	 * @since    3.0.0
	 *
	 * @param    array $registered_assets Styles to register.
	 */
	public function register_assets( $registered_assets ) {
		if ( isset( $registered_assets['search-filter-twentytwentyone'] ) ) {
			return $registered_assets;
		}
		$registered_assets['search-filter-twentytwentyone'] = array(
			'name'   => 'search-filter-twentytwentyone',
			'script' => array(),
			'style'  => array(
				'src'          => trailingslashit( plugin_dir_url( dirname( __DIR__, 2 ) ) ) . 'assets/integrations/themes/twentytwentyone.css',
				'dependencies' => array( 'search-filter-frontend' ),
				'version'      => SEARCH_FILTER_VERSION,
				'media'        => 'all',
			),
		);
		return $registered_assets;
	}

	/**
	 * Enqueue the styles for the integration
	 *
	 * @since    3.0.0
	 *
	 * @param    array $enqueued_styles Styles that have been enqueued.
	 */
	public function enqueue_styles( $enqueued_styles ) {
		$enqueued_styles[] = 'search-filter-twentytwentyone';
		$enqueued_styles   = array_unique( $enqueued_styles );
		return $enqueued_styles;
	}
}
