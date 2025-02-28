<?php
/**
 * Submit Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Control
 */

namespace Search_Filter\Fields\Control;

use Search_Filter\Fields\Control;
use Search_Filter\Queries\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Sort extends Control {

	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputSelectedColor',
		'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
		'inputInteractiveColor',
		'inputInteractiveHoverColor',
		'inputClearColor',
		'inputClearHoverColor',

		'labelColor',
		'labelBackgroundColor',
		'labelPadding',
		'labelMargin',
		'labelScale',

		'descriptionColor',
		'descriptionBackgroundColor',
		'descriptionPadding',
		'descriptionMargin',
		'descriptionScale',
	);

	public static $setting_support = array(
		'showLabel'     => true,
		'placeholder'   => true,
		'inputShowIcon' => true,
	);

	// TODO - we don't want to use "input type" to define "control type".
	public static $input_type = 'sort';

	public static $type = 'control';

	public $icons = array(
		'arrow-down',
		'clear',
	);

	public static function get_label() {
		return __( 'Sort', 'search-filter' );
	}

	/**
	 * The main function that constructs the main part of the filter,
	 * this could contain a single input or multiple inputs
	 *
	 * @since    3.0.0
	 */
	public function build() {
		return '';
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * Match the value to the sort options, then use those settings
	 * to apply the sort.
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		$value = $this->get_value();
		// TODO - maybe str_replace ' ' with '+' first.
		$sort_parts = explode( '+', $value );

		if ( count( $sort_parts ) !== 2 ) {
			return parent::apply_wp_query_args( $query_args );
		}

		// If its not initialized, bail early.
		$sort_options = $this->get_attribute( 'sortOptions' );
		if ( ! is_array( $sort_options ) ) {
			return parent::apply_wp_query_args( $query_args );
		}

		// Bail early if there are no sort options.
		if ( count( $sort_options ) === 0 ) {
			return parent::apply_wp_query_args( $query_args );
		}

		$order_by = $sort_parts[0];
		$order    = $sort_parts[1];

		$order_params = array();

		foreach ( $sort_options as $sort_option ) {

			if ( $sort_option['orderBy'] !== $order_by && $sort_option['metaKey'] !== $order_by ) {
				continue;
			}
			if ( $sort_option['order'] !== $order ) {
				continue;
			}
			// Then we found the option.
			$order_params = $sort_option;
			break;
		}

		if ( empty( $order_params ) ) {
			return parent::apply_wp_query_args( $query_args );
		}

		// Now we have the order params.
		$sort_args = Query::get_sort_params_from_setting( $order_params );

		if ( ! empty( $sort_args['order_option'] ) ) {

			// If order is not set on the query args, or its not array, make it one.
			if ( ! isset( $query_args['orderby'] ) || ! is_array( $query_args['orderby'] ) ) {
				$query_args['orderby'] = array();
			}

			// We need to remove any keys that match the orderby key set because the merge later
			// will replace the value instead of appending it.
			$cleaned_wp_query_orderby = $query_args['orderby'];
			// Although we know there will only be one orderby, lets loop it for future proofing.
			$order_keys = array_keys( $sort_args['order_option'] );
			foreach ( $order_keys as $order_key ) {
				if ( isset( $cleaned_wp_query_orderby[ $order_key ] ) ) {
					unset( $cleaned_wp_query_orderby[ $order_key ] );

					// If this is a custom field order, we need to remove the associated meta query
					// if it exists.
					if ( $order_params['orderBy'] === 'custom_field' && ! empty( $query_args['meta_query'] ) ) {
						$meta_key                 = $order_params['metaKey'];
						$meta_query_names         = array(
							$meta_key . '_base',
							$meta_key . '_clause',
						);
						$query_args['meta_query'] = $this->remove_meta_query_by_names( $query_args['meta_query'], $meta_query_names );
					}
				}
			}
			// Put the sort args at the start of the orderby array.
			$query_args['orderby'] = array_merge( $sort_args['order_option'], $cleaned_wp_query_orderby );
		}

		if ( ! empty( $sort_args['meta_query'] ) ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(
					'relation' => 'AND',
				);
			}
			$query_args['meta_query'][] = $sort_args['meta_query'];
		}

		return $query_args;
	}

	/**
	 * Removes a meta query or group by name.
	 *
	 * TODO: When we clear a group, we don't remove the containing array
	 * with the relation.  This is probably ok for now.
	 *
	 * @param array $meta_query The meta query.
	 * @param array $meta_query_names The names of the meta query to remove.
	 *
	 * @return array The updated meta query.
	 */
	private function remove_meta_query_by_names( $meta_query, $meta_query_names ) {
		// We know the query will only be 3 levels deep, so don't bother with recursion.
		foreach ( $meta_query as $key => $meta_query_item ) {
			if ( in_array( $key, $meta_query_names, true ) ) {
				unset( $meta_query[ $key ] );
				return $meta_query;
			}
			if ( ! is_array( $meta_query_item ) ) {
				continue;
			}
			// Loop through the second level.
			foreach ( $meta_query_item as $sub_key => $sub_item ) {
				if ( in_array( $sub_key, $meta_query_names, true ) ) {
					unset( $meta_query[ $key ][ $sub_key ] );
					return $meta_query;
				}
				if ( ! is_array( $sub_item ) ) {
					continue;
				}
				// And finally the third level.
				$item_keys = array_keys( $sub_item );
				foreach ( $item_keys as $item_key ) {
					if ( in_array( $item_key, $meta_query_names, true ) ) {
						unset( $meta_query[ $key ][ $sub_key ][ $item_key ] );
						return $meta_query;
					}
				}
			}
		}
		return $meta_query;
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}
		$url_name = 'sort';
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}
}
