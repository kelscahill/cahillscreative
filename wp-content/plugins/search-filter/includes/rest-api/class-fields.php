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

use Search_Filter\Database\Queries\Fields as Fields_Query;
use Search_Filter\Fields\Field;
use Search_Filter\Queries\Query;
use Search_Filter\Settings\Sanitize;
use Search_Filter\Fields\Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields REST API endpoint handler.
 */
class Fields {
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
			'/records/fields',
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
						'query_id' => array(
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
						'name'         => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'status'       => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes'   => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => array( $this, 'sanitize_attributes' ),
						),
						'context'      => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'context_path' => array(
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
			'/records/fields/(?P<id>[\d]+)',
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
					'args'                => array(
						'return_as' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'enum'              => array( 'record', 'instance' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_record' ),
					'permission_callback' => array( $this, 'permissions' ),
					'args'                => array(
						'name'         => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),

						'status'       => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes'   => array(
							'type'              => 'object',
							'required'          => false,
							'sanitize_callback' => array( $this, 'sanitize_attributes' ),
						),
						'context'      => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'context_path' => array(
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
			'/records/counts/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_records_section_counts' ),
				'args'                => array(
					'query_id' => array(
						'required'          => false,
						'description'       => __( 'Foreign ID.', 'search-filter' ),
						'type'              => 'number',
						'sanitize_callback' => 'absint',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/fields/(?P<id>[\d]+)/locations',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_field_locations' ),
				'permission_callback' => array( $this, 'permissions' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'Unique identifier for the field.', 'search-filter' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get the counts for a section.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get_records_section_counts( \WP_REST_Request $request ) {
		$params = $request->get_params();
		// TODO - we don't want to get the query ID here - add a hook to apply it conditionally based on section.
		// TODO - same with context, we need a filter for this.
		$query_id = isset( $params['query_id'] ) ? $params['query_id'] : 0;
		$query    = new Fields_Query();

		$args = array();
		// TODO - this should be applied via a hook and only if the section is `fields`.
		if ( $query_id !== 0 ) {
			$args['query_id'] = $query_id;
		}
		$count_data = Util::get_records_section_status_counts( $query, $args );
		return rest_ensure_response( $count_data );
	}

	/**
	 * Fetch Field records.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get_records( \WP_REST_Request $request ) {
		$params     = $request->get_params();
		$query_args = wp_parse_args(
			$params,
			array(
				'context'      => '',
				'context_path' => '',
			)
		);
		$query_args = apply_filters( 'search-filter/rest-api/records/fields/get_records/query_args', $query_args, $request );

		$query_args  = Util::get_records_query_args( $query_args );
		$query       = new Fields_Query( $query_args );
		$query_items = $query->items;
		$records     = array();

		foreach ( $query_items as $query_item ) {
			$record = array(
				'id'            => $query_item->get_id(),
				'name'          => $query_item->get_name(),
				'status'        => $query_item->get_status(),
				'attributes'    => $query_item->get_attributes(),
				'date_created'  => $query_item->get_date_created(),
				'date_modified' => $query_item->get_date_modified(),
				'context'       => $query_item->get_context(),
				'context_path'  => $query_item->get_context_path(),
				'locations'     => array(),
			);

			// Add locations data.

			// Then we want to add the associated query to the record.
			// Lookup the query.
			if ( isset( $record['attributes']['queryId'] ) ) {
				$query_id        = absint( $record['attributes']['queryId'] );
				$connected_query = Query::get_instance( absint( $query_id ) );
				if ( ! is_wp_error( $connected_query ) ) {
					$record['query'] = array(
						'id'     => $connected_query->get_id(),
						'name'   => $connected_query->get_name(),
						'status' => $connected_query->get_status(),
					);
					// TODO - figure out we want to pass the whole query object or just the id and name.
				}
			}

			$record = apply_filters( 'search-filter/rest-api/records/fields/get_records/record', $record, $query_item, $request );
			array_push( $records, $record );
		}

		$records = apply_filters( 'search-filter/rest-api/records/fields/get_records/records', $records, $request );

		$records_response = rest_ensure_response( $records );

		// These headers are using on all our admin screen queries for total count + paged,
		// but they are only used with `getEntityRecords` when the `per_page`
		// param is passed in the request.
		$records_response->header( 'X-WP-Total', (string) $query->found_items );
		$records_response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $records_response;
	}

	/**
	 * Create a new record.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function create_record( \WP_REST_Request $request ) {
		$params       = $request->get_params();
		$context      = isset( $params['context'] ) ? $params['context'] : '';
		$context_path = isset( $params['context_path'] ) ? $params['context_path'] : '';

		$section_instance = new Field();
		$section_instance->set_name( $params['name'] );
		$section_instance->set_attributes( $params['attributes'], true );
		$section_instance->set_status( $params['status'] );
		$section_instance->set_context( $context );
		$section_instance->set_context_path( $context_path );

		// Save to DB.
		$result_id = $section_instance->save();

		do_action( 'search-filter/rest-api/fields/create_record', $section_instance, $params, $request );

		return rest_ensure_response( $section_instance->get_record() );
	}
	/**
	 * Update existing record.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function update_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Field( $id );
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
		if ( isset( $params['context_path'] ) ) {
			$section_instance->set_context_path( $params['context_path'] );
		}

		$result = $section_instance->save();
		do_action( 'search-filter/rest-api/fields/update_record', $section_instance, $params, $request );

		// Return the complete updated record for the query.
		return rest_ensure_response( $section_instance->get_record() );
	}

	/**
	 * Update existing record.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function delete_record( \WP_REST_Request $request ) {

		$params = $request->get_params();
		$id     = $params['id'];

		$section_instance = new Field( $id );
		$section_instance->delete();

		// Return the complete updated record for the query.
		$response = array( 'id' => $id );
		return rest_ensure_response( $response );
	}
	/**
	 * Delete trashed records.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function delete_trashed_records( \WP_REST_Request $request ) {

		// TODO - this doesn't work with our new paradigm... need to fix.
		$params = $request->get_params();

		$query = new Fields_Query();
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
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get_record( \WP_REST_Request $request ) {

		$params    = $request->get_params();
		$id        = absint( $params['id'] );
		$return_as = isset( $params['return_as'] ) ? $params['return_as'] : 'record';

		// Then this won't be a real record.
		$record = apply_filters( 'search-filter/admin/get_record/pre_lookup', false, $id, 'fields', $request );
		if ( $record && ! is_wp_error( $record ) ) {
			$response = array(
				'code' => 'success',
				'data' => $record,
			);
			return rest_ensure_response( $response );
		}

		$instance = Field::get_instance( $id );

		// Bail if nothing found.
		if ( is_wp_error( $instance ) ) {
			return rest_convert_error_to_response( new \WP_Error( 'not_found', 'Not found.', array( 'status' => 404 ) ) );
		}

		if ( $return_as === 'instance' ) {
			return rest_ensure_response( $instance->get_render_data() );
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
			'context_path'  => $item->get_context_path(),
			'locations'     => array(),
		);

		$record = apply_filters( 'search-filter/admin/get_record/fields/record', $record, $id, $item, $request );

		// TODO - We need to find a way to properly send an error response.
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

	/**
	 * Get enriched location data for a field
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response The response containing enriched location data.
	 */
	public function get_field_locations( \WP_REST_Request $request ) {
		$field_id = $request['id'];
		$field    = Field::get_instance( $field_id );

		if ( is_wp_error( $field ) ) {
			return rest_convert_error_to_response( $field );
		}

		$locations          = $field->get_locations();
		$enriched_locations = array();

		foreach ( $locations as $location ) {
			$parts = explode( '/', $location );
			$type  = $parts[0];
			$id    = isset( $parts[1] ) ? absint( $parts[1] ) : 0;

			$location_data = array(
				'type'    => $type,
				'id'      => $id,
				'raw'     => $location,
				'title'   => '',
				'editUrl' => '',
				'viewUrl' => '',
			);

			if ( $type === 'post' && $id > 0 ) {
				$post = get_post( $id );
				if ( $post ) {

					$location_data['title']    = $post->post_title ? $post->post_title : __( '(No title)', 'search-filter' );
					$location_data['editUrl']  = get_edit_post_link( $id, 'raw' );
					$location_data['status']   = $post->post_status;
					$location_data['postType'] = $post->post_type;
					// Get post type label for better display.
					$post_type_obj = get_post_type_object( $post->post_type );
					if ( $post_type_obj ) {
						$location_data['postTypeLabel'] = $post_type_obj->labels->singular_name;
					}

					$template_post_types = array( 'wp_template_part', 'wp_template' );

					// Then a regular post type.
					if ( ! in_array( $post->post_type, $template_post_types, true ) ) {
						$location_data['viewUrl'] = get_permalink( $id );
						$location_data['type']    = 'post';
					} else {
						// It's a template/template part.
						$location_data['type'] = 'template';

						// Lets ensure we only list templates that belong to the current theme.

						// Get the theme from the wp_theme taxonomy.
						$terms = get_the_terms( $id, 'wp_theme' );
						if ( $terms && ! is_wp_error( $terms ) ) {
							$theme_slug = $terms[0]->name; // The term name is the theme slug.

							// Current theme slug (child theme if one is active).
							$current_theme_slug = get_stylesheet();

							if ( $theme_slug !== $current_theme_slug ) {
								continue; // Skip templates not belonging to the current theme.
							}
						}
					}
				} else {
					/* translators: %d: Post ID */
					$location_data['title']  = sprintf( __( 'Deleted Post #%d', 'search-filter' ), $id );
					$location_data['status'] = 'deleted';
				}
			} elseif ( $type === 'widget' && $id > 0 ) {
				/* translators: %d: Widget ID */
				$location_data['title']   = sprintf( __( 'Widget #%d', 'search-filter' ), $id );
				$location_data['editUrl'] = admin_url( 'widgets.php' );
			} elseif ( $type === 'widgets' ) {
				$location_data['title']   = __( 'Widgets Area', 'search-filter' );
				$location_data['editUrl'] = admin_url( 'widgets.php' );
			} else {
				continue; // Unknown location type, skip.
			}

			$enriched_locations[] = $location_data;
		}

		return rest_ensure_response( $enriched_locations );
	}
}
