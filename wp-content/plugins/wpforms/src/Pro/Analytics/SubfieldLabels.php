<?php

namespace WPForms\Pro\Analytics;

/**
 * Resolver for composite-field subfield labels.
 *
 * A composite field (Name, Address, Date/Time) is tracked as one analytics row
 * per subfield. Email and Password are *optional* composites — they split into
 * `primary`/`secondary` subfields only when their confirmation setting is on,
 * and are tracked as a single field-level row otherwise. The sublabels are not
 * stored in `form_data` — the field classes hardcode them or derive them from
 * the chosen scheme/setting — so the defaults are reproduced here, in a single
 * place shared by every analytics_fields reader that needs to label a subfield row.
 *
 * Shared by `Stats` (Analytics admin page + AI `field_stats`) and the AI
 * Analytics scope `StateBuilder` (`fields_ranking` enrichment) so the two
 * surfaces always compose identical labels.
 *
 * Labels are returned as plain (un-escaped) text — each consumer escapes at its
 * own output boundary, or sends the label to a non-HTML sink (the AI scope's
 * JSON payload), where HTML entities would be wrong.
 *
 * @since 2.0.0
 */
class SubfieldLabels {

	/**
	 * Resolve human labels for a composite field's subfields.
	 *
	 * Returns `[ subfield_key => label ]` for core composite types and `[]` for
	 * simple fields. Addons add their own types via the filter.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from `form_data['fields']`.
	 *
	 * @return array
	 */
	public static function resolve( array $field ): array {

		$type   = (string) ( $field['type'] ?? '' );
		$labels = [];

		if ( $type === 'name' ) {
			$labels = self::resolve_name( $field );
		} elseif ( $type === 'address' ) {
			$labels = self::resolve_address( $field );
		} elseif ( $type === 'date-time' ) {
			$labels = self::resolve_datetime( $field );
		} elseif ( $type === 'email' ) {
			$labels = self::resolve_email( $field );
		} elseif ( $type === 'password' ) {
			$labels = self::resolve_password( $field );
		}

		/**
		 * Filters the resolved subfield labels for a composite field.
		 *
		 * @since 2.0.0
		 *
		 * @param array $labels Map of subfield_key => label.
		 * @param array $field  Field definition from form_data.
		 */
		return (array) apply_filters( 'wpforms_pro_analytics_subfield_labels_resolve', $labels, $field );
	}

	/**
	 * Compose the display label for a field row.
	 *
	 * Simple fields (empty subfield key) return the plain field label. Composite
	 * subfield rows return `"<field label>: <subfield label>"`, falling back to
	 * the raw key when the field defines no label for it (e.g. an addon composite
	 * type this resolver does not cover).
	 *
	 * @since 2.0.0
	 *
	 * @param array  $field        Field definition from `form_data['fields']`.
	 * @param string $subfield_key Subfield key, or '' for a simple field.
	 *
	 * @return string
	 */
	public static function compose( array $field, string $subfield_key ): string {

		$label = self::resolve_field_label( $field );

		if ( $subfield_key === '' ) {
			return $label;
		}

		return self::compose_from_map( $label, self::resolve( $field ), $subfield_key );
	}

	/**
	 * Resolve the field-level label used for a field's analytics rows.
	 *
	 * Defaults to the form label, but lets a field type present a different
	 * field-level name in the breakdown ( e.g. PayPal Commerce shows the
	 * payment-type name rather than the user's "Payment Method" form label ).
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from `form_data['fields']`.
	 *
	 * @return string
	 */
	public static function resolve_field_label( array $field ): string {

		$label = (string) ( $field['label'] ?? '' );

		/**
		 * Filters the field-level label used for a field's analytics rows.
		 *
		 * @since 2.0.0
		 *
		 * @param string $label Field label from form_data.
		 * @param array  $field Field definition from form_data.
		 */
		return (string) apply_filters( 'wpforms_pro_analytics_subfield_labels_field_label', $label, $field );
	}

	/**
	 * Compose a display label from an already-resolved field label and subfield map.
	 *
	 * Shared join used by `compose()` (which resolves the map from a raw field) and
	 * by callers that already hold the `[ subfield_key => label ]` map (e.g. the AI
	 * ranking enrichment). Returns the plain field label for an empty subfield key;
	 * otherwise `"<field label>: <subfield label>"`, falling back to the raw key when
	 * the map has no label for it.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field_label  Field label (already resolved).
	 * @param array  $subfields    Map of subfield_key => label.
	 * @param string $subfield_key Subfield key, or '' for a simple field.
	 *
	 * @return string
	 */
	public static function compose_from_map( string $field_label, array $subfields, string $subfield_key ): string {

		if ( $subfield_key === '' ) {
			return $field_label;
		}

		$sub_label = $subfields[ $subfield_key ] ?? $subfield_key;

		// An empty resolved sublabel means the subfield stands in for the field
		// itself ( e.g. the primary input of a confirmation pair ), so show only
		// the field label without a subfield suffix.
		if ( $sub_label === '' ) {
			return $field_label;
		}

		return $field_label === '' ? $sub_label : $field_label . ': ' . $sub_label;
	}

