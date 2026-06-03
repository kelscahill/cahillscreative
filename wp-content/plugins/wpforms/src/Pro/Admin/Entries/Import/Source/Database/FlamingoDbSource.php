<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\Database;

use WPCF7_ContactForm;
use WPCF7_FormTag;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractDatabaseSource;

/**
 * Flamingo for Contact Form 7 database import source.
 *
 * Contact Form 7 stores submissions through the Flamingo plugin
 * using the `flamingo_inbound` custom post-type.
 *
 * @since 1.10.1
 */
class FlamingoDbSource extends AbstractDatabaseSource {

	/**
	 * Flamingo inbound message post type.
	 *
	 * @since 1.10.1
	 */
	private const POST_TYPE = 'flamingo_inbound';

	/**
	 * Flamingo inbound message post-status.
	 *
	 * @since 1.10.1
	 */
	private const POST_STATUS = 'publish';

	/**
	 * Flamingo inbound channel taxonomy.
	 *
	 * @since 1.10.1
	 */
	private const CHANNEL_TAXONOMY = 'flamingo_inbound_channel';

	/**
	 * Return the plugin slug for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {

		return 'flamingo';
	}

	/**
	 * Return the human-readable plugin name.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'Flamingo (Contact Form 7)';
	}

	/**
	 * Return the total entry count across all Flamingo forms.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total_entry_count(): int {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( ID )
				FROM ' . esc_sql( $wpdb->posts ) . '
				WHERE post_type = %s
				AND post_status = %s',
				self::POST_TYPE,
				self::POST_STATUS
			)
		);

		return (int) $count;
	}

	/**
	 * Return CF7 form fields by reading the _fields post-meta from a representative submission.
	 *
	 * Multi-value fields (checkbox and multi-select) are expanded into subentries keyed as
	 * `field.choice-slug`, with choices read from the CF7 form definition.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_fields(): array {

		if ( ! $this->source_form_id ) {
			return [];
		}

		global $wpdb;

		// Since the form can be changed after submission,
		// we should retrieve all unique submitted fields and later merge the list into one.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry_fields = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT pm.meta_value
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->term_relationships ) . ' AS tr ON tr.object_id = p.ID
				INNER JOIN ' . esc_sql( $wpdb->term_taxonomy ) . ' AS tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
					AND tt.taxonomy = %s
					AND tt.term_id = %d
				INNER JOIN ' . esc_sql( $wpdb->postmeta ) . ' AS pm
					ON pm.post_id = p.ID
					AND pm.meta_key = %s
				WHERE p.post_type = %s
				  AND p.post_status = %s
				GROUP BY pm.meta_value',
				self::CHANNEL_TAXONOMY,
				$this->source_form_id,
				'_fields',
				self::POST_TYPE,
				self::POST_STATUS
			)
		);

		if ( empty( $entry_fields ) ) {
			return [];
		}

		$entry_fields = array_map(
			static function ( $row ) {

				return array_keys( maybe_unserialize( $row ) );
			},
			$entry_fields
		);

		$entry_fields = array_unique( array_filter( array_merge( [], ...$entry_fields ) ) );

		if ( empty( $entry_fields ) ) {
			return [];
		}

		$form_tags = $this->get_cf7_form_tags();
		$fields    = [];

		foreach ( $entry_fields as $entry_field_slug ) {
			$label = ucwords( str_replace( [ '-', '_' ], ' ', $entry_field_slug ) );

			// Deleted form fields after a submission don't have a CF7 form tag.
			if ( empty( $form_tags[ $entry_field_slug ] ) ) {
				$fields[] = [
					'key'   => $entry_field_slug,
					'label' => $label,
				];

				continue;
			}

			$tag = $form_tags[ $entry_field_slug ];

			if ( ! $this->is_supported_type( $tag ) ) {
				continue;
			}

			$fields[] = [
				'key'   => $entry_field_slug,
				'label' => $label,
			];
		}

		return $fields;
	}

	/**
	 * Checks if the given CF7 form tag is a supported type for Flamingo database import source.
	 *
	 * @since 1.10.1
	 *
	 * @param WPCF7_FormTag $tag The CF7 form tag object.
	 *
	 * @return bool
	 */
	private function is_supported_type( WPCF7_FormTag $tag ): bool {

		$is_supported = $tag->type !== 'file';

		/**
		 * Filter whether a CF7 form tag is supported by the Flamingo database import source.
		 *
		 * @since 1.10.1
		 *
		 * @param bool          $is_supported Whether the form tag is supported.
		 * @param WPCF7_FormTag $tag          The CF7 form tag object.
		 */
		return apply_filters( 'wpforms_pro_admin_entries_import_source_database_flamingo_db_source_is_supported_type', $is_supported, $tag );
	}

