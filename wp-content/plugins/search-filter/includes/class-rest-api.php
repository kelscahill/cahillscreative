<?php
/**
 * Main Rest API entrypoint.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Admin\Screens;
use Search_Filter\Core\Notices;
use Search_Filter\Settings;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Queries\Query;
use WP_REST_Request;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for setting up rest api routes.
 */
class Rest_API {

	const ENABLE_PRO_NONCE = 'search_filter_enable_pro_nonce';

	/**
	 * The bulk class.
	 *
	 * @var Records
	 */
	private $bulk;
	/**
	 * The queries class.
	 *
	 * @var Queries
	 */
	private $queries;
	/**
	 * The fields class.
	 *
	 * @var Fields
	 */
	private $fields;
	/**
	 * The styles class.
	 *
	 * @var Styles
	 */
	private $styles;
	/**
	 * The data class.
	 *
	 * @var Records
	 */
	private $data;
	/**
	 * The data class.
	 *
	 * @var Integrations
	 */
	private $integrations;
	/**
	 * The data class.
	 *
	 * @var Features
	 */
	private $features;
	/**
	 * Attach main hook.
	 */
	public function __construct() {
		$this->bulk         = new \Search_Filter\Rest_API\Bulk();
		$this->queries      = new \Search_Filter\Rest_API\Queries();
		$this->fields       = new \Search_Filter\Rest_API\Fields();
		$this->styles       = new \Search_Filter\Rest_API\Styles();
		$this->data         = new \Search_Filter\Rest_API\Data();
		$this->features     = new \Search_Filter\Rest_API\Features();
		$this->integrations = new \Search_Filter\Rest_API\Integrations();

		add_action( 'rest_pre_serve_request', array( $this, 'add_rest_api_request_action' ), 10, 4 );

		add_action( 'rest_api_init', array( $this, 'add_routes' ) );
	}

