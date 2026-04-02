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

use Search_Filter\Core\Deprecations;
use Search_Filter\Fields\Control;
use Search_Filter\Queries\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Sort extends Control {

	/**
	 * Array of styles settings the field supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'                   => true,
		'inputMargin'                   => true,
		'labelBorderStyle'              => true,
		'labelBorderRadius'             => true,
		'descriptionBorderStyle'        => true,
		'descriptionBorderRadius'       => true,
		'inputClearPadding'             => true,
		'inputBorderRadius'             => true,

		'inputScale'                    => true,
		'inputColor'                    => true,
		'inputBackgroundColor'          => true,
		'inputPlaceholderColor'         => true,
		'inputSelectedColor'            => true,
		'inputSelectedBackgroundColor'  => true,
		'inputBorder'                   => true,
		'inputBorderHoverColor'         => true,
		'inputBorderFocusColor'         => true,
		'inputIconColor'                => true,
		'inputInteractiveColor'         => true,
		'inputInteractiveHoverColor'    => true,
		'inputClearColor'               => true,
		'inputClearHoverColor'          => true,
		'inputShadow'                   => true,
		'inputPadding'                  => true,
		'inputGap'                      => true,
		'inputTogglePadding'            => true,
		'inputToggleSize'               => true,
		'inputClearSize'                => array(
			'conditions' => array(),
			'variation'  => array(
				'style' => array(
					'variables' => array(
						'input-clear-size' => array(
							'value' => 'var(--search-filter-scale-base-size)',
							'type'  => 'unit',
						),
					),
				),
			),
		),
		'inputNoResultsText'            => true,
		'inputEnableSearch'             => true,
		'inputSingularResultsCountText' => true,
		'inputPluralResultsCountText'   => true,

		'labelColor'                    => true,
		'labelBackgroundColor'          => true,
		'labelPadding'                  => true,
		'labelMargin'                   => true,
		'labelScale'                    => true,

		'descriptionColor'              => true,
		'descriptionBackgroundColor'    => true,
		'descriptionPadding'            => true,
		'descriptionMargin'             => true,
		'descriptionScale'              => true,
	);

	/**
	 * The processed (cached) styles.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_styles    The processed styles, null if not processed yet.
	 */
	protected static $processed_styles = null;

	/**
	 * Assoc array of settings the field supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'        => true,
		'width'           => true,
		'queryId'         => true,
		'stylesId'        => true,
		'type'            => true,
		'label'           => true,
		'showLabel'       => true,
		'showDescription' => true,
		'description'     => true,
		'controlType'     => true,
		'placeholder'     => true,
		'sortOptions'     => true,
	);

	/**
	 * The processed (cached) setting support.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_setting_support    The processed settings, null if not processed yet.
	 */
	protected static $processed_setting_support = null;

	/**
	 * The input type name.
	 *
	 * TODO - we don't want to use "input type" to define "control type".
	 *
	 * @var string
	 */
	public static $input_type = 'sort';

	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';
	/**
	 * List of components this field relies on.
	 *
	 * @var array
	 */
	public $components = array(
		'combobox',
	);

	/**
	 * Array of icon names to load.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $icons = array(
		'arrow-down',
		'clear',
	);

	/**
	 * Get the label for the input type.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Sort', 'search-filter' );
	}
	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to sort results.', 'search-filter' );
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

		// Only apply if a value is selected.
		if ( ! $this->has_values() ) {
			return parent::apply_wp_query_args( $query_args );
		}
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
						$meta_key         = $order_params['metaKey'];
						$meta_query_names = array(
							$meta_key . '_base',
							$meta_key . '_clause',
						);
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
						$query_args['meta_query'] = $this->remove_meta_query_by_names( $query_args['meta_query'], $meta_query_names );
					}
				}
			}
			// Put the sort args at the start of the orderby array.
			$query_args['orderby'] = array_merge( $sort_args['order_option'], $cleaned_wp_query_orderby );
		}

		if ( ! empty( $sort_args['meta_query'] ) ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
				$query_args['meta_query'] = array(
					'relation' => 'AND',
				);
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
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
		if ( ! $this->get_attribute( 'dataType' ) ) {
			return parent::get_url_name();
		}
		$url_name = 'sort';
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );

		return $url_name;
	}

	/**
	 * Create the local options array.
	 *
	 * @since 3.0.0
	 */
	public function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		do_action( 'search-filter/fields/control/create_options/start', $this );

		$sort_options = $this->get_attribute( 'sortOptions' );

		if ( ! is_array( $sort_options ) ) {
			return;
		}

		$values                 = $this->get_values();
		$options                = array();
		$existing_option_values = array();

		foreach ( $sort_options as $sort_option ) {

			// Skip options without labels.
			if ( empty( $sort_option['label'] ) ) {
				continue;
			}

			$option_value = $sort_option['orderBy'] . '+' . $sort_option['order'];
			if ( $sort_option['orderBy'] === 'custom_field' ) {
				$option_value = $sort_option['metaKey'] . '+' . $sort_option['order'];
			}

			// Make sure we don't have duplicates, this causes errors with unique
			// keys when rendering.
			if ( in_array( $option_value, $existing_option_values, true ) ) {
				continue;
			}

			$options[] = array(
				'label' => $sort_option['label'],
				'value' => $option_value,
			);

			if ( in_array( $option_value, $values, true ) ) {
				$this->value_labels[ $option_value ] = $sort_option['label'];
			}

			$existing_option_values[] = $option_value;
		}

		// Allow overriding for custom options.
		$options = apply_filters( 'search-filter/fields/control/options', $options, $this );

		// After create options hook.
		do_action( 'search-filter/fields/control/create_options/finish', $this );
		$this->set_options( $options );
	}
	/**
	 * Get the list of options based on data attributes
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ! parent::has_options() ) {
			$this->create_options();
		}

		return $this->options;
	}
}
