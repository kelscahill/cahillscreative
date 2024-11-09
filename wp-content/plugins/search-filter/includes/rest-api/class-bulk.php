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

use WP_Error;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Bulk {
	private $section_record_classes = array(
		'queries' => 'Search_Filter\\Queries\\Query',
		'fields'  => 'Search_Filter\\Fields\\Field',
		'styles'  => 'Search_Filter\\Styles\\Style',
	);
	private $query_classes          = array(
		'queries' => '\Search_Filter\Database\Queries\Queries',
		'fields'  => '\Search_Filter\Database\Queries\Fields',
		'styles'  => '\Search_Filter\Database\Queries\Style_Presets',
	);
	public function __construct() {
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
	 * Add rest routes.
	 */
	public function add_routes() {
		register_rest_route(
			'search-filter/v1',
			'/records/bulk',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_records' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'ids'        => array(
							'required'          => true,
							'description'       => __( 'Unique identifier for the resource.', 'search-filter' ),
							'type'              => 'array',
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'section'    => array(
							'required'          => true,
							'description'       => __( 'Section of the resource.', 'search-filter' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
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
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_records' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'ids'     => array(
							'required'          => true,
							'description'       => __( 'Unique identifier for the resource.', 'search-filter' ),
							'type'              => 'array',
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'section' => array(
							'required'          => true,
							'description'       => __( 'Section of the resource.', 'search-filter' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/records/trashed',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_trashed_records' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'section' => array(
							'required'          => true,
							'description'       => __( 'Section of the resource.', 'search-filter' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/records/import/(?P<section>[a-zA-Z0-9_-]+)',
			array(
				'args' => array(
					'section' => array(
						'description'       => __( 'Section of the resource.', 'search-filter' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_records' ),
					'args'                => array(
						'data' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);
	}
	/**
	 * Fetch records ( queries / fields /styles )
	 */
	public function import_records( \WP_REST_Request $request ) {
		$params  = $request->get_params();
		$section = $params['section'];
		$records = json_decode( $params['data'], true );

		if ( $records === null || ! is_array( $records ) ) {
			// Return early with a failure.
			$response = new WP_Error(
				'rest_invalid_json',
				__( 'Invalid JSON data.', 'search-filter' ),
				array( 'status' => 400 )
			);
			return rest_convert_error_to_response( $response );
		}

		$saved_ids = array();
		// Try to import the records.
		foreach ( $records as $record_data ) {
			$record_instance = new $this->section_record_classes[ $section ]();
			$record_instance->set_name( sanitize_text_field( $record_data['name'] ) );
			$record_instance->set_status( sanitize_text_field( $record_data['status'] ) );
			$record_instance->set_attributes( \Search_Filter\Core\Sanitize::deep_clean( $record_data['attributes'] ) );
			$record_id   = $record_instance->save();
			$saved_ids[] = $record_id;
		}
		$response = $saved_ids;
		return rest_ensure_response( $response );
	}
	/**
	 * Bulk update records
	 */
	public function update_records( \WP_REST_Request $request ) {

		$params  = $request->get_params();
		$ids     = $params['ids'];
		$section = $params['section'];

		foreach ( $ids as $id ) {
			// Prepare the record.
			$section_instance = new $this->section_record_classes[ $section ]( $id );

			if ( isset( $params['name'] ) ) {
				$section_instance->set_name( $params['name'] );
			}
			if ( isset( $params['status'] ) ) {
				$section_instance->set_status( $params['status'] );
			}
			if ( isset( $params['attributes'] ) ) {
				$section_instance->set_attributes( $params['attributes'] );
			}

			$section_instance->save();
		}

		$response = 'success';
		return rest_ensure_response( $response );
	}
	/**
	 * Bulk delete records
	 */
	public function delete_records( \WP_REST_Request $request ) {

		$params  = $request->get_params();
		$ids     = $params['ids'];
		$section = $params['section'];

		foreach ( $ids as $id ) {
			// Prepare the record.
			$section_instance = new $this->section_record_classes[ $section ]( $id );
			$section_instance->delete();
		}

		// Return the complete updated record for the query.
		$response = 'success';
		return rest_ensure_response( $response );
	}
	/**
	 * Delete trashed records
	 */
	public function delete_trashed_records( \WP_REST_Request $request ) {

		// TODO - this doesn't work with our new paradigm... need to fix.
		$params  = $request->get_params();
		$section = $params['section'];

		$query = new $this->query_classes[ $section ]();
		$query->delete_items_with_status( 'trashed' );

		// Return the complete updated record for the query.
		$response = 'success';
		return rest_ensure_response( $response );
	}
}
