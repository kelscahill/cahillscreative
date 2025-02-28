<?php
namespace Search_Filter\Integrations\Themes;

use Search_Filter\Util;

/**
 * Class for handling the Neve theme integration with Search & Filter
 */
class Twentytwentyone {
	/**
	 * Initialise the integration
	 */
	public function __construct() {
		add_filter( 'search-filter/frontend/register_styles', array( $this, 'register_styles' ) );
		add_filter( 'search-filter/frontend/enqueue_styles', array( $this, 'enqueue_styles' ) );
	}
	/**
	 * Add wp-edit-blocks as a dependency so that the Neve CSS gets loaded in our admin screens.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $registered_styles Styles to register.
	 */
	public function register_styles( $registered_styles ) {
		$registered_styles['search-filter-twentytwentyone'] = array(
			'src'     => trailingslashit( plugin_dir_url( dirname( __DIR__, 2 ) ) ) . 'assets/css/integrations/twentytwentyone.css',
			'deps'    => array( 'search-filter' ),
			'version' => SEARCH_FILTER_VERSION,
			'media'   => 'all',
		);
		return $registered_styles;
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
