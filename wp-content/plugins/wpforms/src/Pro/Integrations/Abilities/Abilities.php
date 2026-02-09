<?php

namespace WPForms\Pro\Integrations\Abilities;

use WP_Error;
use WPForms\Integrations\Abilities\Abilities as AbilitiesBase;

/**
 * WordPress Abilities API Integration for WPForms Pro.
 *
 * @since 1.9.9
 */
class Abilities extends AbilitiesBase {

	/**
	 * Register WPForms abilities for Pro version.
	 *
	 * @since 1.9.9
	 */
	public function register_abilities(): void {

		// Register common abilities (list_forms, get_form).
		$this->register_common_abilities();

		// Pro-specific abilities.
		$this->register_get_entry_summaries_ability();
		$this->register_get_entry_ability();
		$this->register_form_stats_ability();
		$this->register_search_entries_ability();
	}

	/**
	 * Register the get_entry_summaries ability.
	 *
	 * @since 1.9.9
	 */
	protected function register_get_entry_summaries_ability(): void {

		wp_register_ability(
			self::ABILITY_NAMESPACE . '/get-entry-summaries',
			[
				'label'               => __( 'Get Entry Summaries', 'wpforms' ),
				'description'         => __( 'Get entry summaries for a specific WPForms form.', 'wpforms' ),
				'category'            => self::CATEGORY_SLUG,
				'execute_callback'    => [ $this, 'ability_get_entry_summaries' ],
				'permission_callback' => [ $this, 'check_view_entries_permission' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'form_id'        => [
							'description' => __( 'The ID of the form to get entries for.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
						'limit'          => [
							'description' => __( 'Maximum number of entries to return.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 20,
						],
						'offset'         => [
							'description' => __( 'Number of entries to skip.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 0,
							'default'     => 0,
						],
						'type'           => [
							'description' => __( 'Filter entries by type: read, unread, starred.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ '', 'read', 'unread', 'starred' ],
							'default'     => '',
						],
						'status'         => [
							'description' => __( 'Filter entries by status: partial, abandoned, spam, trash.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ '', 'partial', 'abandoned', 'spam', 'trash' ],
							'default'     => '',
						],
						'include_fields' => [
							'description' => __( 'Whether to include entry field values.', 'wpforms' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
					'required'   => [ 'form_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'entries' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'id'      => [ 'type' => 'integer' ],
									'form_id' => [ 'type' => 'integer' ],
									'date'    => [ 'type' => 'string' ],
									'viewed'  => [ 'type' => 'boolean' ],
									'starred' => [ 'type' => 'boolean' ],
								],
							],
						],
						'total'   => [ 'type' => 'integer' ],
						'form_id' => [ 'type' => 'integer' ],
					],
				],
				'meta'                => [
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
			]
		);
	}

	/**
	 * Register the get_entry ability.
	 *
	 * @since 1.9.9
	 */
	protected function register_get_entry_ability(): void {

		wp_register_ability(
			self::ABILITY_NAMESPACE . '/get-entry',
			[
				'label'               => __( 'Get Entry', 'wpforms' ),
				'description'         => __( 'Get detailed information about a specific form entry.', 'wpforms' ),
				'category'            => self::CATEGORY_SLUG,
				'execute_callback'    => [ $this, 'ability_get_entry' ],
				'permission_callback' => [ $this, 'check_view_entry_permission' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'entry_id'       => [
							'description' => __( 'The ID of the entry to retrieve.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
						'include_fields' => [
							'description' => __( 'Whether to include entry field values.', 'wpforms' ),
							'type'        => 'boolean',
							'default'     => true,
						],
					],
					'required'   => [ 'entry_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'id'         => [ 'type' => 'integer' ],
						'form_id'    => [ 'type' => 'integer' ],
						'date'       => [ 'type' => 'string' ],
						'modified'   => [ 'type' => 'string' ],
						'viewed'     => [ 'type' => 'boolean' ],
						'starred'    => [ 'type' => 'boolean' ],
						'ip_address' => [ 'type' => 'string' ],
						'fields'     => [ 'type' => 'array' ],
					],
				],
				'meta'                => [
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
			]
		);
	}

	/**
	 * Register the form_stats ability.
	 *
	 * @since 1.9.9
	 */
	protected function register_form_stats_ability(): void {

		wp_register_ability(
			self::ABILITY_NAMESPACE . '/get-form-stats',
			[
				'label'               => __( 'Get Form Stats', 'wpforms' ),
				'description'         => __( 'Get detailed statistics for a WPForms form including entry counts.', 'wpforms' ),
				'category'            => self::CATEGORY_SLUG,
				'execute_callback'    => [ $this, 'ability_get_form_stats' ],
				'permission_callback' => [ $this, 'check_view_entries_permission' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'form_id' => [
							'description' => __( 'The ID of the form to get stats for.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'form_id' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'form_id'           => [ 'type' => 'integer' ],
						'total_entries'     => [ 'type' => 'integer' ],
						'unread_entries'    => [ 'type' => 'integer' ],
						'starred_entries'   => [ 'type' => 'integer' ],
						'entries_available' => [ 'type' => 'boolean' ],
					],
				],
				'meta'                => [
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
			]
		);
	}

	/**
	 * Register the search_entries ability.
	 *
	 * @since 1.9.9
	 */
	protected function register_search_entries_ability(): void {

		wp_register_ability(
			self::ABILITY_NAMESPACE . '/search-entries',
			[
				'label'               => __( 'Search Entries', 'wpforms' ),
				'description'         => __( 'Search form entries by field values, date range, status, and other criteria.', 'wpforms' ),
				'category'            => self::CATEGORY_SLUG,
				'execute_callback'    => [ $this, 'ability_search_entries' ],
				'permission_callback' => [ $this, 'check_search_entries_permission' ],
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'form_id'        => [
							'description' => __( 'The ID of the form to search entries for. If not specified, searches across all forms.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
						'search'         => [
							'description' => __( 'Full-text search query across all entry fields.', 'wpforms' ),
							'type'        => 'string',
						],
						'field_id'       => [
							'description' => __( 'Specific field ID to search in.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
						'field_value'    => [
							'description' => __( 'Value to search for in the specified field.', 'wpforms' ),
							'type'        => 'string',
						],
						'date_from'      => [
							'description' => __( 'Start date for date range filter (Y-m-d format).', 'wpforms' ),
							'type'        => 'string',
							'format'      => 'date',
						],
						'date_to'        => [
							'description' => __( 'End date for date range filter (Y-m-d format).', 'wpforms' ),
							'type'        => 'string',
							'format'      => 'date',
						],
						'type'           => [
							'description' => __( 'Filter entries by type: read, unread, starred.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ '', 'read', 'unread', 'starred' ],
							'default'     => '',
						],
						'status'         => [
							'description' => __( 'Filter entries by status: partial, abandoned, spam, trash.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ '', 'partial', 'abandoned', 'spam', 'trash' ],
							'default'     => '',
						],
						'limit'          => [
							'description' => __( 'Maximum number of entries to return.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 20,
						],
						'page'           => [
							'description' => __( 'Page number for pagination.', 'wpforms' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'default'     => 1,
						],
						'orderby'        => [
							'description' => __( 'Sort by: entry_id, date, status.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ 'entry_id', 'date', 'status' ],
							'default'     => 'date',
						],
						'order'          => [
							'description' => __( 'Sort order: ASC, DESC.', 'wpforms' ),
							'type'        => 'string',
							'enum'        => [ 'ASC', 'DESC' ],
							'default'     => 'DESC',
						],
						'include_fields' => [
							'description' => __( 'Whether to include entry field values.', 'wpforms' ),
							'type'        => 'boolean',
							'default'     => true,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'entries'     => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'id'      => [ 'type' => 'integer' ],
									'form_id' => [ 'type' => 'integer' ],
									'date'    => [ 'type' => 'string' ],
									'viewed'  => [ 'type' => 'boolean' ],
									'starred' => [ 'type' => 'boolean' ],
									'fields'  => [ 'type' => 'array' ],
								],
							],
						],
						'total'       => [ 'type' => 'integer' ],
						'total_pages' => [ 'type' => 'integer' ],
						'page'        => [ 'type' => 'integer' ],
						'limit'       => [ 'type' => 'integer' ],
					],
				],
				'meta'                => [
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
					],
				],
			]
		);
	}

	/**
	 * Permission callback: Check if the user can view entries.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return bool|WP_Error
	 */
	public function check_view_entries_permission( $input = null ) {

		$args    = $this->normalize_input( $input );
		$form_id = absint( $args['form_id'] ?? 0 );

		if ( ! wpforms_current_user_can( 'view_entries_form_single', $form_id ) ) {
			return new WP_Error(
				'wpforms_forbidden',
				__( 'You do not have permission to view entries for this form.', 'wpforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Check if the user can view a specific entry.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return bool|WP_Error
	 */
	public function check_view_entry_permission( $input = null ) {

		$args     = $this->normalize_input( $input );
		$entry_id = absint( $args['entry_id'] ?? 0 );

		if ( ! wpforms_current_user_can( 'view_entry_single', $entry_id ) ) {
			return new WP_Error(
				'wpforms_forbidden',
				__( 'You do not have permission to view this entry.', 'wpforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Check if the user can search entries.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return bool|WP_Error
	 */
	public function check_search_entries_permission( $input = null ) {

		$args    = $this->normalize_input( $input );
		$form_id = absint( $args['form_id'] ?? 0 );

		// If form_id is specified, check form-specific permission.
		if ( $form_id > 0 ) {
			if ( ! wpforms_current_user_can( 'view_entries_form_single', $form_id ) ) {
				return new WP_Error(
					'wpforms_forbidden',
					__( 'You do not have permission to view entries for this form.', 'wpforms' ),
					[ 'status' => 403 ]
				);
			}

			return true;
		}

		// If the form_id is not specified, fall back to checking the general view entries permission.
		if ( ! wpforms_current_user_can( 'view_entries' ) ) {
			return new WP_Error(
				'wpforms_forbidden',
				__( 'You do not have permission to view entries.', 'wpforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Ability callback: Get entry summaries.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array|WP_Error
	 */
	public function ability_get_entry_summaries( $input = null ) {

		$args           = $this->normalize_input( $input );
		$form_id        = absint( $args['form_id'] ?? 0 );
		$include_fields = wp_validate_boolean( $args['include_fields'] ?? false );
		$entry_handler  = $this->get_entry_handler();

		if ( is_wp_error( $entry_handler ) ) {
			return $entry_handler;
		}

		// Build query args.
		$base_args = $this->build_entry_summaries_base_args( $args, $form_id );

		// Get total count efficiently (without fetching entries).
		$total = $entry_handler->get_entries( $base_args, true );

		// Get paginated entries.
		$query_args = array_merge(
			$base_args,
			[
				'number' => absint( $args['limit'] ?? 20 ),
				'offset' => absint( $args['offset'] ?? 0 ),
			]
		);

		$entries = $entry_handler->get_entries( $query_args );

		if ( empty( $entries ) ) {
			return [
				'entries' => [],
				'total'   => $total,
				'form_id' => $form_id,
			];
		}

		$formatted_entries = [];

		foreach ( $entries as $entry ) {
			$formatted_entries[] = $this->format_entry_summary( $entry, $include_fields );
		}

		return [
			'entries' => $formatted_entries,
			'total'   => $total,
			'form_id' => $form_id,
		];
	}

	/**
	 * Build base query arguments for entry summaries.
	 *
	 * @since 1.9.9
	 *
	 * @param array $args    Input arguments.
	 * @param int   $form_id Form ID.
	 *
	 * @return array Base query arguments.
	 */
	protected function build_entry_summaries_base_args( array $args, int $form_id ): array {

		$type_filters = $this->get_type_filters();
		$type         = sanitize_text_field( $args['type'] ?? '' );
		$status       = sanitize_text_field( $args['status'] ?? '' );

		$base_args = array_merge(
			[
				'form_id' => $form_id,
				'orderby' => 'entry_id',
				'order'   => 'DESC',
			],
			$type_filters[ $type ] ?? []
		);

		// Add the entry status filter (partial/abandoned/spam/trash).
		if ( ! empty( $status ) ) {
			$base_args['status'] = $status;
		}

		return $base_args;
	}

	/**
	 * Ability callback: Get the single entry.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array|WP_Error
	 */
	public function ability_get_entry( $input = null ) {

		$args           = $this->normalize_input( $input );
		$entry_id       = absint( $args['entry_id'] ?? 0 );
		$include_fields = wp_validate_boolean( $args['include_fields'] ?? true );

		$entry_handler = $this->get_entry_handler();

		if ( is_wp_error( $entry_handler ) ) {
			return $entry_handler;
		}

		$entry = $entry_handler->get( $entry_id );

		if ( empty( $entry ) ) {
			return new WP_Error(
				'wpforms_entry_not_found',
				__( 'Entry not found.', 'wpforms' ),
				[ 'status' => 404 ]
			);
		}

		return $this->format_entry_detail( $entry, $include_fields );
	}

	/**
	 * Ability callback: Get form stats.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array|WP_Error
	 */
	public function ability_get_form_stats( $input = null ) {

		$args    = $this->normalize_input( $input );
		$form_id = absint( $args['form_id'] ?? 0 );

		$form_handler = $this->get_form_handler();

		if ( is_wp_error( $form_handler ) ) {
			return $form_handler;
		}

		$form = $form_handler->get( $form_id );

		if ( empty( $form ) ) {
			return new WP_Error(
				'wpforms_form_not_found',
				__( 'Form not found.', 'wpforms' ),
				[ 'status' => 404 ]
			);
		}

		$entry_handler = $this->get_entry_handler();

		if ( is_wp_error( $entry_handler ) ) {
			return $entry_handler;
		}

		$total_entries = $entry_handler->get_entries(
			[
				'form_id' => $form_id,
				'number'  => 0,
			],
			true
		);

		$unread_entries = $entry_handler->get_entries(
			[
				'form_id' => $form_id,
				'viewed'  => 0,
				'number'  => 0,
			],
			true
		);

		$starred_entries = $entry_handler->get_entries(
			[
				'form_id' => $form_id,
				'starred' => 1,
				'number'  => 0,
			],
			true
		);

		return [
			'form_id'           => $form_id,
			'total_entries'     => absint( $total_entries ),
			'unread_entries'    => absint( $unread_entries ),
			'starred_entries'   => absint( $starred_entries ),
			'entries_available' => true,
		];
	}

	/**
	 * Ability callback: Search entries.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array|WP_Error
	 */
	public function ability_search_entries( $input = null ) {

		$params = $this->parse_search_params( $input );

		$entry_handler = $this->get_entry_handler();

		if ( is_wp_error( $entry_handler ) ) {
			return $entry_handler;
		}

		// Build query args with all filters and pagination.
		$query_args = $this->build_search_query_args( $params );

		// Get total count efficiently (without fetching entries).
		$count_args = $query_args;

		unset( $count_args['number'], $count_args['offset'] );
		$total = $entry_handler->get_entries( $count_args, true );

		// Calculate total pages.
		$total_pages = $params['limit'] > 0 ? ceil( $total / $params['limit'] ) : 1;

		// Get paginated entries from database.
		$entries = $entry_handler->get_entries( $query_args );

		if ( empty( $entries ) ) {
			return [
				'entries'     => [],
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => $params['page'],
				'limit'       => $params['limit'],
			];
		}

		// Format entries.
		$formatted_entries = [];

		foreach ( $entries as $entry ) {
			$formatted_entries[] = $this->format_entry_summary( $entry, $params['include_fields'] );
		}

		return [
			'entries'     => $formatted_entries,
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $params['page'],
			'limit'       => $params['limit'],
		];
	}

	/**
	 * Format entry data for summary listing.
	 *
	 * @since 1.9.9
	 *
	 * @param object $entry          Entry object.
	 * @param bool   $include_fields Whether to include entry field values.
	 *
	 * @return array
	 */
	protected function format_entry_summary( object $entry, bool $include_fields = false ): array {

		$result = [
			'id'      => absint( $entry->entry_id ),
			'form_id' => absint( $entry->form_id ),
			'date'    => $entry->date,
			'status'  => $entry->status,
			'viewed'  => (bool) $entry->viewed,
			'starred' => (bool) $entry->starred,
		];

		if ( $include_fields ) {
			$formatted_fields = $this->format_entry_fields( $entry );

			if ( ! empty( $formatted_fields ) ) {
				$result['fields'] = $formatted_fields;
			}
		}

		return $result;
	}

	/**
	 * Format entry data for detailed view.
	 *
	 * @since 1.9.9
	 *
	 * @param object $entry          Entry object.
	 * @param bool   $include_fields Whether to include entry field values.
	 *
	 * @return array
	 */
	protected function format_entry_detail( object $entry, bool $include_fields = true ): array {

		$result = [
			'id'         => absint( $entry->entry_id ),
			'form_id'    => absint( $entry->form_id ),
			'date'       => $entry->date,
			'modified'   => $entry->date_modified,
			'status'     => $entry->status,
			'viewed'     => (bool) $entry->viewed,
			'starred'    => (bool) $entry->starred,
			'ip_address' => $this->maybe_mask_ip( $entry->ip_address ),
		];

		if ( $include_fields ) {
			$formatted_fields = $this->format_entry_fields( $entry );

			if ( ! empty( $formatted_fields ) ) {
				$result['fields'] = $formatted_fields;
			}
		}

		return $result;
	}

	/**
	 * Format entry fields from JSON to array.
	 *
	 * @since 1.9.9
	 *
	 * @param object $entry Entry object.
	 *
	 * @return array Formatted fields array.
	 */
	protected function format_entry_fields( object $entry ): array {

		if ( empty( $entry->fields ) ) {
			return [];
		}

		$fields = json_decode( $entry->fields, true );

		if ( ! is_array( $fields ) ) {
			return [];
		}

		$formatted_fields = [];

		foreach ( $fields as $field_id => $field ) {
			$formatted_fields[] = [
				'id'    => absint( $field['id'] ?? $field_id ),
				'name'  => sanitize_text_field( $field['name'] ?? '' ),
				'value' => $this->sanitize_field_value( $field['value'] ?? '' ),
				'type'  => sanitize_text_field( $field['type'] ?? '' ),
			];
		}

		return $formatted_fields;
	}

	/**
	 * Sanitize field value for output.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $value Field value.
	 *
	 * @return array|string
	 */
	protected function sanitize_field_value( $value ) {

		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Maybe mask the IP address based on privacy settings.
	 *
	 * @since 1.9.9
	 *
	 * @param string $ip IP address.
	 *
	 * @return string
	 */
	protected function maybe_mask_ip( string $ip ): string {

		/**
		 * Filter whether to mask IP addresses in Abilities API responses.
		 *
		 * @since 1.9.9
		 *
		 * @param bool   $mask Whether to mask the IP.
		 * @param string $ip   The IP address.
		 */
		if ( (bool) apply_filters( 'wpforms_abilities_mask_ip_address', false, $ip ) ) {// phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			$last_dot = strrpos( $ip, '.' );

			if ( $last_dot !== false ) {
				return '***.***.***.' . substr( $ip, $last_dot + 1 );
			}

			return '***';
		}

		return $ip;
	}

	/**
	 * Get the entry handler and validate it.
	 *
	 * @since 1.9.9
	 *
	 * @return object|WP_Error Entry handler object or WP_Error on failure.
	 */
	protected function get_entry_handler() {

		$entry_handler = wpforms()->obj( 'entry' );

		if ( ! $entry_handler ) {
			return new WP_Error(
				'wpforms_entries_not_available',
				__( 'Entry handler not available.', 'wpforms' ),
				[ 'status' => 500 ]
			);
		}

		return $entry_handler;
	}

	/**
	 * Get type filters mapping (read, unread, starred).
	 *
	 * @since 1.9.9
	 *
	 * @return array Type filters mapping.
	 */
	protected function get_type_filters(): array {

		return [
			'starred' => [ 'starred' => 1 ],
			'read'    => [ 'viewed' => 1 ],
			'unread'  => [ 'viewed' => 0 ],
		];
	}

	/**
	 * Parse and sanitize search parameters.
	 *
	 * @since 1.9.9
	 *
	 * @param mixed $input Raw input data.
	 *
	 * @return array Parsed and sanitized parameters.
	 */
	protected function parse_search_params( $input ): array {

		$args = wp_parse_args(
			$this->normalize_input( $input ),
			[
				'form_id'        => 0,
				'search'         => '',
				'field_id'       => 0,
				'field_value'    => '',
				'date_from'      => '',
				'date_to'        => '',
				'type'           => '',
				'status'         => '',
				'limit'          => 20,
				'page'           => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'include_fields' => true,
			]
		);

		return [
			'form_id'        => absint( $args['form_id'] ),
			'search'         => sanitize_text_field( $args['search'] ),
			'field_id'       => absint( $args['field_id'] ),
			'field_value'    => sanitize_text_field( $args['field_value'] ),
			'date_from'      => sanitize_text_field( $args['date_from'] ),
			'date_to'        => sanitize_text_field( $args['date_to'] ),
			'type'           => sanitize_text_field( $args['type'] ),
			'status'         => sanitize_text_field( $args['status'] ),
			'limit'          => absint( $args['limit'] ),
			'page'           => absint( $args['page'] ),
			'orderby'        => sanitize_text_field( $args['orderby'] ),
			'order'          => strtoupper( sanitize_text_field( $args['order'] ) ),
			'include_fields' => wp_validate_boolean( $args['include_fields'] ),
		];
	}

	/**
	 * Build query args for search entries.
	 *
	 * @since 1.9.9
	 *
	 * @param array $params Search parameters from parse_search_params().
	 *
	 * @return array Query arguments for entry handler.
	 */
	protected function build_search_query_args( array $params ): array {

		// Calculate offset from page number.
		$offset = ( $params['page'] - 1 ) * $params['limit'];

		$query_args = [
			'number'  => $params['limit'],
			'offset'  => $offset,
			'orderby' => $params['orderby'],
			'order'   => $params['order'],
		];

		// Add the form_id filter if specified.
		if ( $params['form_id'] > 0 ) {
			$query_args['form_id'] = $params['form_id'];
		}

		// Add the type filter (read/unread/starred).
		if ( ! empty( $params['type'] ) ) {
			$type_filters = $this->get_type_filters();

			if ( isset( $type_filters[ $params['type'] ] ) ) {
				$query_args = array_merge( $query_args, $type_filters[ $params['type'] ] );
			}
		}

		// Add the entry status filter (partial/abandoned/spam/trash).
		if ( ! empty( $params['status'] ) ) {
			$query_args['status'] = $params['status'];
		}

		// Add the date range filter.
		if ( ! empty( $params['date_from'] ) || ! empty( $params['date_to'] ) ) {
			$date_from = $params['date_from'] ?? '1970-01-01';
			$date_to   = $params['date_to'] ?? gmdate( 'Y-m-d' );

			$query_args['date'] = [ $date_from, $date_to ];
		}

		// Add full-text search across all fields.
		if ( ! empty( $params['search'] ) ) {
			$query_args['field_id']      = 'any';
			$query_args['value']         = $params['search'];
			$query_args['value_compare'] = 'contains';
		}

		// Add field-specific search (overrides full-text search if both provided).
		if ( $params['field_id'] > 0 && ! empty( $params['field_value'] ) ) {
			$query_args['field_id']      = $params['field_id'];
			$query_args['value']         = $params['field_value'];
			$query_args['value_compare'] = 'contains';
		}

		return $query_args;
	}
}