	/**
	 * Return CF7 form tags for this channel's source form, keyed by field name.
	 *
	 * Returns an empty array when the CF7 form cannot be found or the CF7 plugin is inactive.
	 *
	 * @since 1.10.1
	 *
	 * @return array<string, WPCF7_FormTag>
	 */
	private function get_cf7_form_tags(): array {

		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return [];
		}

		$cf7_form_id = $this->find_cf7_form_id();

		if ( ! $cf7_form_id ) {
			return [];
		}

		$form = WPCF7_ContactForm::get_instance( $cf7_form_id );

		if ( ! $form || ! method_exists( $form, 'scan_form_tags' ) ) {
			return [];
		}

		$tags = [];

		foreach ( $form->scan_form_tags() as $tag ) {
			if ( ! empty( $tag->name ) ) {
				$tags[ $tag->name ] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Find the CF7 contact form post-ID linked to this Flamingo channel.
	 *
	 * CF7 stores the channel term ID in the `_flamingo` post meta on the form post.
	 *
	 * @since 1.10.1
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	private function find_cf7_form_id(): int {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$form_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT p.ID
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->postmeta ) . ' AS pm
					ON pm.post_id = p.ID
					AND pm.meta_key = %s
				WHERE p.post_type = %s
				  AND pm.meta_value LIKE %s
				LIMIT 1',
				'_flamingo',
				'wpcf7_contact_form',
				'%i:' . (int) $this->source_form_id . ';%'
			)
		);

