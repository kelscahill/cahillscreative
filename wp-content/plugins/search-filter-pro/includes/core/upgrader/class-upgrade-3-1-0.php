<?php
/**
 * Upgrade routines for version 3.1.0
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.1.0
 */
class Upgrade_3_1_0 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.1.0
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		$fields = Fields::find(
			array(
				'number' => 0,
			)
		);

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			// Check for range fields.
			if ( $field->get_attribute( 'type' ) !== 'range' ) {
				continue;
			}

			// Now check to see if autodetect min/max is set. If so, delete
			// any min/max that might be present.
			if ( $field->get_attribute( 'rangeAutodetectMin' ) === 'yes' ) {
				$field->delete_attribute( 'rangeMin' );
			}

			if ( $field->get_attribute( 'rangeAutodetectMax' ) === 'yes' ) {
				$field->delete_attribute( 'rangeMax' );
			}
		}

		// Resave queries.
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			// Clear query caches.
			Tiered_Cache::invalidate_query_cache( $query->get_id() );
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		return Upgrade_Result::success();
	}

	/**
	 * Disable CSS save during upgrade.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	public static function disable_css_save() {
		return false;
	}
}
