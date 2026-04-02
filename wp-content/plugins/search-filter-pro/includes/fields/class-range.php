<?php
/**
 * Choice Filter base class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields
 */

namespace Search_Filter_Pro\Fields;

use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries\Query;
use Search_Filter\Util;
use Search_Filter_Pro\Cache;
use Search_Filter_Pro\Fields\Range\Queries\Indexer as Range_Indexer_Query;
use Search_Filter_Pro\Fields\Range\Queries\Legacy_Indexer as Range_Legacy_Indexer_Query;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Legacy\Query as Legacy_Indexer_Query;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Indexer\Query_Store;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles things a field with choices with need - such
 * as a list of options.
 */
class Range extends \Search_Filter\Fields\Range {

	/**
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		return 'range';
	}

	/**
	 * Track if the regiseterd function has been run.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	protected static $has_registered = false;

	/**
	 * The type of the field.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $type = 'range';

	/**
	 * Store fields data for range calculations.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $fields = array();

	/**
	 * Tiered_Cache instances keyed by query ID.
	 *
	 * @since 3.2.0
	 *
	 * @var array<int, Tiered_Cache>
	 */
	private static $cache_instances = array();

	/**
	 * Get the Tiered_Cache instance for a query.
	 *
	 * @since 3.2.0
	 *
	 * @param int $query_id The query ID.
	 * @return Tiered_Cache
	 */
	private static function get_cache_instance( $query_id ) {
		if ( ! isset( self::$cache_instances[ $query_id ] ) ) {
			self::$cache_instances[ $query_id ] = new Tiered_Cache( 'query_cache_' . $query_id );
		}
		return self::$cache_instances[ $query_id ];
	}

	/**
	 * Get the TTL for cache based on whether query has search.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $has_search Whether the query has a search term.
	 * @return int TTL in seconds.
	 */
	private static function get_cache_ttl( $has_search ) {
		return $has_search ? 2 * HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Register the field.
	 *
	 * @since 3.0.0
	 */
	public static function register() {
		if ( self::$has_registered ) {
			return;
		}
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'setup' ), 2 );

		// Register the min/max values for range fields based on the current query.
		self::hook_range_min_max_attributes();

