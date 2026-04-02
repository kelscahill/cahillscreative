<?php
/**
 * Upgrade routine for version 3.0.0 Beta 16.
 *
 * @package Search_Filter
 * @since 3.0.0
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Styles\Style;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.0.0 Beta 16.
 *
 * @since 3.0.0
 */
class Upgrade_3_0_0_Beta_16 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.0.0 Beta 16.
	 *
	 * @since 3.0.0
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field, query and style.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// First, update any fields that are using the `filter` type to `choice`,
		// do it directly using the DB because otherwise the fields will throw an error.
		$fields = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);
		foreach ( $fields as $field ) {

			if ( is_wp_error( $field ) ) {
				continue;
			}

			// Update filter type to choice.
			$attributes = $field->get_attributes();

			if ( ! isset( $attributes['inputType'] ) ) {
				continue;
			}

			// Upgrade any legacy input types which used to have a prefix.
			$prefixes = array(
				'search-',
				'filter-',
				'control-',
				'choice-',
				'advanced-',
				'range-',
			);

			// If input type starts with a legacy prefix, remove it.
			foreach ( $prefixes as $prefix ) {
				if ( strpos( $attributes['inputType'], $prefix ) === 0 ) {
					$attributes['inputType'] = substr( $attributes['inputType'], strlen( $prefix ) );
				}
			}

			$record = array(
				'attributes' => wp_json_encode( (object) $attributes ),
			);

			$query  = new \Search_Filter\Database\Queries\Fields();
			$result = $query->update_item( $field->get_id(), $record );
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
			$query->save();
		}

		// Resave styles.
		$styles = \Search_Filter\Styles::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}
			$style->save();
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );
		// Now build the CSS once.
		CSS_Loader::save_css();

		return Upgrade_Result::success();
	}

	/**
	 * Disables CSS save during upgrade to prevent rebuilding CSS for every change.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Always returns false to disable CSS save.
	 */
	public static function disable_css_save() {
		return false;
	}
}
