<?php

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedClassInspection */

namespace WPForms\Pro\Integrations\Elementor;

use WP_REST_Request;
use WP_REST_Response;

use WPForms\Integrations\Elementor\RestApi as RestApiBase;
use WPForms\Integrations\Elementor\ThemesData;

/**
 * Rest API for Elementor Widget for Pro.
 *
 * @since 1.9.6
 */
class RestApi extends RestApiBase {

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
	 * @param Widget|mixed      $widget_obj       Widget object.
	 * @param ThemesData|mixed  $themes_data_obj  ThemesData object.
	 * @param StockPhotos|mixed $stock_photos_obj StockPhotos object.
	 */
	public function __construct( $widget_obj, $themes_data_obj, $stock_photos_obj ) {

		if ( ! $widget_obj || ! $themes_data_obj || ! $stock_photos_obj || ! wpforms_is_wpforms_rest() ) {
			return;
		}

		$this->stock_photos_obj = $stock_photos_obj;

		parent::__construct( $widget_obj, $themes_data_obj );
	}

	/**
	 * Register API routes for Elementor widget.
	 *
	 * @since 1.9.6
	 */
	public function register_api_routes() {

		parent::register_api_routes();

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/stock-photos/install/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'install_stock_photos' ],
				'permission_callback' => [ $this, 'admin_permissions_check' ],
			]
		);
	}

	/**
	 * Install stock photos..
	 *
	 * @since 1.9.6
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_stock_photos( WP_REST_Request $request ): WP_REST_Response {

		$force = (bool) ( $request->get_param( 'force' ) ?? false );

		// Install stock photos and return REST response.
		$result = $this->stock_photos_obj->install( $force );

		if ( ! empty( $result['error'] ) ) {
			return rest_ensure_response(
				[
					'result' => false,
					'error'  => $result['error'],
				]
			);
		}

		return rest_ensure_response(
			[
				'result'   => true,
				'pictures' => $result['pictures'] ?? [],
			]
		);
	}
}
