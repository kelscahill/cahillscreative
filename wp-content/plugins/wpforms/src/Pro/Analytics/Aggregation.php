<?php

namespace WPForms\Pro\Analytics;

use DateTimeImmutable;
use WPForms\Analytics\Aggregation as BaseAggregation;
use WPForms\Db\Analytics\DB as BaseDB;
use WPForms\Pro\Db\Analytics\DB;

/**
 * Pro Analytics aggregation.
 *
 * Adds field-level rollup and abandonment detection via aggregate_extended();
 * overrides purge_old() for application-level cascade across snapshot_fields.
 *
 * @since 2.0.0
 */
class Aggregation extends BaseAggregation {

	/**
	 * Pro-only aggregation steps: field rollup + abandonment detection.
	 *
	 * @since 2.0.0
	 *
	 * @param string $today_start Today's start datetime in site timezone.
	 */
	public function aggregate_extended( string $today_start ): void {

		$this->aggregate_fields( $today_start );
		$this->detect_abandonments( $today_start );
	}

	/**
	 * Resolve the DB instance only when it supports field-level upserts.
	 *
	 * Returns null when either the container miss happened or the resolved DB
	 * is a Lite DB without the Pro-only `upsert_field_aggregate()` method.
	 * Callers should bail when this returns null.
	 *
	 * @since 2.0.0
	 *
	 * @return BaseDB|null
	 */
	private function field_aware_db(): ?BaseDB {

		$db = $this->db();

		if ( ! $db || ! method_exists( $db, 'upsert_field_aggregate' ) ) {
			return null;
		}

		return $db;
	}