	/**
	 * Resolve the subfield labels for a Name field.
	 *
	 * Keys match the submitted `name` bracket segments (`first`/`middle`/`last`).
	 * `middle` only exists for the `first-middle-last` format; the `simple`
	 * format renders a single input and therefore has no subfields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private static function resolve_name( array $field ): array {

		$format = (string) ( $field['format'] ?? 'first-last' );

		if ( $format === 'simple' ) {
			return [];
		}

		$labels = [
			'first' => __( 'First', 'wpforms' ),
			'last'  => __( 'Last', 'wpforms' ),
		];

		if ( $format === 'first-middle-last' ) {
			$labels['middle'] = __( 'Middle', 'wpforms' );
		}

		return $labels;
	}

	/**
	 * Resolve the subfield labels for an Address field.
	 *
	 * The scheme is stored as `scheme` (with a `format` fallback for pre-1.2.7
	 * data); the labels themselves are not stored in `form_data`. The keys match
	 * the submitted `address` bracket segments.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private static function resolve_address( array $field ): array {

		$scheme = (string) ( $field['scheme'] ?? $field['format'] ?? 'us' );

		$labels = [
			'address1' => __( 'Address Line 1', 'wpforms' ),
			'address2' => __( 'Address Line 2', 'wpforms' ),
			'city'     => __( 'City', 'wpforms' ),
			'state'    => __( 'State', 'wpforms' ),
			'postal'   => __( 'Zip Code', 'wpforms' ),
		];

		// The US scheme renders no country subfield; the international scheme adds
		// a country field and widens the state/postal wording.
		if ( $scheme !== 'us' ) {
			$labels['state']   = __( 'State / Province / Region', 'wpforms' );
			$labels['postal']  = __( 'Postal Code', 'wpforms' );
			$labels['country'] = __( 'Country', 'wpforms' );
		}

		// Drop subfields hidden by their per-subfield toggle ( mirrors the Pro
		// Address field_properties 'hidden' flags: address2_hide, postal_hide,
		// country_hide ).
		foreach ( [ 'address2', 'postal', 'country' ] as $sub_key ) {
			if ( ! empty( $field[ "{$sub_key}_hide" ] ) ) {
				unset( $labels[ $sub_key ] );
			}
		}

		return $labels;
	}

	/**
	 * Resolve the subfield labels for a Date/Time field.
	 *
	 * Keys match the submitted `date`/`time` bracket segments. The `format`
	 * setting controls which subfields render: `date` shows only Date, `time`
	 * only Time, and `date-time` ( the default ) shows both. The dropdown date
	 * type renders three inputs but they all submit under the `date` key, so the
	 * single `date` label covers it.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private static function resolve_datetime( array $field ): array {

		$format = (string) ( $field['format'] ?? 'date-time' );
		$labels = [
			'date' => __( 'Date', 'wpforms' ),
			'time' => __( 'Time', 'wpforms' ),
		];

		if ( $format === 'date' ) {
			unset( $labels['time'] );
		} elseif ( $format === 'time' ) {
			unset( $labels['date'] );
		}

		return $labels;
	}

	/**
	 * Resolve the subfield labels for an Email field.
	 *
	 * Email is an optional composite: it renders a single input until the
	 * confirmation setting is enabled, at which point it splits into `primary`
	 * and `secondary` inputs. Without confirmation there are no subfields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private static function resolve_email( array $field ): array {

		return self::resolve_confirmation_pair( $field, __( 'Confirm Email', 'wpforms' ) );
	}

	/**
	 * Resolve the subfield labels for a Password field.
	 *
	 * Like Email, Password is an optional composite — it splits into `primary`
	 * and `secondary` inputs only when the confirmation setting is enabled.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field Field definition from form_data.
	 *
	 * @return array
	 */
	private static function resolve_password( array $field ): array {

		return self::resolve_confirmation_pair( $field, __( 'Confirm Password', 'wpforms' ) );
	}

	/**
	 * Resolve the subfield labels for a confirmation-pair field ( Email, Password ).
	 *
	 * Returns `[]` when confirmation is disabled ( the field renders a single
	 * input and is tracked as one field-level row ). When enabled, the keys match
	 * the submitted `primary`/`secondary` bracket segments. The `primary` label is
	 * intentionally empty so it composes to the bare field label — the primary
	 * input represents the field itself; only the confirmation row is suffixed.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $field         Field definition from form_data.
	 * @param string $confirm_label Label for the confirmation ( secondary ) input.
	 *
	 * @return array
	 */
	private static function resolve_confirmation_pair( array $field, string $confirm_label ): array {

		if ( empty( $field['confirmation'] ) ) {
			return [];
		}

		return [
			'primary'   => '',
			'secondary' => $confirm_label,
		];
	}
}