		return (int) $form_id;
	}

	/**
	 * Return total Flamingo inbound message count for the source CF7 form.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total(): int {

		if ( ! $this->source_form_id ) {
			return 0;
		}

		global $wpdb;

		// The taxonomy `count` column counts all term relationships regardless of post-status,
		// while process_chunk() imports only `publish` posts. Mirror the chunk query here so
		// the total matches what will actually be imported and the session can complete.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( p.ID )
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->term_relationships ) . ' AS tr ON tr.object_id = p.ID
				INNER JOIN ' . esc_sql( $wpdb->term_taxonomy ) . ' AS tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
					AND tt.taxonomy = %s
					AND tt.term_id = %d
				WHERE p.post_type = %s
				  AND p.post_status = %s',
				self::CHANNEL_TAXONOMY,
				$this->source_form_id,
				self::POST_TYPE,
				self::POST_STATUS
			)
		);
	}

	/**
	 * Process a chunk of up to N entries starting at the given offset cursor.
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Number of already-processed posts (offset).
	 * @param int $number_entries Number of entries to process in this chunk.
	 *
	 * @return array
	 */
	public function process_chunk( int $cursor, int $number_entries ): array {

		if ( ! $this->source_form_id ) {
			return [
				'entries'     => [],
				'next_cursor' => $cursor,
				'errors'      => [],
			];
		}

		$entries = $this->fetch_entries( $cursor, $number_entries );

		if ( empty( $entries ) ) {
			return [
				'entries'     => [],
				'next_cursor' => $cursor,
				'errors'      => [],
			];
		}

		$post_ids     = array_column( $entries, 'ID' );
		$meta_by_post = $this->prefetch_chunk_fields_and_meta( $post_ids );

		foreach ( $entries as $entry ) {
			$date = $entry->post_date_gmt;

			if ( $date !== '' && $date !== '0000-00-00 00:00:00' ) {
				$meta_by_post[ (int) $entry->ID ]['meta']['date'] = $date;
			}
		}

		$next_cursor  = $cursor + count( $post_ids );
		$this->cursor = $next_cursor;

		return [
			'entries'     => $this->build_import_entries( $post_ids, $meta_by_post ),
			'next_cursor' => $next_cursor,
			'errors'      => [],
		];
	}

	/**
	 * Fetch chunk entries (ID + date) via taxonomy filter.
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Offset (number of already-processed posts).
	 * @param int $number_entries Number of posts to fetch.
	 *
	 * @return array
	 */
	private function fetch_entries( int $cursor, int $number_entries ): array {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.ID, p.post_date_gmt
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->term_relationships ) . ' AS tr ON tr.object_id = p.ID
				INNER JOIN ' . esc_sql( $wpdb->term_taxonomy ) . ' AS tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
					AND tt.taxonomy = %s
					AND tt.term_id = %d
				WHERE p.post_type = %s
				  AND p.post_status = %s
				ORDER BY p.ID
				LIMIT %d OFFSET %d',
				self::CHANNEL_TAXONOMY,
				$this->source_form_id,
				self::POST_TYPE,
				self::POST_STATUS,
				$number_entries,
				$cursor
			)
		);
	}

	/**
	 * Fetch and index postmeta for the given post IDs.
	 *
	 * Populates $this->entry_meta_by_post with ip_address and user_agent from the _meta JSON.
	 * Returns field meta (keyed by post ID) for field value mapping.
	 *
	 * @since 1.10.1
	 *
	 * @param array $post_ids Post IDs to fetch meta for.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function prefetch_chunk_fields_and_meta( array $post_ids ): array {

		global $wpdb;

		$meta_sql  = $wpdb->prepare(
			'SELECT post_id, meta_key, meta_value
			FROM ' . esc_sql( $wpdb->postmeta ) . '
			WHERE ( meta_key LIKE %s OR meta_key = %s )',
			$wpdb->esc_like( '_field_' ) . '%',
			'_meta'
		);
		$meta_sql .= ' AND post_id IN (' . wpforms_wpdb_prepare_in( $post_ids, '%d' ) . ')';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows = $wpdb->get_results( $meta_sql, ARRAY_A );
		$post_data = [];

		foreach ( $meta_rows as $row ) {
			$row_post_id = (int) $row['post_id'];

			if ( $row['meta_key'] === '_meta' ) {
				$post_data[ $row_post_id ]['meta'] = $this->get_entry_meta( $row['meta_value'] );

				continue;
			}

			$field_key = preg_replace( '/^_field_/', '', $row['meta_key'] );

			$post_data[ $row_post_id ]['fields'][ $field_key ] = $row['meta_value'];
		}

		return $post_data;
	}

	/**
	 * Decode a Flamingo _meta JSON value and store ip_address/user_agent into entry_meta_by_post.
	 *
	 * @since 1.10.1
	 *
	 * @param string $meta_value Raw JSON value from the _meta postmeta row.
	 *
	 * @return array
	 */
	private function get_entry_meta( string $meta_value ): array {

		$entry_meta = [];
		$meta       = maybe_unserialize( $meta_value );

		if ( ! is_array( $meta ) ) {
			return $entry_meta;
		}

		if ( ! empty( $meta['remote_ip'] ) ) {
			$entry_meta['ip_address'] = $meta['remote_ip'];
		}

		if ( ! empty( $meta['user_agent'] ) ) {
			$entry_meta['user_agent'] = $meta['user_agent'];
		}

		if ( ! empty( $meta['url'] ) ) {
			$entry_meta['page_url'] = $meta['url'];
		}

		if ( ! empty( $meta['post_id'] ) ) {
			$entry_meta['page_id'] = $meta['post_id'];
		}

		if ( ! empty( $meta['post_title'] ) ) {
			$entry_meta['page_title'] = $meta['post_title'];
		}

		if ( ! empty( $meta['user_email'] ) ) {
			$user = get_user_by( 'email', $meta['user_email'] );

			$entry_meta['user_id'] = $user && $user->ID ? $user->ID : 0;
		}

		return $entry_meta;
	}

	/**
	 * Build the import entries array from fetched post IDs and field meta.
	 *
	 * @since 1.10.1
	 *
	 * @param array $post_ids     Post IDs in chunk order.
	 * @param array $meta_by_post Field meta keyed by post ID.
	 *
	 * @return array
	 */
	private function build_import_entries( array $post_ids, array $meta_by_post ): array {

		$import_entries = [];

		foreach ( $post_ids as $post_id ) {
			$post_data     = $meta_by_post[ $post_id ] ?? [];
			$fields        = $post_data['fields'] ?? [];
			$import_fields = [];

			foreach ( $fields as $field_key => $meta_value ) {
				$meta_value                  = maybe_unserialize( $meta_value );
				$import_fields[ $field_key ] = is_array( $meta_value ) ? implode( "\n", $meta_value ) : (string) $meta_value;
			}

			// Pass posts with no `_field_*` data through with empty fields so the importer
			// surfaces them as a visible "Empty Entry" error rather than silently dropping them.
			$import_entries[] = [
				'id'     => (int) $post_id,
				'fields' => $import_fields,
				'meta'   => $this->build_entry_meta( $post_data ),
			];
		}

		return $import_entries;
	}

	/**
	 * Build entry meta for a Flamingo post from chunk-prefetched data.
	 *
	 * @since 1.10.1
	 *
	 * @param mixed $source_entry Flamingo inbound post ID.
	 *
	 * @return array
	 */
	protected function build_entry_meta( $source_entry ): array {

		return $source_entry['meta'] ?? [];
	}

	/**
	 * Get a list of CF7 forms (Flamingo channels) available for import.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_forms(): array {

		global $wpdb;

		// Only return channels that have at least one importable inbound post. Count only inbound posts
		// with the `publish` status, so the displayed entry count matches what will actually be imported
		// rather than the raw taxonomy count (which also includes spam/trash).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT t.term_id, t.name, t.slug, COUNT( p.ID ) AS entry_count
				FROM ' . esc_sql( $wpdb->term_taxonomy ) . ' AS tt
				INNER JOIN ' . esc_sql( $wpdb->terms ) . ' AS t ON t.term_id = tt.term_id
				INNER JOIN ' . esc_sql( $wpdb->term_relationships ) . ' AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN ' . esc_sql( $wpdb->posts ) . ' AS p
					ON p.ID = tr.object_id
					AND p.post_type = %s
					AND p.post_status = %s
				WHERE tt.taxonomy = %s
				AND tt.parent > 0
				GROUP BY t.term_id, t.name, t.slug
				HAVING entry_count > 0
				ORDER BY entry_count DESC, t.name',
				self::POST_TYPE,
				self::POST_STATUS,
				self::CHANNEL_TAXONOMY
			)
		);

		if ( empty( $results ) ) {
			return [];
		}

		$forms = [];

		foreach ( $results as $row ) {
			$entry_count = (int) $row->entry_count;

			$forms[] = [
				'id'               => (int) $row->term_id,
				'title'            => $row->name,
				'entry_count'      => $entry_count,
				'disabled'         => $entry_count === 0,
				'form_entries_url' => add_query_arg(
					[
						'page'    => 'flamingo_inbound',
						'channel' => $row->slug,
					],
					admin_url( 'admin.php' )
				),
			];
		}

		return $forms;
	}

	/**
	 * Determine whether the Flamingo import source is available on this site.
	 *
	 * Requires the Flamingo plugin to be active and at least one CF7 form
	 * with stored inbound messages.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		return class_exists( 'WPCF7_ContactForm' ) && ! empty( $this->get_forms() );
	}
}