	/**
	 * Roll up per-field interaction counters into analytics_fields.
	 *
	 * Public so unit tests can drive it in isolation from detect_abandonments().
	 * Matches the public visibility of Lite\Aggregation::aggregate_forms().
	 *
	 * @since 2.0.0
	 *
	 * @param string $today_start Today's start datetime in site timezone.
	 */
	public function aggregate_fields( string $today_start ): void {

		global $wpdb;

		$snaps = BaseDB::snapshots_table();
		$sf    = DB::snapshot_fields_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.form_id,
					DATE(s.occurred_at)   AS period_date,
					sf.field_id,
					sf.subfield_key,
					SUM(sf.was_displayed) AS views,
					SUM(sf.focus_count)   AS focus_count,
					SUM(sf.click_count)   AS click_count,
					SUM(sf.input_count)   AS input_count,
					SUM(sf.errors)        AS errors,
					SUM(sf.duration_ms)   AS total_duration_ms,
					SUM(CASE WHEN sf.duration_ms IS NOT NULL AND sf.duration_ms > 0 THEN 1 ELSE 0 END) AS duration_count
				FROM $sf sf
				INNER JOIN $snaps s ON s.id = sf.snapshot_id
				WHERE s.id IN (
						SELECT MAX(id) FROM {$snaps}
						WHERE processed    = 0
							AND form_visible = 1
							AND occurred_at  < %s
						GROUP BY session_id, form_id
					)
				GROUP BY s.form_id, DATE(s.occurred_at), sf.field_id, sf.subfield_key
				HAVING views > 0
					OR focus_count > 0
					OR click_count > 0
					OR input_count > 0
					OR errors > 0
					OR total_duration_ms > 0",
				$today_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $rows ) ) {
			return;
		}

		$db = $this->field_aware_db();

		if ( ! $db ) {
			return;
		}

		foreach ( $rows as $row ) {

			$deltas = [
				'views'             => (int) $row['views'],
				'focus_count'       => (int) $row['focus_count'],
				'click_count'       => (int) $row['click_count'],
				'input_count'       => (int) $row['input_count'],
				'errors'            => (int) $row['errors'],
				'total_duration_ms' => (int) $row['total_duration_ms'],
				'duration_count'    => (int) $row['duration_count'],
			];

			$db->upsert_field_aggregate( (int) $row['form_id'], (int) $row['field_id'], (string) $row['subfield_key'], (string) $row['period_date'], $deltas );
			$db->upsert_field_sentinel( (int) $row['form_id'], (int) $row['field_id'], (string) $row['subfield_key'], $deltas );
		}
	}

	/**
	 * Identify abandoned sessions and record field-level abandonment deltas.
	 *
	 * A session is abandoned for a given form when no trigger_type = 2 snapshot
	 * exists with the same (session_id, form_id) pair. The NOT EXISTS correlation
	 * spans both columns so that submitting Form A does not suppress Form B's
	 * abandonment when the two forms share a session_id.
	 *
	 * Public for the same reason aggregate_fields() is — direct test-driving.
	 *
	 * @since 2.0.0
	 *
	 * @param string $today_start Today's start datetime in site timezone.
	 */
	public function detect_abandonments( string $today_start ): void {

		global $wpdb;

		$snaps = BaseDB::snapshots_table();
		$sf    = DB::snapshot_fields_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.form_id,
						DATE(s.occurred_at)          AS period_date,
						sf.field_id,
						sf.subfield_key,
						COUNT(DISTINCT s.session_id) AS abandonments
					FROM $sf sf
					INNER JOIN $snaps s ON s.id = sf.snapshot_id
					WHERE s.id IN (
							SELECT MAX(id) FROM $snaps
							WHERE processed      = 0
								AND form_visible = 1
								AND occurred_at  < %s
							GROUP BY session_id, form_id
						)
						AND sf.was_displayed = 1
						AND ( sf.focus_count > 0 OR sf.click_count > 0 OR sf.input_count > 0 )
						AND NOT EXISTS (
							SELECT 1 FROM {$snaps} sub
							WHERE sub.session_id     = s.session_id
								AND sub.form_id      = s.form_id
								AND sub.trigger_type = 2
						)
					GROUP BY s.form_id, DATE(s.occurred_at), sf.field_id, sf.subfield_key",
				$today_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $rows ) ) {
			return;
		}

		$db = $this->field_aware_db();

		if ( ! $db ) {
			return;
		}

		foreach ( $rows as $row ) {

			$delta = [ 'abandonments' => (int) $row['abandonments'] ];

			$db->upsert_field_aggregate( (int) $row['form_id'], (int) $row['field_id'], (string) $row['subfield_key'], (string) $row['period_date'], $delta );
			$db->upsert_field_sentinel( (int) $row['form_id'], (int) $row['field_id'], (string) $row['subfield_key'], $delta );
		}
	}

	/**
	 * Cascade delete: snapshot_fields child rows, then parent snapshot rows.
	 *
	 * Two separate DELETE statements, both keyed on processed = 1 and occurred_at < cutoff.
	 * Child rows must be deleted first to maintain referential integrity. Race safety is
	 * supplied by the 30-day default retention gap.
	 *
	 * @since 2.0.0
	 */
	public function purge_old(): void {

		/** This filter is documented in wpforms/src/Analytics/Aggregation.php */
		$days = (int) apply_filters( 'wpforms_analytics_aggregation_purge_old_retention_days', self::DEFAULT_RETENTION_DAYS ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		if ( $days <= 0 ) {
			return;
		}

		$cutoff = ( new DateTimeImmutable( "-$days days", wp_timezone() ) )->format( 'Y-m-d H:i:s' );

		global $wpdb;

		$snaps = BaseDB::snapshots_table();
		$sf    = DB::snapshot_fields_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$child_result = $wpdb->query(
			$wpdb->prepare(
				"DELETE sf
				 FROM $sf sf
				 INNER JOIN $snaps s ON s.id = sf.snapshot_id
				 WHERE s.processed   = 1
				   AND s.occurred_at < %s",
				$cutoff
			)
		);

		$parent_result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $snaps
				 WHERE processed    = 1
				 	AND occurred_at < %s",
				$cutoff
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $child_result === false || $parent_result === false ) {
			wpforms_log(
				'Analytics aggregation: Pro purge_old failed',
				[
					'cutoff'        => $cutoff,
					'child_result'  => $child_result,
					'parent_result' => $parent_result,
					'last_error'    => $wpdb->last_error,
				],
				[
					'type'  => [ 'error' ],
					'force' => true,
				]
			);
		}
	}
}