	/**
	 * Add the rest api request action.
	 *
	 * @param bool            $served The served status.
	 * @param mixed           $result The result.
	 * @param WP_REST_Request $request The request object.
	 * @param WP_REST_Server  $server The server object.
	 * @return bool The served status.
	 */
	public function add_rest_api_request_action( $served, $result, $request, $server ) {
		// if route starts with `/search-filter/v1/` then we need to add the action.
		$routes                 = array( '/search-filter/', '/search-filter-pro/' );
		$is_search_filter_route = false;
		foreach ( $routes as $route ) {
			if ( strpos( $request->get_route(), $route ) === 0 ) {
				$is_search_filter_route = true;
				break;
			}
		}

		if ( $is_search_filter_route ) {
			do_action( 'search-filter/rest-api/request', $request );
		}

		return $served;
	}
	/**
	 * Gets a saved field
	 *
	 * @param mixed $params  Query ID + Field ID.
	 */
	public function get_field( $params ) {
		$field = Field::find(
			array(
				'query_id' => $params['query_id'],
				'name'     => $params['field'],
			)
		);
		$data  = array();
		if ( ! is_wp_error( $field ) ) {
			$data['output'] = $field->render( true );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Generates a field based on attributes
	 *
	 * @param array $params Parameters from the request.
	 */
	public function get_field_data( $params ) {
		$attributes = $params['attributes'];

		$field = null;
		try {
			$field = Field_Factory::create( $attributes );

		} catch ( \Exception $e ) {
			/**
			 * We don't want to throw an error if the attributes are not
			 * valid, we'll get this often when a field is first created
			 * and before its settings have been resolved in JavaScript.
			 *
			 * TODO - this endpoint needs rethinking - I think currently
			 * its only used to fetch the options array for filters...
			 * TODO 2 - need to resolve settings in PHP (we need this for
			 * the Elementor integration anyway) same as the JS
			 * implementation does, so that we don't init our blocks +
			 * fields with bad data, and we won't be sending bad requests
			 * to this endpoint.
			 */
			return rest_ensure_response(
				array(
					'options' => array(),
				)
			);
		}

		$response = array();
		if ( $field ) {
			$response = $field->get_json_data();
			if ( isset( $response['attributes'] ) ) {
				unset( $response['attributes'] );
			}
		}
		return rest_ensure_response( $response );
	}

	/**
	 * Enable pro
	 *
	 * @param array $params Parameters from the request.
	 */
	public function enable_pro( $params ) {
		// Nonce.
		$nonce = $params['nonce'];
		if ( ! wp_verify_nonce( $nonce, self::ENABLE_PRO_NONCE ) ) {
			return rest_ensure_response( false );
		}
		$pro_enabled = \Search_Filter\Core\Dependants::is_search_filter_pro_enabled();
		if ( ! $pro_enabled ) {
			\Search_Filter\Core\Dependants::enable_search_filter_pro();
		}
		// TODO - need to return a suitable response.
		return rest_ensure_response( true );
	}
	/**
	 * Fetch the admin data
	 */
	public function get_admin_data() {

		$admin_data = array(
			'pro'  => array(
				'isEnabled'         => \Search_Filter\Core\Dependants::is_search_filter_pro_enabled(),
				'isRequirementsMet' => \Search_Filter\Core\Dependants::is_search_filter_pro_requirements_met(),
				'isInstalled'       => \Search_Filter\Core\Dependants::is_search_filter_pro_installed(),
				'enableNonce'       => wp_create_nonce( self::ENABLE_PRO_NONCE ),
			),
			'site' => array(
				'url'   => home_url(),
				'email' => wp_get_current_user()->user_email,
			),
		);
		$admin_data = apply_filters( 'search-filter/rest-api/get_admin_data', $admin_data );
		return rest_ensure_response( $admin_data );
	}

	/**
	 * Fetch the admin pages
	 */
	public function get_admin_pages() {
		$screens = new Screens();
		return rest_ensure_response( $screens->get_pages() );
	}

	/**
	 * Get the list of registered field input types
	 */
	public function get_field_input_types() {
		// TODO - this needs to be dynamically generated.
		$field_types       = array(
			'search'   => __( 'Search', 'search-filter' ),
			'choice'   => __( 'Choice', 'search-filter' ),
			'range'    => __( 'Range', 'search-filter' ),
			'advanced' => __( 'Advanced', 'search-filter' ),
			'control'  => __( 'Control', 'search-filter' ),
		);
		$input_type_matrix = Field_Factory::get_field_input_types();
		$cleaned_matrix    = array();
		foreach ( $input_type_matrix as $field_type => $input_types ) {

			$cleaned_matrix[ $field_type ]               = array();
			$cleaned_matrix[ $field_type ]['label']      = $field_types[ $field_type ];
			$cleaned_matrix[ $field_type ]['inputTypes'] = array();

			foreach ( $input_types as $input_type => $input_type_class ) {
				$cleaned_matrix[ $field_type ]['inputTypes'][ $input_type ] = $input_type_class::get_label();
			}
		}
		return rest_ensure_response( $cleaned_matrix );
	}

	/**
	 * Fetch the default styles
	 */
	public function get_default_styles() {
		return rest_ensure_response( Styles::get_default_styles_id() );
	}

	/**
	 * Fetch the screen options
	 */
	public function get_screen_options() {
		return rest_ensure_response( Screens::get_admin_screen_options() );
	}
	/**
	 * Fetch the dashboard data
	 */
	public function get_dashboard_data() {

		$fields_count    = Fields::find_count(
			array(
				'status'  => 'enabled',
				'context' => '',
			)
		);
		$fields_be_count = Fields::find_count(
			array(
				'status'  => 'enabled',
				'context' => 'block-editor',
			)
		);
		$queries_count   = Queries::find_count(
			array(
				'status' => 'enabled',
			)
		);
		$styles_count    = Styles::find_count(
			array(
				'status' => 'enabled',
			)
		);
		$counts          = array(
			'fields'              => $fields_count,
			'fields/block-editor' => $fields_be_count,
			'queries'             => $queries_count,
			'styles'              => $styles_count,
		);

		return rest_ensure_response( $counts );
	}
	/**
	 * Update the screen options
	 *
	 * @param array $params Parameters from the request.
	 */
	public function update_screen_options( $params ) {
		$section = $params['section'];
		$options = wp_parse_args(
			$params['options'],
			// Make sure we have sensible defaults if anything goes wrong.
			array(
				'itemsPerPage' => '10',
				'columns'      => array( 'name', 'status', 'date' ),
			)
		);
		if ( ! in_array( 'name', $options['columns'], true ) ) {
			$options['columns'] = array_push( $options['columns'], 'name' );
		}
		$screen_options             = Screens::get_admin_screen_options();
		$screen_options[ $section ] = $options;
		update_user_meta( get_current_user_id(), 'search_filter_screen_options', $screen_options );
		return rest_ensure_response( $screen_options );
	}
	/**
	 * Set the default styles
	 *
	 * @param array $params Parameters from the request.
	 */
	public function set_default_styles( $params ) {
		$id = $params['id'];
		Styles::set_default_styles_id( $id );
		return rest_ensure_response( $id );
	}

	/**
	 * Get the admin settings
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function get_admin_settings( WP_REST_Request $request ) {

		$params  = $request->get_params();
		$section = $params['section'];

		$settings = array();
		$groups   = array();

		$settings_class = Settings::get_register_class( $section );
		if ( $settings_class ) {
			$settings = call_user_func( array( $settings_class, 'get_ordered' ), 'arrays' );
			$groups   = call_user_func( array( $settings_class, 'get_groups_ordered' ) );
		}

		$response = array(
			'code' => 'success',
			'data' => array(
				'settings' => $settings,
				'groups'   => $groups,
			),
		);
		return rest_ensure_response( $response );
	}

	/**
	 * Get admin settings in bulk
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function get_admin_settings_bulk( WP_REST_Request $request ) {

		$sections = $request->get_param( 'sections' );

		$section_data = array();

		foreach ( $sections as $section ) {
			$settings_class = Settings::get_register_class( $section );

			if ( $settings_class ) {
				$section_data[ $section ] = array(
					'settings' => call_user_func( array( $settings_class, 'get_ordered' ), 'arrays' ),
					'groups'   => call_user_func( array( $settings_class, 'get_groups_ordered' ) ),
				);
			}
		}
		return rest_ensure_response( $section_data );
	}

	/**
	 * Sanitize the admin sections
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	public function sanitize_sections( $sections ) {
		return array_map( 'sanitize_key', $sections );
	}
	/**
	 * Get the notices.
	 *
	 * @since 3.0.0
	 */
	public function get_admin_notices( \WP_REST_Request $request ) {
		$notices = Notices::get_notices();
		return rest_ensure_response( $notices );
	}

	/**
	 * Dismiss a notice.
	 *
	 * @param \WP_REST_Request $request
	 * @return void
	 */
	public function dismiss_admin_notice( \WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );
		Notices::dismiss_notice( $id );
		return rest_ensure_response( true );
	}
	/**
	 * Get the context fields IDs.
	 *
	 * @since 3.0.0
	 */
	public function get_context_fields_ids( WP_REST_Request $request ) {

		$params = $request->get_params();

		$context_path = $params['context_path'];
		$context      = $params['context'];

		$fields     = Fields::find(
			array(
				'context'      => $context,
				'context_path' => $context_path,
				'number'       => 0,
			),
			'records'
		);
		$fields_ids = array();
		foreach ( $fields as $field ) {
			$fields_ids[] = $field->get_id();
		}
		return rest_ensure_response( $fields_ids );
	}
	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		$this->queries->add_routes();
		$this->fields->add_routes();
		$this->styles->add_routes();
		$this->bulk->add_routes();
		$this->data->add_routes();
		$this->features->add_routes();
		$this->integrations->add_routes();

		Settings::add_routes();

		register_rest_route(
			'search-filter/v1',
			'/fields/context/ids',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_context_fields_ids' ),
				'args'                => array(
					'context'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context_path' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		// TODO - move admin/pages into admin/data?
		register_rest_route(
			'search-filter/v1',
			'/admin/pages',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_pages' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/admin/data',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_data' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/pro/enable',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'enable_pro' ),
				'args'                => array(
					'nonce' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/screen/options',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_screen_options' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/screen/dashboard',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dashboard_data' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/screen/(?P<section>[a-zA-Z0-9_-]+)',
			array(
				'args' => array(
					'section' => array(
						'description'       => __( 'Section of the resource.', 'search-filter' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_screen_options' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'options' => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
					),
				),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/field-input-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_field_input_types' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/styles/default',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_default_styles' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/admin/styles/default',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'set_default_styles' ),
				'permission_callback' => array( $this, 'permissions' ),
				'args'                => array(
					'id' => array(
						'type'              => 'number',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/admin/settings/bulk',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'get_admin_settings_bulk' ),
				'args'                => array(
					'sections' => array(
						'type'              => 'array',
						'required'          => true,
						'sanitize_callback' => array( $this, 'sanitize_sections' ),
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/admin/settings',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_settings' ),
				'args'                => array(
					'section' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/admin/notices',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_notices' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/admin/notices/(?P<id>\S+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Unique identifier for the resource.', 'search-filter' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_admin_notice' ),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/field',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_field' ),
				'args'                => array(
					'query_id' => array(
						'type'              => 'number',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'field'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/field/data',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_field_data' ),
				'args'                => array(
					'attributes' => array(
						'type'              => 'array',
						'required'          => true,
						'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/options/query_post_types',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'query_post_types' ),
				'args'                => array(
					'integrationType'  => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'archiveType'      => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'queryIntegration' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'taxonomy'         => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'postType'         => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/options/taxonomies',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_taxonomies_options' ),
				'args'                => array(
					'queryId' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/options/taxonomy-terms',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_taxonomy_terms_options' ),
				'args'                => array(
					'dataTaxonomy' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/options/post-types',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_post_types_options' ),
				'args'                => array(
					'queryId' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/results-url',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_results_url' ),
				'args'                => array(
					'integrationType' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'archiveType'     => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'taxonomy'        => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'postType'        => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'singleLocation'  => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/options/post-stati',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_post_stati_options' ),
				'args'                => array(
					'queryId' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/options/queries',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_queries_options' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/options/styles',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_styles_options' ),
				'permission_callback' => array( $this, 'permissions' ),
				'allow_batch'         => true,
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/settings/batch',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'get_batched_requests' ),
				'permission_callback' => array( $this, 'permissions' ),
				'args'                => array(
					'requests' => array(
						'required' => true,
						'type'     => 'array',
						// 'maxItems' => $this->get_max_batch_size(),
						'items'    => array(
							'type'       => 'object',
							'properties' => array(
								'method'  => array(
									'type'              => 'string',
									'enum'              => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
									'default'           => 'GET',
									'sanitize_callback' => 'sanitize_text_field',
								),
								'path'    => array(
									'type'              => 'string',
									'required'          => true,
									'sanitize_callback' => 'sanitize_text_field',
								),
								'body'    => array(
									'type'                 => 'object',
									'properties'           => array(),
									'additionalProperties' => true,
									'sanitize_callback'    => 'Search_Filter\\Core\\Sanitize::deep_clean',
								),
								'headers' => array(
									'type'                 => 'object',
									'properties'           => array(),
									'additionalProperties' => array(
										'type'  => array( 'string', 'array' ),
										'items' => array(
											'type' => 'string',
										),
									),
									'sanitize_callback'    => 'Search_Filter\\Core\\Sanitize::deep_clean',
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Get the batched requests.
	 *
	 * @param \WP_REST_Request $request The request.
	 */
	public function get_batched_requests( \WP_REST_Request $request ) {
		$requests = $request->get_param( 'requests' );

		$responses = array();

		foreach ( $requests as $request ) {

			// If path doesn't start with /search-filter/v1, then skip it.
			if ( strpos( $request['path'], '/search-filter/v1' ) !== 0 ) {
				continue;
			}

			$responses = rest_preload_api_request(
				$responses,
				array(
					'path'   => $request['path'],
					'method' => $request['method'],
				)
			);
		}

		return rest_ensure_response( $responses );
	}

	/**
	 * Get the post types availabe for the query editor.
	 *
	 * @param array $params Parameters from the request.
	 */
	public function get_query_post_types( $params ) {

		$default_data = array(
			'disabled' => false,
			'message'  => false,
		);

		$post_type_data = array();

		if ( ! isset( $params['integrationType'] ) ) {
			return false;
		}

		$integration_type = $params['integrationType'];

		switch ( $integration_type ) {
			case 'basic':
				$post_type_data = array(
					'disabled' => true,
					'value'    => array(),
					'message'  => __( 'Setting the post type is not available with your Query Integration configuration.', 'search-filter' ),
				);

				break;
			case 'results_page':
				$post_type_data = array();
				break;
			case 'archive':
				if ( ! isset( $params['archiveType'] ) ) {
					break;
				}
				$archive_type = $params['archiveType'];
				if ( 'post_type' === $archive_type && isset( $params['postType'] ) ) {
					$post_type      = $params['postType'];
					$post_type_data = array(
						'disabled' => true,
						'value'    => array( $post_type ),
						'message'  => __( 'Synced with integration settings.', 'search-filter' ),
					);

				} elseif ( 'taxonomy' === $archive_type && isset( $params['taxonomy'] ) ) {
					$taxonomy = get_taxonomy( $params['taxonomy'] );
					if ( $taxonomy ) {
						$post_type_data = array(
							'disabled' => true,
							'value'    => is_array( $taxonomy->object_type ) ? array_values( $taxonomy->object_type ) : array( $taxonomy->object_type ),
							'message'  => __( 'This option is restricted by the Taxonomy Archive you have selected.', 'search-filter' ),
						);
					} else {
						// TODO - error.
					}
				} else {
					// TODO - error.
				}
				break;
			case 'search':
				$post_type_data = array();

				break;
		}
		$post_type_data = apply_filters( 'search-filter/rest-api/get_query_post_types', $post_type_data, $params );

		return wp_parse_args( $post_type_data, $default_data );
	}

	/**
	 * Returns the post types as rest data.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function query_post_types( WP_REST_Request $request ) {
		$display_params = $request->get_params();
		$post_type_data = $this->get_query_post_types( $display_params );

		$data = array(
			'options' => Settings::get_post_types( array( 'publicly_queryable' => true ), 'or' ),
		);
		$json = array_merge( $data, $post_type_data );
		return rest_ensure_response( $json );
	}

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the available taxonomies based on the current query settings.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_taxonomies_options( WP_REST_Request $request ) {
		$query_id = $request->get_param( 'queryId' );
		// If no query param has been passed fetch all taxonomies with archive enabled.
		if ( is_null( $query_id ) || empty( $query_id ) ) {
			$json_taxonomies = Settings::get_taxonomies_w_archive();
			$json            = array(
				'options' => $json_taxonomies,
			);
			return rest_ensure_response( $json );
		}
		// If no query param has been passed fetch all post types with archive enabled.
		// TODO - consider showing all taxonomies rather than limiting it to queryId.
		$query = Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			return array(
				'options' => array(),
			);
		}

		$post_types = array();

		if ( $query->get_attribute( 'archiveType' ) === 'taxonomy' ) {
			$taxonomy = get_taxonomy( $query->get_attribute( 'taxonomy' ) );
			if ( ! $taxonomy ) {
				return array(
					'options' => array(),
				);
			}
			$post_types = is_array( $taxonomy->object_type ) ? $taxonomy->object_type : array( $taxonomy->object_type );

		} elseif ( $query->get_attribute( 'archiveType' ) === 'post_type' ) {
			$post_types = $query->get_attribute( 'postTypes' );
		}
		// Grab all the taxonomy associated with the selecte post types as an array.
		$taxonomy_names = array();

		if ( $post_types ) {
			foreach ( $post_types as $post_type ) {
				$taxonomy_names = array_merge( $taxonomy_names, get_object_taxonomies( $post_type, 'names' ) );
			}
			$taxonomy_names = array_unique( $taxonomy_names );
		}

		$json_taxonomies = array();
		foreach ( $taxonomy_names as $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );
			if ( $taxonomy ) {
				$item          = array();
				$item['value'] = $taxonomy->name;
				$item['label'] = $taxonomy->label;
				array_push( $json_taxonomies, $item );
			}
		}
		$json = array(
			'options' => $json_taxonomies,
		);
		return rest_ensure_response( $json );
	}

	/**
	 * Get the available taxonomy terms for a particular taxonomy.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string The request result as JSON.
	 */
	public function get_taxonomy_terms_options( WP_REST_Request $request ) {
		$taxonomy = $request->get_param( 'dataTaxonomy' );

		$options = Settings::create_taxonomy_terms_options( $taxonomy );
		$json    = array(
			'options' => $options,
		);

		return rest_ensure_response( $json );
	}


	/**
	 * Get the available post types based on the current query settings.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_post_types_options( WP_REST_Request $request ) {

		$query_id = $request->get_param( 'queryId' );

		// If no query param has been passed fetch all post types with archive enabled.
		if ( is_null( $query_id ) || empty( $query_id ) ) {
			$json_post_types = Settings::get_post_types_w_archive();
			$json            = array(
				'options' => $json_post_types,
			);
			return rest_ensure_response( $json );
		}

		// TODO - consider showing all post types rather than limiting it to queryId.
		$post_types = array();
		$query      = Query::find( array( 'id' => $query_id ) );
		if ( ! is_wp_error( $query ) ) {
			$post_types = $query->get_attribute( 'postTypes' );
		}

		// Grab all the taxonomy associated with the selecte post types as an array.
		$json_post_types = array();
		if ( $post_types ) {
			foreach ( $post_types as $post_type_name ) {
				$post_type = get_post_type_object( $post_type_name );
				if ( $post_type ) {
					$item          = array();
					$item['value'] = $post_type->name;
					$item['label'] = $post_type->label;
					array_push( $json_post_types, $item );
				}
			}
		}
		$json = array(
			'options' => $json_post_types,
		);

		return rest_ensure_response( $json );
	}

	/**
	 * Gets the results URL based on the query settings.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_results_url( WP_REST_Request $request ) {
		$display_params = $request->get_params();
		$query          = new Query();
		$query->set_attributes( $display_params );
		$results_data = $query->get_results_data( true );

		// TODO - reformat response, don't use `data` props, just return
		// the data or send an error.
		$json = array(
			'data' => $results_data,
		);
		return rest_ensure_response( $json );
	}


	/**
	 * Get the available post stati based on the current query settings.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_post_stati_options( WP_REST_Request $request ) {

		$query_id = $request->get_param( 'queryId' );

		// If no query param has been passed fetch all post stati
		if ( is_null( $query_id ) || empty( $query_id ) ) {
			$json = array(
				'options' => Settings::get_post_stati(),
			);
			return rest_ensure_response( $json );
		}

		// TODO - consider showing all post types rather than limiting it to queryId.
		$post_stati = array();
		$query      = Query::find( array( 'id' => $query_id ) );
		if ( ! is_wp_error( $query ) ) {
			$post_stati = $query->get_attribute( 'postStatus' );
		}

		// Grab all the taxonomy associated with the selecte post types as an array.
		$json_post_stati = array();
		if ( $post_stati ) {
			foreach ( $post_stati as $post_status_name ) {
				$post_status = get_post_status_object( $post_status_name );
				if ( $post_status ) {
					$item          = array();
					$item['value'] = $post_status->name;
					$item['label'] = $post_status->label;
					array_push( $json_post_stati, $item );
				}
			}
		}
		$json = array(
			'options' => $json_post_stati,
		);

		return rest_ensure_response( $json );
	}
	/**
	 * Gets the list of saved queries.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_queries_options( WP_REST_Request $request ) {

		// Important: always use this function to get the list, so we can
		// reliably get the first query ID for preloading.
		$queries_list = Queries::get_queries_list();
		$queries      = array();
		foreach ( $queries_list as $item ) {
			$query_data = array(
				'value' => strval( $item->id ),
				'label' => $item->name,
			);

			array_push( $queries, $query_data );
		}
		$return = array(
			'options' => $queries,
		);
		return rest_ensure_response( $return );
	}


	/**
	 * Gets the list of saved styles.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return string The request result as JSON.
	 */
	public function get_styles_options( WP_REST_Request $request ) {
		$params = $request->get_params();

		$defaults   = array(
			'no_found_rows' => true,
			'status'        => 'enabled',
		);
		$query_args = wp_parse_args( $params, $defaults );
		$query      = new \Search_Filter\Database\Queries\Style_Presets( $query_args );
		$styles     = array(
			array(
				'value' => '0',
				'label' => 'Default',
			),
		);
		foreach ( $query->items as $item ) {
			$style_data = array(
				'value' => strval( $item->id ),
				'label' => $item->name,
			);

			array_push( $styles, $style_data );
		}
		// TODO - reformat response, don't use `data` props, just return
		// the data or send an error.
		$return = array(
			'options' => $styles,
		);
		return rest_ensure_response( $return );
	}
}
