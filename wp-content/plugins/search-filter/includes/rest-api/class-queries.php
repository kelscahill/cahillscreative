<?php
/**
 * Description of class
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Rest_API;

use Search_Filter\Database\Queries\Queries as Queries_Query;
use Search_Filter\Queries\Query;
use Search_Filter\Queries\Settings;
use Search_Filter\Rest_API;
use Search_Filter\Settings\Sanitize;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles query record operations via REST API.
 *
 * @since 3.0.0
 */
class Queries {
	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Add rest routes.
	 */
	public function add_routes() {
		register_rest_route(
			'search-filter/v1',
			'/records/queries',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_records' ),
					'args'                => array(
						'paged'    => array(
							'type'              => 'number',
							'required'          => false,
							'sanitize_callback' => 'absint',
						),
						'orderby'  => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'order'    => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'status'   => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'per_page' => array(
							'type'              => 'number',
							'required'          => false,
							'sanitize_callback' => 'absint',
						),
						'context'  => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_record' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'name'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'status'      => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes'  => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'context'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'integration' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/records/queries/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Unique identifier for the resource.', 'search-filter' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_record' ),
					'permission_callback' => array( $this, 'permissions' ),

				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_record' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'name'        => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'status'      => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes'  => array(
							'type'              => 'object',
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_attributes' ),
						),
						'context'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'integration' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_record' ),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/records/counts/queries',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_records_section_counts' ),
				'permission_callback' => array( $this, 'permissions' ),
				'args'                => array(
					'query_id' => array(
						'required'          => false,
						'description'       => __( 'Query ID.', 'search-filter' ),
						'type'              => 'number',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get the counts for a section
	 */
	public function get_records_section_counts() {
		$query      = new Queries_Query();
		$args       = array();
		$count_data = Util::get_records_section_status_counts( $query, $args );
		return rest_ensure_response( $count_data );
	}

	/**
	 * Fetch queries.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function get_records( \WP_REST_Request $request ) {
		$params     = $request->get_params();
		$query_args = $params;
		$query_args = apply_filters( 'search-filter/rest-api/records/queries/get_records/query_args', $query_args );

		if ( isset( $query_args['_locale'] ) ) {
			unset( $query_args['_locale'] );
		}
		if ( isset( $query_args['context'] ) ) {
			unset( $query_args['context'] );
		}

		$query_args  = Util::get_records_query_args( $query_args );
		$query       = new Queries_Query( $query_args );
		$query_items = $query->items;
		$records     = array();

		foreach ( $query_items as $record ) {
			$query_record = array(
				'id'            => $record->get_id(),
				'name'          => $record->get_name(),
				'status'        => $record->get_status(),
				'attributes'    => $record->get_attributes(),
				'date_created'  => $record->get_date_created(),
				'date_modified' => $record->get_date_modified(),
				'context'       => $record->get_context(),
				'integration'   => $record->get_integration(),
			);

			$query_item = Query::get_instance( $record->get_id() );
			if ( ! is_wp_error( $query_item ) ) {
				$query_record['fields'] = count( $query_item->get_fields() );
			}
			array_push( $records, $query_record );
		}

		$records = apply_filters( 'search-filter/rest-api/records/queries/get_records/records', $records );

		$records_response = rest_ensure_response( $records );

		// These headers are using on all our admin screen queries for total count + paged,
		// but they are only used with `getEntityRecords` when the `per_page`
		// param is passed in the request when using.
		$records_response->header( 'X-WP-Total', (string) $query->found_items );
		$records_response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $records_response;
	}

	/**
	 * Create a new record.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function create_record( \WP_REST_Request $request ) {
		$params      = $request->get_params();
		$context     = isset( $params['context'] ) ? $params['context'] : '';
		$integration = isset( $params['integration'] ) ? $params['integration'] : '';

		$section_instance = new Query();
		$section_instance->set_name( $params['name'] );
		$section_instance->set_attributes( $params['attributes'], true );
		$section_instance->set_status( $params['status'] );
		$section_instance->set_context( $context );
		$section_instance->set_integration( $integration );

		$section_instance = apply_filters( 'search-filter/rest-api/queries/create_record/instance', $section_instance, 'queries', $params );

		// Save to DB.
		$result_id = $section_instance->save();

		do_action( 'search-filter/rest-api/queries/create_record', $section_instance );

		return rest_ensure_response( $section_instance->get_record() );
	}

	/**
	 * Update existing record.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function update_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Query( $id );
		if ( isset( $params['status'] ) ) {
			$section_instance->set_status( $params['status'] );
		}
		if ( isset( $params['name'] ) ) {
			$section_instance->set_name( $params['name'] );
		}
		if ( isset( $params['attributes'] ) ) {
			$section_instance->set_attributes( $params['attributes'], true );
		}
		if ( isset( $params['context'] ) ) {
			$section_instance->set_context( $params['context'] );
		}
		if ( isset( $params['integration'] ) ) {
			$section_instance->set_integration( $params['integration'] );
		}

		$result = $section_instance->save();

		do_action( 'search-filter/rest-api/queries/update_record', $section_instance );

		// Return the complete updated record for the query.
		return rest_ensure_response( $section_instance->get_record() );
	}

	/**
	 * Delete existing record.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function delete_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Query( $id );

		$section_instance->delete();

		// Return the complete updated record for the query.
		$response = array( 'id' => $id );
		return rest_ensure_response( $response );
	}

	/**
	 * Delete trashed records.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function delete_trashed_records( \WP_REST_Request $request ) {

		// TODO - this doesn't work with our new paradigm... need to fix.
		$params = $request->get_params();

		$query = new Queries_Query();
		$query->delete_items_with_status( 'trashed' );

		// Return the complete updated record for the query.
		$response = array(
			'code' => 'success',
		);
		return rest_ensure_response( $response );
	}


	/**
	 * Fetch record by section and ID.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error REST response or error.
	 */
	public function get_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = absint( $params['id'] );

		// Then this won't be a real record...
		$record = apply_filters( 'search-filter/admin/get_record/pre_lookup', false, $id, 'queries' );
		if ( $record && ! is_wp_error( $record ) ) {
			$response = array(
				'code' => 'success',
				'data' => $record,
			);
			return rest_ensure_response( $response );
		}

		$instance = Query::get_instance( $id );

		// Bail if nothing found.
		if ( is_wp_error( $instance ) ) {
			return \rest_convert_error_to_response( new \WP_Error( 'not_found', 'Not found.', array( 'status' => 404 ) ) );
		}

		$item = $instance->get_record();
		// TODO - this is a bit clunky, what if we need other columns...
		// Maybe just return the actual record?
		$record = array(
			'id'            => $item->get_id(),
			'name'          => $item->get_name(),
			'status'        => $item->get_status(),
			'attributes'    => $item->get_attributes(),
			'date_created'  => $item->get_date_created(),
			'date_modified' => $item->get_date_modified(),
			'context'       => $item->get_context(),
			'integration'   => $item->get_integration(),
		);

		$record = apply_filters( 'search-filter/admin/get_record/queries/record', $record, $id, $item );

		return rest_ensure_response( $record );
	}

	/**
	 * Sanitize attributes
	 *
	 * Allows for granular control over each settings sanitization
	 * via a settings `sanitize` property.
	 *
	 * @param array $attributes The attributes to sanitize.
	 * @return array The sanitized attributes.
	 */
	public function sanitize_attributes( $attributes ) {
		$settings = Settings::get();
		return Sanitize::settings( $attributes, $settings );
	}
}
