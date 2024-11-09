<?php

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;

class Upgrade_3_0_2 {

	public static function upgrade() {

		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );

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
			$old_sort_order = $query->get_attribute( 'sortOrder' );

			if ( ! is_string( $old_sort_order ) ) {
				continue;
			}
			if ( empty( $old_sort_order ) ) {
				continue;
			}

			$order   = explode( '-', $old_sort_order );
			$orderby = $order[0];

			if ( $orderby === 'inherit' ) {
				$query->set_attribute( 'sortOrder', array() );
				$query->save();
				continue;
			}

			$order = strtolower( $order[1] );
			if ( empty( $order ) ) {
				$order = 'asc';
			}

			$new_order = array(
				'orderBy'                 => $orderby,
				'order'                   => $order,
				'metaKey'                 => '',
				'metaType'                => '',
				'includePostsWithoutMeta' => false,
			);
			$query->set_attribute( 'sortOrder', array( $new_order ) );
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
			// This will regenrate the CSS for the style in the DB
			// to include the new sort order field.
			$style->save();
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Now build the CSS once.
		CSS_Loader::save_css();
	}

	public static function disable_css_save() {
		return false;
	}
}
