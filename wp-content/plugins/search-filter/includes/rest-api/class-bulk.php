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
 * Handles bulk operations for records via REST API.
 *
 * @since 3.0.0
 */
class Bulk {
	/**
	 * Maps section names to their corresponding record class names.
	 *
	 * @var array<string, string>
	 */
	private $section_record_classes = array(
		'queries' => 'Search_Filter\\Queries\\Query',
		'fields'  => 'Search_Filter\\Fields\\Field',
		'styles'  => 'Search_Filter\\Styles\\Style',
	);

	/**
	 * Maps section names to their corresponding database query class names.
	 *
	 * @var array<string, string>
	 */
	private $query_classes = array(
		'queries' => '\Search_Filter\Database\Queries\Queries',
		'fields'  => '\Search_Filter\Database\Queries\Fields',
		'styles'  => '\Search_Filter\Database\Queries\Style_Presets',
	);

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
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
		// New endpoint for exporting all sections - register before parameterized routes.
		register_rest_route(
			'search-filter/v1',
			'/records/export/all',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_all_records' ),
					'args'                => array(
						'queries'  => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'fields'   => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'styles'   => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'settings' => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);
		// New endpoint for importing all sections - register before parameterized routes.
		register_rest_route(
			'search-filter/v1',
			'/records/import/all',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_all_records' ),
					'args'                => array(
						'data'            => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'update_existing' => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);
		// New endpoint for exporting specific sections with optional IDs.
		register_rest_route(
			'search-filter/v1',
			'/records/export/(?P<section>[a-zA-Z0-9_-]+)',
			array(
				'args' => array(
					'section' => array(
						'description'       => __( 'Section of the resource (queries, fields, or styles).', 'search-filter' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_section' ),
					'args'                => array(
						'ids' => array(
							'type'              => 'array',
							'items'             => array(
								'type' => 'integer',
							),
							'required'          => false,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);
	}
	/**
	 * Export specific section records with optional IDs filtering.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error REST response or error.
	 */
	public function export_section( \WP_REST_Request $request ) {
		$params  = $request->get_params();
		$section = $params['section'];
		$ids     = isset( $params['ids'] ) ? $params['ids'] : null;

		// Validate section.
		if ( ! in_array( $section, array( 'queries', 'fields', 'styles' ), true ) ) {
			return new WP_Error(
				'rest_invalid_section',
				__( 'Invalid section. Must be queries, fields, or styles.', 'search-filter' ),
				array( 'status' => 400 )
			);
		}

		$export_data = array();

		switch ( $section ) {
			case 'styles':
				$query_args = array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ),
				);
				if ( $ids ) {
					$query_args['id__in'] = $ids;
				}
				$styles_query          = new \Search_Filter\Database\Queries\Style_Presets( $query_args );
				$export_data['styles'] = array();
				foreach ( $styles_query->items as $style ) {
					$export_data['styles'][] = array(
						'id'         => $style->get_id(),
						'name'       => $style->get_name(),
						'status'     => $style->get_status(),
						'attributes' => $style->get_attributes(),
						'tokens'     => $style->get_tokens(),
						'context'    => $style->get_context(),
					);
				}
				break;

			case 'queries':
				$query_args = array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ),
				);
				if ( $ids ) {
					$query_args['id__in'] = $ids;
				}
				$queries_query          = new \Search_Filter\Database\Queries\Queries( $query_args );
				$export_data['queries'] = array();
				foreach ( $queries_query->items as $query ) {
					$export_data['queries'][] = array(
						'id'         => $query->get_id(),
						'name'       => $query->get_name(),
						'status'     => $query->get_status(),
						'attributes' => $query->get_attributes(),
					);
				}
				break;

			case 'fields':
				$query_args = array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ),
				);
				if ( $ids ) {
					$query_args['id__in'] = $ids;
				}
				$fields_query          = new \Search_Filter\Database\Queries\Fields( $query_args );
				$export_data['fields'] = array();
				foreach ( $fields_query->items as $field ) {
					$export_data['fields'][] = array(
						'id'           => $field->get_id(),
						'name'         => $field->get_name(),
						'status'       => $field->get_status(),
						'attributes'   => $field->get_attributes(),
						'context'      => $field->get_context(),
						'context_path' => $field->get_context_path(),
					);
				}
				break;
		}

		return rest_ensure_response( $export_data );
	}
	/**
	 * Bulk update records.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
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
	 * Bulk delete records.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
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
	 * Delete trashed records.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
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
	/**
	 * Export all records from selected sections.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function export_all_records( \WP_REST_Request $request ) {
		$params = $request->get_params();

		$export_data = array();

		// Export styles if requested.
		if ( $params['styles'] ) {
			$styles_query          = new \Search_Filter\Database\Queries\Style_Presets(
				array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ), // Export all statuses.
				)
			);
			$export_data['styles'] = array();
			foreach ( $styles_query->items as $style ) {
				$export_data['styles'][] = array(
					'id'         => $style->get_id(),
					'name'       => $style->get_name(),
					'status'     => $style->get_status(),
					'attributes' => $style->get_attributes(),
					'tokens'     => $style->get_tokens(),
					'context'    => $style->get_context(),
				);
			}
		}

		// Export queries if requested.
		if ( $params['queries'] ) {
			$queries_query          = new \Search_Filter\Database\Queries\Queries(
				array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ), // Export all statuses.
				)
			);
			$export_data['queries'] = array();
			foreach ( $queries_query->items as $query ) {
				$export_data['queries'][] = array(
					'id'         => $query->get_id(),
					'name'       => $query->get_name(),
					'status'     => $query->get_status(),
					'attributes' => $query->get_attributes(),
				);
			}
		}

		// Export fields if requested.
		if ( $params['fields'] ) {
			$fields_query          = new \Search_Filter\Database\Queries\Fields(
				array(
					'number' => 0, // Get all.
					'status' => array( 'enabled', 'disabled', 'trashed' ), // Export all statuses.
				)
			);
			$export_data['fields'] = array();
			foreach ( $fields_query->items as $field ) {
				$export_data['fields'][] = array(
					'id'           => $field->get_id(),
					'name'         => $field->get_name(),
					'status'       => $field->get_status(),
					'attributes'   => $field->get_attributes(),
					'context'      => $field->get_context(),
					'context_path' => $field->get_context_path(),
				);
			}
		}

		// Export settings if requested.
		if ( $params['settings'] ) {
			$export_data['settings'] = \Search_Filter\Settings::get_all_settings_data();
		}

		return rest_ensure_response( $export_data );
	}
	/**
	 * Import all records with ID preservation and remapping.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error REST response or error.
	 */
	public function import_all_records( \WP_REST_Request $request ) {
		$params          = $request->get_params();
		$import_data     = \Search_Filter\Core\Sanitize::deep_clean( json_decode( $params['data'], true ) );
		$update_existing = isset( $params['update_existing'] ) ? $params['update_existing'] : true;

		if ( $import_data === null || ! is_array( $import_data ) ) {
			return new WP_Error(
				'rest_invalid_json',
				__( 'Invalid JSON data.', 'search-filter' ),
				array( 'status' => 400 )
			);
		}

		$id_mappings = array();
		$results     = array(
			'styles'  => array(),
			'queries' => array(),
			'fields'  => array(),
		);

		// Step 1: Import styles first and build mapping.
		if ( isset( $import_data['styles'] ) ) {
			foreach ( $import_data['styles'] as $style_data ) {
				$old_id = isset( $style_data['id'] ) ? absint( $style_data['id'] ) : 0;

				// If update_existing is true, try to preserve the original ID.
				$style = new \Search_Filter\Styles\Style();
				if ( $update_existing && $old_id !== 0 ) {
					$existing_style = \Search_Filter\Styles\Style::find( array( 'id' => $old_id ) );
					if ( is_wp_error( $existing_style ) ) {
						// ID is available, try to use it.
						$style->set_id( $old_id );
					} else {
						// ID exists, update the existing item.
						$style = $existing_style;
					}
				}
				// If update_existing is false, always create a new item.

				$style->set_name( $style_data['name'] );
				$style->set_status( $style_data['status'] );
				if ( isset( $style_data['attributes'] ) ) {
					$style->set_attributes( $style_data['attributes'] );
				}
				if ( isset( $style_data['tokens'] ) ) {
					$style->set_tokens( $style_data['tokens'] );
				}
				if ( isset( $style_data['context'] ) ) {
					$style->set_context( $style_data['context'] );
				}
				$new_id = $style->save();

				// Only store mapping if ID changed.
				if ( $old_id !== 0 && $old_id !== $new_id ) {
					if ( ! isset( $id_mappings['styles'] ) ) {
						$id_mappings['styles'] = array();
					}
					$id_mappings['styles'][ $old_id ] = $new_id;
				}
				$results['styles'][] = $new_id;
			}
		}

		// Step 2: Import queries second and build mapping.
		if ( isset( $import_data['queries'] ) ) {
			foreach ( $import_data['queries'] as $query_data ) {
				$old_id = isset( $query_data['id'] ) ? absint( $query_data['id'] ) : 0;

				// If update_existing is true, try to preserve the original ID.
				$query = new \Search_Filter\Queries\Query();
				if ( $update_existing && $old_id !== 0 ) {
					$existing_query = \Search_Filter\Queries\Query::find( array( 'id' => $old_id ) );
					if ( is_wp_error( $existing_query ) ) {
						// ID is available, try to use it.
						$query->set_id( $old_id );
					} else {
						// ID exists, update the existing item.
						$query = $existing_query;
					}
				}
				// If update_existing is false, always create a new item.

				$query->set_name( $query_data['name'] );
				$query->set_status( $query_data['status'] );

				// Check if query references styles and remap if needed.
				$attributes = isset( $query_data['attributes'] ) ? $query_data['attributes'] : array();
				$query->set_attributes( $attributes );
				$new_id = $query->save();

				// Only store mapping if ID changed.
				if ( $old_id !== 0 && $old_id !== $new_id ) {
					if ( ! isset( $id_mappings['queries'] ) ) {
						$id_mappings['queries'] = array();
					}
					$id_mappings['queries'][ $old_id ] = $new_id;
				}
				$results['queries'][] = $new_id;
			}
		}

		// Step 3: Import fields last with full remapping.
		if ( isset( $import_data['fields'] ) ) {
			foreach ( $import_data['fields'] as $field_data ) {
				$old_id = isset( $field_data['id'] ) ? absint( $field_data['id'] ) : 0;

				// If update_existing is true, try to preserve the original ID.
				$field = new \Search_Filter\Fields\Field();
				if ( $update_existing && $old_id !== 0 ) {
					$existing_field = \Search_Filter\Fields\Field::find( array( 'id' => $old_id ) );
					if ( is_wp_error( $existing_field ) ) {
						// ID is available, try to use it.
						$field->set_id( $old_id );
					} else {
						// ID exists, update the existing item.
						$field = $existing_field;
					}
				}
				// If update_existing is false, always create a new item.

				$field->set_name( $field_data['name'] );
				$field->set_status( $field_data['status'] );

				// Remap field attributes.
				$attributes = isset( $field_data['attributes'] ) ? $field_data['attributes'] : array();

				// Remap query ID if exists.
				$query_id = isset( $attributes['queryId'] ) ? absint( $attributes['queryId'] ) : 0;
				if ( isset( $id_mappings['queries'][ $query_id ] ) ) {
					$attributes['queryId'] = (string) $id_mappings['queries'][ $query_id ];
				}

				// Remap styles ID if exists.
				$styles_id = isset( $attributes['stylesId'] ) ? absint( $attributes['stylesId'] ) : 0;
				if ( isset( $id_mappings['styles'][ $styles_id ] ) ) {
					$attributes['stylesId'] = (string) $id_mappings['styles'][ $styles_id ];
				}

				$field->set_attributes( $attributes );

				// Set context and context_path if provided.
				if ( isset( $field_data['context'] ) ) {
					$field->set_context( $field_data['context'] );
				}
				if ( isset( $field_data['context_path'] ) ) {
					$field->set_context_path( $field_data['context_path'] );
				}

				$new_id = $field->save();

				// Only store mapping if ID changed.
				if ( $old_id !== 0 && $old_id !== $new_id ) {
					if ( ! isset( $id_mappings['fields'] ) ) {
						$id_mappings['fields'] = array();
					}
					$id_mappings['fields'][ $old_id ] = $new_id;
				}
				$results['fields'][] = $new_id;
			}
		}

		// Step 4: Import settings if provided.
		if ( isset( $import_data['settings'] ) && ! empty( $import_data['settings'] ) ) {
			\Search_Filter\Settings::set_all_settings_data( $import_data['settings'] );
			$results['settings'] = true;
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'imported' => $results,
				'mappings' => $id_mappings, // Useful for debugging.
			)
		);
	}
}