		self::$has_registered = true;
	}

	/**
	 * Add range specific settings.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {

		// Custom field setting for choosing a post meta key.
		$setting = array(
			'name'      => 'dataMaxRangeOptionsNotice',
			'content'   => __( 'There is an upper limit of 200 options that can be generated.', 'search-filter-pro' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Notice',
			'status'    => 'warning',
			'context'   => array( 'admin/field', 'block/field' ),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeAutodetectMin',
			'label'     => __( 'Auto detect minimum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'context'   => array( 'admin/field', 'block/field' ),
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'default'   => 'no',
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeMin',
			'label'     => __( 'Minimum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => '0',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'rangeAutodetectMin',
						'value'   => 'no',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeAutodetectMax',
			'label'     => __( 'Auto detect maximum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'context'   => array( 'admin/field', 'block/field' ),
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'default'   => 'no',
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeMax',
			'label'     => __( 'Maximum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => '100',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'rangeAutodetectMax',
						'value'   => 'no',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeStep',
			'label'     => __( 'Step amount', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => 10,
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeDecimalPlaces',
			'label'     => __( 'Decimal places', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'min'       => 0,
			'max'       => 6,
			'default'   => 0,
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeDecimalCharacter',
			'label'     => __( 'Decimal character', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '.',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'rangeDecimalPlaces',
						'value'   => 0,
						'compare' => '>',
					),
				),
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeThousandCharacter',
			'label'     => __( 'Thousand character', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => ',',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeValuePrefix',
			'label'     => __( 'Value prefix', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeValueSuffix',
			'label'     => __( 'Value suffix', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeSeparator',
			'label'     => __( 'Separator text', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(),
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'rangeSliderTextPosition',
			'label'     => __( 'Text position', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'ButtonGroup',
			'options'   => array(
				array(
					'label' => __( 'Above', 'search-filter' ),
					'value' => 'above',
				),
				array(
					'label' => __( 'Below', 'search-filter' ),
					'value' => 'below',
				),
			),
			'default'   => 'above',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(),
			),
			'sanitize'  => array(
				'whitespace' => 'keep',
			),
		);

		Fields_Settings::add_setting( $setting );

		/*
		$setting = array(
			'name'      => 'rangeSliderShowReset',
			'label'     => __( 'Show reset button', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'label' => __( 'Yes', 'search-filter' ),
					'value' => 'yes',
				),
				array(
					'label' => __( 'No', 'search-filter' ),
					'value' => 'no',
				),
			),
			'default'   => 'no',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(),
			),
		);

		Fields_Settings::add_setting( $setting );


		$setting = array(
			'name'      => 'rangeSliderResetPosition',
			'label'     => __( 'Reset button position', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'ButtonGroup',
			'options'   => array(
				array(
					'label' => __( 'Above', 'search-filter' ),
					'value' => 'above',
				),
				array(
					'label' => __( 'Below', 'search-filter' ),
					'value' => 'below',
				),
			),
			'default'   => 'below',
			'context'   => array( 'admin/field', 'block/field' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'rangeSliderShowReset',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting );
		*/
	}

	/**
	 * Hook into filter to add range min/max attributes.
	 *
	 * Hook into render attributes to auto-detect min/max only when actually rendering.
	 * This prevents premature Indexer_Query creation during field initialization.
	 *
	 * @since 3.0.0
	 */
	private static function hook_range_min_max_attributes() {
		add_filter( 'search-filter/fields/field/render/attributes', array( __CLASS__, 'filter_range_min_max_attributes' ), 2, 2 );
	}

	/**
	 * Remove the filter for range min/max attributes.
	 *
	 * @since 3.0.0
	 */
	private static function unhook_range_min_max_attributes() {
		remove_filter( 'search-filter/fields/field/render/attributes', array( __CLASS__, 'filter_range_min_max_attributes' ), 2 );
	}

	/**
	 * Convert a value to float for range calculations.
	 *
	 * @since 3.1.8
	 *
	 * @param mixed $value         The value to convert.
	 * @param float $default_value Default value if conversion fails.
	 * @return float
	 */
	private static function to_float( $value, float $default_value = 0.0 ): float {
		if ( is_float( $value ) || is_int( $value ) ) {
			return (float) $value;
		}
		if ( is_string( $value ) && is_numeric( $value ) ) {
			return (float) $value;
		}
		return $default_value;
	}

	/**
	 * Convert a value to int for decimal places.
	 *
	 * @since 3.1.8
	 *
	 * @param mixed $value         The value to convert.
	 * @param int   $default_value Default value if conversion fails.
	 * @return int
	 */
	private static function to_int( $value, int $default_value = 0 ): int {
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && is_numeric( $value ) ) {
			return (int) $value;
		}
		if ( is_float( $value ) ) {
			return (int) $value;
		}
		return $default_value;
	}

	/**
	 * Calculate constrained min/max value based on step and rounding method.
	 *
	 * @since 3.0.0
	 *
	 * @param float  $base         The base value (minimum).
	 * @param float  $target       The target value to constrain.
	 * @param float  $step         The step amount.
	 * @param int    $decimals     The number of decimal places.
	 * @param string $round_method The rounding method (round, floor, ceil).
	 * @return float The constrained value.
	 */
	private static function calculate_constrained_min_max_value( float $base, float $target, float $step, int $decimals, string $round_method = 'round' ): float {
		// First, ensure the target is within the valid range,
		// assuming base is the minimum value.
		if ( $target < $base ) {
			return $base;
		}

		// Calculate how many steps away from the base.
		if ( $step === 0.0 ) {
			$steps_away = 0.0;
		} else {
			$steps_away = ( $target - $base ) / $step;
		}

		// Apply the appropriate rounding method.
		$rounded_steps = 0.0;
		if ( $round_method === 'floor' ) {
			$rounded_steps = floor( $steps_away );
		} elseif ( $round_method === 'ceil' ) {
			$rounded_steps = ceil( $steps_away );
		} else {
			// Default to 'round'.
			$rounded_steps = round( $steps_away );
		}

		$base = self::round_to_decimals( $base, $decimals );
		$step = self::round_to_decimals( $step, $decimals );

		// Calculate the constrained value.
		$constrained_value = $base + ( $rounded_steps * $step );

		return self::round_to_decimals( $constrained_value, $decimals );
	}

	/**
	 * Round a number to a specific number of decimal places.
	 *
	 * @since 3.0.0
	 *
	 * @param float $value    The value to round.
	 * @param int   $decimals The number of decimal places.
	 * @return float The rounded value.
	 */
	private static function round_to_decimals( float $value, int $decimals ): float {
		$factor = pow( 10, abs( $decimals ) );
		return round( $value * $factor ) / $factor;
	}


	/**
	 * Check if the current request is a saving REST request.
	 *
	 * Prevent calculating min/max values when we're saving.
	 *
	 * TODO - needs to be handled more elegantly in the rest api class.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private static function is_saving_rest_request() {
		if ( ! wp_is_serving_rest_request() ) {
			return false;
		}

		$route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';

		// Use regex or string matching.
		if ( str_starts_with( $route, '/wp/v2/' ) ) {
			return true;
		}
		if ( str_starts_with( $route, '/search-filter/v1/records/fields/' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Fetch all data for the field options when the field options
	 * are starting to be created.
	 *
	 * @since 3.0.0
	 *
	 * @param array                       $attributes The field attributes.
	 * @param \Search_Filter\Fields\Field $field      The field to start creating options for.
	 * @return array The filtered attributes.
	 */
	public static function filter_range_min_max_attributes( $attributes, $field ) {

		// Prevent looking up attributes and calculating min/max when we're saving.
		if ( self::is_saving_rest_request() ) {
			return $attributes;
		}

		$field_id = $field->get_id();

		if ( ! $field->has_init() ) {
			return $attributes;
		}

		if ( $field->get_attribute( 'type', true ) !== 'range' ) {
			return $attributes;
		}

		// Using `get_attribute()` will trigger an infinite loop, so unhook before using
		// get_attribute() or get_attributes().
		self::unhook_range_min_max_attributes();

		// If we've already calculated the min/max values before, re-use them.
		if ( isset( self::$fields[ $field_id ] ) ) {
			if ( array_key_exists( 'rangeMin', self::$fields[ $field_id ] ) && array_key_exists( 'rangeMax', self::$fields[ $field_id ] ) ) {
				$attributes['rangeMin'] = self::$fields[ $field_id ]['rangeMin'];
				$attributes['rangeMax'] = self::$fields[ $field_id ]['rangeMax'];
			}
			self::hook_range_min_max_attributes();
			return $attributes;
		}

		$query_id = absint( $field->get_attribute( 'queryId' ) );

		if ( $query_id === 0 ) {
			self::hook_range_min_max_attributes();
			return $attributes;
		}

		$query = Query::get_instance( absint( $query_id ) );

		if ( is_wp_error( $query ) ) {
			self::hook_range_min_max_attributes();
			return $attributes;
		}

		if ( ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) || $field->get_id() === 0 ) {

			$min_max                = self::wp_query_get_min_max( $field->get_attributes(), $query_id, $field->get_id() );
			$decimals               = self::to_int( $field->get_attribute( 'rangeDecimalPlaces' ) );
			$step                   = self::to_float( $field->get_attribute( 'rangeStep' ) );
			$min_val                = self::to_float( $min_max['min'] );
			$max_val                = self::to_float( $min_max['max'] );
			$attributes['rangeMin'] = self::round_to_decimals( $min_val, $decimals );
			$attributes['rangeMax'] = self::calculate_constrained_min_max_value( $min_val, $max_val, $step, $decimals, 'ceil' );
			// Now we know we're using an indexer query, init the field.
			self::$fields[ $field_id ] = array(
				'field'      => $field,
				'queryId'    => $query_id,
				'ids'        => array(), // Contains the resolved IDs for the field with the current query.
				'rangeMin'   => $attributes['rangeMin'],
				'rangeMax'   => $attributes['rangeMax'],
				'useIndexer' => 'no',
			);
			return $attributes;
		}

		// Build the query to get the current IDs if it's not already built.
		$indexer_query = Query_Store::get_query( $query_id );

		if ( $indexer_query === null ) {
			if ( Indexer::migration_completed() ) {
				$indexer_query = new Indexer_Query( $query );
			} else {
				$indexer_query = new Legacy_Indexer_Query( $query );
			}
			Query_Store::add_query( $indexer_query );
		}

		// Now we know we're using an indexer query, init the field.
		self::$fields[ $field_id ] = array(
			'field'      => $field,
			'queryId'    => $query_id,
			'ids'        => array(), // Contains the resolved IDs for the field with the current query.
			'rangeMin'   => 0,
			'rangeMax'   => 0,
			'useIndexer' => 'no',
		);

		// Get the relationship value from the source query.
		$field_relationship = $query->get_attribute( 'fieldRelationship' );

		$cache_key = self::get_range_field_cache_key( $field, $indexer_query, $field_relationship );

		$cached_min_max = null;

		if ( Cache::enabled() ) {
			$cache          = self::get_cache_instance( $query_id );
			$tiered_key     = 'range_' . $field_id . '_' . $cache_key;
			$found          = false;
			$cached_min_max = $cache->get( $tiered_key, $found );

			if ( ! $found ) {
				$cached_min_max = null; // Reset to trigger rebuild below.
			}
		}

		// There is no cached item in the DB so build it and store it.
		if ( $cached_min_max === null ) {
			// Get the filtered min/max values.
			$current_min_max    = array(
				'rangeMin' => null,
				'rangeMax' => null,
			);
			$unfiltered_min_max = array(
				'min' => null,
				'max' => null,
			);

			if ( Indexer::migration_completed() ) {
				// Use new indexer if migration has completed.
				$current_min_max = Range_Indexer_Query::get_filtered_min_max( $field, $indexer_query, $field_relationship );
				// Get the unfiltered min/max values.
				$unfiltered_min_max = Range_Indexer_Query::get_unfiltered_min_max( $field, $indexer_query );
			} else {
				// Fallback to legacy implementation if migration not completed.
				$current_min_max = Range_Legacy_Indexer_Query::get_filtered_min_max( $field, $indexer_query, $field_relationship );
				// Get the unfiltered min/max values.
				$unfiltered_min_max = Range_Legacy_Indexer_Query::get_unfiltered_min_max( $field, $indexer_query );
			}

			$cached_min_max = array(
				'currentMin'    => array_key_exists( 'min', $current_min_max ) ? $current_min_max['min'] : null,
				'currentMax'    => array_key_exists( 'max', $current_min_max ) ? $current_min_max['max'] : null,
				'unfilteredMin' => array_key_exists( 'min', $unfiltered_min_max ) ? $unfiltered_min_max['min'] : null,
				'unfilteredMax' => array_key_exists( 'max', $unfiltered_min_max ) ? $unfiltered_min_max['max'] : null,
			);

			if ( Cache::enabled() ) {
				$cache      = self::get_cache_instance( $query_id );
				$tiered_key = 'range_' . $field_id . '_' . $cache_key;
				$ttl        = self::get_cache_ttl( $indexer_query->has_search() );
				$cache->set( $tiered_key, wp_json_encode( $cached_min_max ), $ttl );
			}
		} else {
			$cached_min_max = json_decode( $cached_min_max, true );
		}

		// If the min & max are the same value, then we don't need to figure out the range, just limit it
		// to the single value. This accounts for situations where the exact value does not correlate to
		// a valid step value but we don't want to allow a range which can lead to no results.
		$selected_values     = $field->get_values();
		$has_selected_values = count( $selected_values ) === 2;

		// Build the range min/max based on unfiltered values if there are no
		// active fields.
		$range_min = $cached_min_max['unfilteredMin'];
		$range_max = $cached_min_max['unfilteredMax'];
		// If there are active fields, build the range min/max based on the current query.
		$query_has_active_fields = $query->has_active_fields( array( $field_id ) );

		if ( $query_has_active_fields ) {
			$range_min = $cached_min_max['currentMin'];
			$range_max = $cached_min_max['currentMax'];
		}

		$decimals        = self::to_int( $field->get_attribute( 'rangeDecimalPlaces' ) );
		$step            = self::to_float( $field->get_attribute( 'rangeStep' ) );
		$unfiltered_min  = self::to_float( $cached_min_max['unfilteredMin'] );
		$constrained_min = self::calculate_constrained_min_max_value( $unfiltered_min, self::to_float( $range_min ), $step, $decimals, 'floor' );
		$constrained_max = self::calculate_constrained_min_max_value( $unfiltered_min, self::to_float( $range_max ), $step, $decimals, 'ceil' );

		$calculated_min = $constrained_min;
		$calculated_max = $constrained_max;

		// Check if the current range value is included in the selected values.
		$selected_values_include_range = false;

		$selected_min = null;
		$selected_max = null;

		if ( $has_selected_values ) {
			$min_value                     = (float) $cached_min_max['currentMin'];
			$max_value                     = (float) $cached_min_max['currentMax'];
			$selected_min                  = (float) $selected_values[0];
			$selected_max                  = (float) $selected_values[1];
			$selected_values_include_range = $selected_min <= $min_value && $selected_max >= $max_value;
		}
		if ( $cached_min_max['currentMax'] === null && $cached_min_max['currentMin'] === null ) {
			self::$fields[ $field_id ]['rangeMin'] = null;
			self::$fields[ $field_id ]['rangeMax'] = null;
			$attributes['rangeMin']                = null;
			$attributes['rangeMax']                = null;
			self::hook_range_min_max_attributes();

			return $attributes;

		} elseif ( $has_selected_values ) {
			if ( $selected_values_include_range && $query_has_active_fields ) {
				$calculated_min = $selected_min;
				$calculated_max = $selected_max;
			} else {
				// If we have selected values, then don't allow the constrained values if they're within the selected values
				// to avoid odd UX with the slider jumping around.
				$calculated_min = $constrained_min < $selected_min ? $constrained_min : $selected_min;
				$calculated_max = $constrained_max > $selected_max ? $constrained_max : $selected_max;
			}
		} elseif ( $cached_min_max['currentMin'] === $cached_min_max['currentMax'] ) {
			// If the min & max are the same value,  and there are no selected values (or the selected values include the single value)
			// then set the range to the current value.
			$calculated_min = $cached_min_max['currentMin'];
			$calculated_max = $cached_min_max['currentMax'];
		}

		// Set local store.
		self::$fields[ $field_id ]['rangeMin'] = self::round_to_decimals( self::to_float( $calculated_min ), $decimals );
		self::$fields[ $field_id ]['rangeMax'] = self::round_to_decimals( self::to_float( $calculated_max ), $decimals );
		// Set attributes.
		$attributes['rangeMin'] = self::$fields[ $field_id ]['rangeMin'];
		$attributes['rangeMax'] = self::$fields[ $field_id ]['rangeMax'];

		self::hook_range_min_max_attributes();
		return $attributes;
	}
	/**
	 * Get cache key for a range field.
	 *
	 * Based on the field relationship so we can reuse the cache key where possible.
	 *
	 * @since 3.1.7
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to get the cache key for.
	 * @param    Indexer_Query               $indexer_query    The indexer query.
	 * @param    string                      $field_relationship    The field relationship.
	 * @return   string    The cache key.
	 */
	private static function get_range_field_cache_key( $field, $indexer_query, $field_relationship ) {

		$field_id = $field->get_id();
		// If field relationship is 'all' and match mode is 'all', use the cache key
		// as it is, it contains all the fields values that are being used.
		$cache_key = $indexer_query->get_cache_key();

			// Get the filtered args except the current field into the cache key.
		if ( $field_relationship === 'all' ) {

			$cache_args       = $indexer_query->get_cache_query_args();
			$field_cache_args = $indexer_query->get_field_cache_args();
			// Unset the current field ID from the cache args.
			if ( isset( $field_cache_args[ $field_id ] ) ) {
				unset( $field_cache_args[ $field_id ] );
			}
			$cache_key = $indexer_query->create_cache_key(
				$cache_args,
				$field_cache_args
			);
		} elseif ( $field_relationship === 'any' ) {
			$cache_args = $indexer_query->get_cache_query_args();
			$cache_key  = $indexer_query->create_cache_key(
				$cache_args,
				array(
					$field_id => $field->get_values(),
				)
			);
		}

		return $cache_key;
	}

	/**
	 * Get min/max values using WP_Query when indexer is not available.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes The field attributes.
	 * @param int   $query_id   The query ID.
	 * @param int   $field_id   The field ID.
	 * @return array The min/max values.
	 */
	public static function wp_query_get_min_max( $attributes, $query_id, $field_id = 0 ) {

		$default_attributes = array(
			'rangeMin'           => 0,
			'rangeMax'           => 100,
			'rangeAutodetectMin' => 'no',
			'rangeAutodetectMax' => 'no',
		);

		$attributes = wp_parse_args( $attributes, $default_attributes );

		$return_values = array(
			'min' => $attributes['rangeMin'],
			'max' => $attributes['rangeMax'],
		);

		$query = Query::get_instance( absint( $query_id ) );
		if ( is_wp_error( $query ) ) {
			return $return_values;
		}

		if ( $attributes['rangeAutodetectMin'] === 'no' && $attributes['rangeAutodetectMax'] === 'no' ) {
			return $return_values;
		}

		// Then we're not using the indexer, try to use the supplied custom field key.
		$custom_field_key = apply_filters( 'search-filter/fields/range/auto_detect_custom_field', '', $attributes );

		if ( empty( $custom_field_key ) ) {
			return $return_values;
		}

		if ( $attributes['rangeAutodetectMin'] === 'yes' ) {
			// Lookup max query taking into consideration the post type and status.
			// TODO - cache this repsonse as it's quite expensive.
			// Only reset it when a post of the connected post type is changed or saved.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$min_query = new \WP_Query(
				array(
					'post_type'      => $query->get_attribute( 'postTypes' ),
					'post_status'    => $query->get_attribute( 'postStatus' ),
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'meta_key'       => $custom_field_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'posts_per_page' => 1,
				)
			);
			$min_posts = $min_query->posts;
			if ( ( count( $min_posts ) === 1 ) && ( isset( $min_posts[0] ) ) ) {
				$min_post = $min_posts[0];
				if ( isset( $min_post->ID ) ) {
					$min_value            = get_post_meta( $min_post->ID, $custom_field_key, true );
					$return_values['min'] = (string) $min_value;
				}
			}
		}
		if ( $attributes['rangeAutodetectMax'] === 'yes' ) {
			// Lookup max query taking into consideration the post type and status.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$max_query = new \WP_Query(
				array(
					'post_type'      => $query->get_attribute( 'postTypes' ),
					'post_status'    => $query->get_attribute( 'postStatus' ),
					'orderby'        => 'meta_value_num',
					'order'          => 'DESC',
					'meta_key'       => $custom_field_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'posts_per_page' => 1,
				)
			);
			$max_posts = $max_query->posts;
			if ( ( count( $max_posts ) === 1 ) && ( isset( $max_posts[0] ) ) ) {
				$max_post = $max_posts[0];
				if ( isset( $max_post->ID ) ) {
					$max_value            = get_post_meta( $max_post->ID, $custom_field_key, true );
					$return_values['max'] = (string) $max_value;
				}
			}
		}
		return $return_values;
	}

	/**
	 * Generates a cast type (for SQL) based on the decimal places.
	 *
	 * If the decimal places are 0, then cast to SIGNED.
	 * If the decimal places are greater than 0, then cast to DECIMAL.
	 *
	 * Important, this must be SQL safe before returning.
	 *
	 * @param mixed $decimal_places The number of decimal places.
	 * @return string The SQL cast type.
	 */
	private static function get_cast_type_from_decimal_places( $decimal_places ) {
		$cast_type = 'SIGNED';
		if ( isset( $decimal_places ) ) {
			$decimal_places = absint( $decimal_places );
			if ( $decimal_places > 0 ) {
				$cast_type = 'DECIMAL(12,' . $decimal_places . ')';
			}
		}
		return $cast_type;
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @return   array    The updated WP query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {
		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only set post_type if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		$values = $this->get_values();
		if ( count( $values ) !== 2 ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		$from = $values[0];
		$to   = $values[1];

		$number_of_values = 0;

		if ( $from !== '' ) {
			++$number_of_values;
		}

		if ( $to !== '' ) {
			++$number_of_values;
		}

		if ( $number_of_values === 0 ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Now check things like data type and data source, to figure out which part of the query should be updated.
		if ( $this->get_attribute( 'dataType' ) === 'custom_field' ) {

			$custom_field_key = $this->get_attribute( 'dataCustomField' );
			$decimal_places   = $this->get_attribute( 'rangeDecimalPlaces' );
			$cast_type        = self::get_cast_type_from_decimal_places( absint( $decimal_places ) );

			if ( $custom_field_key ) {

				if ( ! isset( $query_args['meta_query'] ) ) {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$query_args['meta_query'] = array();
				}

				// If there is no relation add it.
				if ( ! isset( $query_args['meta_query']['relation'] ) ) {
					$query_args['meta_query']['relation'] = 'AND';
				}
				if ( $number_of_values === 2 ) {
					// If we have both min and max values, we can use BETWEEN.
					$query_args['meta_query'][] = array(
						'key'     => sanitize_text_field( $custom_field_key ),
						'value'   => array( sanitize_text_field( $from ), sanitize_text_field( $to ) ),
						'compare' => 'BETWEEN',
						'type'    => $cast_type,
					);
				} elseif ( $from !== '' ) {
						// If we have only a min value, we can use greater than or equal to.
						$query_args['meta_query'][] = array(
							'key'     => sanitize_text_field( $custom_field_key ),
							'value'   => sanitize_text_field( $from ),
							'compare' => '>=',
							'type'    => $cast_type,
						);
				} elseif ( $to !== '' ) {
					// If we have only a max value, we can use less than or equal to.
					$query_args['meta_query'][] = array(
						'key'     => sanitize_text_field( $custom_field_key ),
						'value'   => sanitize_text_field( $to ),
						'compare' => '<=',
						'type'    => $cast_type,
					);
				}
			}
		}
		return $this->return_apply_wp_query_args( $query_args );
	}

	/**
	 * Apply the query_args for regular WP queries.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @return   array    The updated WP query args.
	 */
	private function return_apply_wp_query_args( $query_args ) {
		$query_args = \apply_filters( 'search-filter/fields/range/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}

	/**
	 * Parses a value from the URL.
	 *
	 * @since 3.0.0
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST
		// but with wp_unslash already applied.
		$request_var = Util::get_request_var( $url_param_name );
		$values      = $request_var ?? '';

		if ( $values !== '' ) {
			// Explode and sanitize each value.
			$values = explode( ',', $values );
			$values = array_map( 'sanitize_text_field', $values );
			$this->set_values( $values );
		}
	}

	/**
	 * Get the JSON data for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   array
	 */
	public function get_json_data() {

		if ( ! $this->has_init() ) {
			return array();
		}

		$json_data = parent::get_json_data();

		if ( ! isset( $json_data['attributes'] ) ) {
			return $json_data;
		}
		$attributes = $json_data['attributes'];

		if ( ! isset( $attributes['queryId'] ) ) {
			return $json_data;
		}

		$id = 0;
		if ( isset( $json_data['id'] ) ) {
			$id = $json_data['id'];
		}
		$id        = 0;
		$query_id  = $attributes['queryId'];
		$min_max   = self::wp_query_get_min_max( $attributes, $query_id, $id );
		$json_data = parent::get_json_data();

		if ( $this->get_attribute( 'rangeAutodetectMin' ) === 'yes' ) {
			$json_data['overrideAttributes']['rangeMin'] = $min_max['min'];
		}
		if ( $this->get_attribute( 'rangeAutodetectMax' ) === 'yes' ) {
			$decimals                                    = self::to_int( $this->get_attribute( 'rangeDecimalPlaces' ) );
			$step                                        = self::to_float( $this->get_attribute( 'rangeStep' ) );
			$min_val                                     = self::to_float( $min_max['min'] );
			$max_val                                     = self::to_float( $min_max['max'] );
			$json_data['overrideAttributes']['rangeMax'] = self::calculate_constrained_min_max_value( $min_val, $max_val, $step, $decimals, 'ceil' );
		}

		return $json_data;
	}


	/**
	 * Set the values for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $values The values to set.
	 */
	public function set_values( $values ) {
		parent::set_values( $values );

		if ( count( $values ) !== 2 ) {
			return;
		}

		$value                        = implode( ',', $values );
		$this->value_labels[ $value ] = $this->format_range_value( $values );
	}

	/**
	 * Format range values for display.
	 *
	 * @since 3.0.0
	 *
	 * @param array $values The range values (min, max).
	 * @return string The formatted range value.
	 */
	private function format_range_value( $values ) {
		if ( count( $values ) !== 2 ) {
			return '';
		}

		$from = $values[0];
		$to   = $values[1];

		$value_prefix        = $this->get_attribute( 'rangeValuePrefix' );
		$value_suffix        = $this->get_attribute( 'rangeValueSuffix' );
		$decimals            = $this->get_attribute( 'rangeDecimalPlaces' ) ?? 0;
		$thousands_character = $this->get_attribute( 'rangeThousandCharacter' );
		$decimal_character   = $this->get_attribute( 'rangeDecimalCharacter' );
		$range_separator     = $this->get_attribute( 'rangeSeparator' );

		if ( absint( $decimals ) >= 0 ) {
			// If we have decimals, lets make sure the thousands character is not matching
			// the decimal character.  In JS this causes an error, so lets match the logic
			// there for consistency.
			if ( $thousands_character === $decimal_character ) {
				$thousands_character = '';
			}
		}

		$from = $value_prefix . number_format( $from, self::to_int( $decimals ), $decimal_character, $thousands_character ) . $value_suffix;
		$to   = $value_prefix . number_format( $to, self::to_int( $decimals ), $decimal_character, $thousands_character ) . $value_suffix;

		// Add spacing around the range separator, usually the seperator has a container
		// with spacing applied via margin/padding, but in the selection field we can't
		// add html so we need to ensure there is at least some spacing around the seperator.
		return $from . ' ' . $range_separator . ' ' . $to;
	}
}
