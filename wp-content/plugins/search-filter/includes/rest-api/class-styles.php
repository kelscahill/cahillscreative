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

use Search_Filter\Database\Queries\Style_Presets as Styles_Query;
use Search_Filter\Rest_API;
use Search_Filter\Styles\Style;
use Search_Filter\Styles as Styles_Main;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Styles {
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
			'/records/styles',
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
						'name'       => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'status'     => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes' => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'context'    => array(
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
			'/records/styles/(?P<id>[\d]+)',
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
						'name'       => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),

						'status'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes' => array(
							'type'              => 'object',
							'required'          => false,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'context'    => array(
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
			'/records/counts/styles',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_records_section_counts' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
	}


	/**
	 * Get the counts for a section
	 */
	public function get_records_section_counts() {
		$query      = new Styles_Query();
		$args       = array();
		$count_data = Util::get_records_section_status_counts( $query, $args );
		return rest_ensure_response( $count_data );
	}
	/**
	 * Fetch styles records
	 */
	public function get_records( \WP_REST_Request $request ) {
		$params     = $request->get_params();
		$query_args = $params;

		$query_args = apply_filters( 'search-filter/rest-api/records/styles/get_records/query_args', $query_args );
		$query_args = Util::get_records_query_args( $query_args );

		$query_args['orderby'] = 'context';
		$query_args['order']   = 'DESC';

		$query       = new Styles_Query( $query_args );
		$query_items = $query->items;
		$records     = array();

		if ( $query ) {
			// $records_data->max_num_pages = $query->max_num_pages;
			foreach ( $query_items as $record ) {
				$query_record = array(
					'id'            => $record->get_id(),
					'name'          => $record->get_name(),
					'status'        => $record->get_status(),
					'attributes'    => $record->get_attributes(),
					'date_created'  => $record->get_date_created(),
					'date_modified' => $record->get_date_modified(),
					'context'       => $record->get_context(),
				);

				array_push( $records, $query_record );
			}
		}

		$records = apply_filters( 'search-filter/rest-api/records/styles/get_records/records_data', $records );

		$records_response = rest_ensure_response( $records );

		if ( $query ) {
			// These headers are using on all our admin screen queries for total count + paged,
			// but they are only used with `getEntityRecords` when the `per_page`
			// param is passed in the request when using,
			$records_response->header( 'X-WP-Total', $query->found_items );
			$records_response->header( 'X-WP-TotalPages', $query->max_num_pages );
		}

		return $records_response;
	}

	/**
	 * Create a new record.
	 */
	public function create_record( \WP_REST_Request $request ) {
		$params  = $request->get_params();
		$context = isset( $params['context'] ) ? $params['context'] : '';

		$section_instance = new Style();
		$section_instance->set_name( $params['name'] );
		$section_instance->set_attributes( $params['attributes'], true );
		$section_instance->set_status( $params['status'], true );
		$section_instance->set_context( $context );

		$section_instance = apply_filters( 'search-filter/rest-api/styles/create_record/instance', $section_instance, 'styles', $params );

		// Save to DB.
		$result_id = $section_instance->save();

		do_action( 'search-filter/rest-api/styles/create_record', $section_instance, $params );

		return rest_ensure_response( $section_instance->get_record() );
	}
	/**
	 * Update existing record.
	 */
	public function update_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Style( $id );
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
		$result = $section_instance->save();

		do_action( 'search-filter/rest-api/styles/update_record', $section_instance, $params );

		// Return the complete updated record for the query.
		return rest_ensure_response( $section_instance->get_record() );
	}

	/**
	 * Update existing record
	 */
	public function delete_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Style( $id );
		$section_instance->delete();

		// Return the complete updated record for the query.
		$response = array( 'id' => $id );
		return rest_ensure_response( $response );
	}
	/**
	 * Delete trashed records
	 */
	public function delete_trashed_records( \WP_REST_Request $request ) {

		// TODO - this doesn't work with our new paradigm... need to fix.
		$params = $request->get_params();

		$query = new Styles_Query();
		$query->delete_items_with_status( 'trashed' );

		// Return the complete updated record for the query.
		$response = array(
			'code' => 'success',
		);
		return rest_ensure_response( $response );
	}

	private function get_record_by_id( $id ) {
		$section_record = Style::find( array( 'id' => $id ) );
		// Bail if nothing found.
		if ( is_wp_error( $section_record ) ) {
			return $section_record;
		}
		return $section_record->get_record();
	}

	/**
	 * Fetch record by section and ID
	 */
	public function get_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		// Then this won't be a real record...
		$record = apply_filters( 'search-filter/admin/get_record/pre_lookup', false, $id, 'styles' );

		if ( $id === 0 ) {
			// Then we want to add the number of fields to the record.
			$default_style_id = Styles_Main::get_default_styles_id();
			$default_record   = $this->get_record_by_id( $default_style_id );
			if ( ! is_wp_error( $default_record ) ) {
				$default_record->set_id( 0 );
			}
			return $default_record;
		}

		if ( $record && ! is_wp_error( $record ) ) {
			$response = array(
				'code' => 'success',
				'data' => $record,
			);
			return rest_ensure_response( $response );
		}

		$instance = Style::find( array( 'id' => $id ) );

		// Bail if nothing found.
		if ( is_wp_error( $instance ) ) {
			return rest_convert_error_to_response( new \WP_Error( 'not_found', 'Not found.', array( 'status' => 404 ) ) );
		}

		// Create the field using its attributes.
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
		);

		$record = apply_filters( 'search-filter/admin/get_record/styles/record', $record, $id, $item );

		// TODO - We need to find a way to properly send an error response
		return rest_ensure_response( $record );
	}
}
