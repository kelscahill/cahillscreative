<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\FormsInventory;

use WPForms\Analytics\Analytics as AnalyticsFeature;

/**
 * Row enrichment for the Pro forms-inventory scope.
 *
 * @since 2.0.0
 */
class RowEnricher {

	/**
	 * Enrich form rows with Pro-only fields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows Form rows produced by Lite `build_rows()`.
	 *
	 * @return array
	 */
	public function enrich( array $rows ): array {

		if ( ! $rows ) {
			return $rows;
		}

		$form_ids = $this->extract_row_form_ids( $rows );

		$entries_counts  = $this->fetch_entries_counts( $form_ids );
		$analytics       = AnalyticsFeature::is_enabled() ? $this->fetch_analytics_overview( $form_ids ) : [];
		$locations_count = $this->fetch_locations_counts( $form_ids );

		return $this->apply_row_enrichment( $rows, $entries_counts, $analytics, $locations_count );
	}

	/**
	 * Extract the de-duplicated positive form IDs from a row set.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows Form rows produced by Lite `build_rows()`.
	 *
	 * @return array Form IDs (zero/missing IDs filtered out).
	 */
	private function extract_row_form_ids( array $rows ): array {

		return array_values(
			array_filter(
				array_map(
					static function ( $row ): int {

						return (int) ( $row['id'] ?? 0 );
					},
					$rows
				)
			)
		);
	}

	/**
	 * Merge entries counts, analytics overviews, and locations counts into the row set.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows             Form rows produced by Lite `build_rows()`.
	 * @param array $entries_counts   Map of form_id => entries count.
	 * @param array $analytics        Map of form_id => analytics overview.
	 * @param array $locations_counts Map of form_id => locations count.
	 *
	 * @return array
	 */
	private function apply_row_enrichment( array $rows, array $entries_counts, array $analytics, array $locations_counts ): array {

		foreach ( $rows as $i => $row ) {
			$id                            = (int) ( $row['id'] ?? 0 );
			$rows[ $i ]['entries_count']   = (int) ( $entries_counts[ $id ] ?? 0 );
			$rows[ $i ]['locations_count'] = (int) ( $locations_counts[ $id ] ?? 0 );

			if ( isset( $analytics[ $id ] ) ) {
				$rows[ $i ]['analytics'] = $analytics[ $id ];
			}
		}

		return $rows;
	}

	/**
	 * Bulk-fetch entries counts for the given form IDs.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids Form IDs.
	 *
	 * @return array Map of form_id => count.
	 */
	private function fetch_entries_counts( array $form_ids ): array {

		global $wpdb;

		$form_ids = array_values( array_filter( array_map( 'absint', $form_ids ) ) );

		if ( empty( $form_ids ) ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
		$table        = $wpdb->prefix . 'wpforms_entries';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id, COUNT(*) AS count
				 FROM $table
				 WHERE form_id IN ( $placeholders )
				 GROUP BY form_id",
				$form_ids
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$counts = [];

		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row['form_id'] ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Bulk-fetch analytics overview rows for the given form IDs.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids Form IDs.
	 *
	 * @return array Map of form_id => [ views, submissions, interactions, conversion ].
	 */
	private function fetch_analytics_overview( array $form_ids ): array {

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db ) {
			return [];
		}

		$rows = (array) $db->get_overview_stats( $form_ids );

		$out = [];

		foreach ( $rows as $form_id => $row ) {
			$out[ (int) $form_id ] = $this->shape_analytics_overview_row( (array) $row );
		}

		return $out;
	}

	/**
	 * Bulk-fetch locations counts for the given form IDs.
	 *
	 * Reads the `wpforms_form_locations` post meta managed by `Forms\Locator`.
	 * Each form's meta value is an array of location entries; the count of that
	 * array is the number of pages/posts where the form is embedded.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids Form IDs.
	 *
	 * @return array Map of form_id => count.
	 */
	private function fetch_locations_counts( array $form_ids ): array {

		if ( ! $form_ids ) {
			return [];
		}

		// Prime the post meta cache for all form IDs in a single query.
		update_meta_cache( 'post', $form_ids );

		$counts = [];

		foreach ( $form_ids as $form_id ) {
			$locations = get_post_meta( $form_id, 'wpforms_form_locations', true );

			$counts[ $form_id ] = is_array( $locations ) ? count( $locations ) : 0;
		}

		return $counts;
	}

	/**
	 * Shape a single analytics overview row, deriving the conversion percentage.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row Raw overview row with optional `views`/`submissions`/`interactions`.
	 *
	 * @return array Map with `views`, `submissions`, `interactions`, `conversion`.
	 */
	private function shape_analytics_overview_row( array $row ): array {

		$views        = (int) ( $row['views'] ?? 0 );
		$submissions  = (int) ( $row['submissions'] ?? 0 );
		$interactions = (int) ( $row['interactions'] ?? 0 );
		$conversion   = $views > 0 ? round( ( $submissions / $views ) * 100, 2 ) : 0.0;

		return [
			'views'        => $views,
			'submissions'  => $submissions,
			'interactions' => $interactions,
			'conversion'   => $conversion,
		];
	}
}
