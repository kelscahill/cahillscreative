<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields\Data_Types;

use Search_Filter\Fields\Choice;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Util;
use Search_Filter_Pro\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Custom_Field {

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Add custom field data type.
		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_custom_field_data_type' ), 10, 1 );
		// Register the custom field settings.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'register_custom_field_settings' ), 10 );
		// Upgrade the sort field to add custom field support.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'upgrade_sort_field' ), 10 );

		// Add the custom field options.
		add_filter( 'search-filter/fields/choice/options', array( __CLASS__, 'add_custom_field_choice_options' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/wp_query_args', array( __CLASS__, 'get_custom_field_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/range/auto_detect_custom_field', array( __CLASS__, 'custom_field_range_auto_detect' ), 10, 2 );
		add_filter( 'search-filter/fields/advanced/wp_query_args', array( __CLASS__, 'get_custom_field_advanced_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/field/url_name', array( __CLASS__, 'add_custom_field_url_name' ), 10, 2 );
	}

	/**
	 * Add custom field data type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function add_custom_field_data_type( array $setting ) {

		if ( $setting['name'] !== 'dataType' ) {
			return $setting;
		}

		if ( ! is_array( $setting['options'] ) ) {
			return $setting;
		}

		$setting['options'][] = array(
			'label' => __( 'Custom Field', 'search-filter' ),
			'value' => 'custom_field',
		);

		return $setting;
	}


	/**
	 * Register the custom field settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_custom_field_settings() {

		// Custom field setting for choosing a post meta key.
		$setting = array(
			'name'       => 'dataCustomField',
			'label'      => __( 'Custom Field', 'search-filter-pro' ),
			'help'       => __( 'Start typing to search for a custom field.', 'search-filter-pro' ),
			'group'      => 'data',
			'tab'        => 'settings',
			'type'       => 'string',
			'inputType'  => 'PostMetaSearch',
			'context'    => array( 'admin/field', 'block/field' ),
			'isDataType' => true, // Flag data types for the indexer to detect changes.
			'dependsOn'  => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'custom_field',
						'compare' => '=',
					),
				),
			),
			'supports'   => array(
				'previewAPI' => true,
			),
		);

		$add_setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'dataType',
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		// Custom field setting for choosing a post meta key.
		$setting = array(
			'name'      => 'dataCustomFieldIndexerNotice',
			'content'   => __( 'Enable the indexer in the query settings to improve performance and enable more data types.', 'search-filter-pro' ),
			'group'     => 'data',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Notice',
			'status'    => 'warning',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'custom_field',
						'compare' => '=',
					),
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			),
		);

		$add_setting_args = array(
			'position' => array(
				'placement' => 'before',
				'setting'   => 'dataCustomField',
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );
	}

	/**
	 * Update the sort field to add custom field support.
	 *
	 * @since 3.0.0
	 */
	public static function upgrade_sort_field() {
		// Add custom field option to data type setting.
		$sort_options_setting = Fields_Settings::get_setting( 'sortOptions' );

		$custom_field_option = array(
			'label' => __( 'Custom Field', 'search-filter' ),
			'value' => 'custom_field',
		);
		$sort_options_setting->add_option( $custom_field_option );
	}

	/**
	 * Add the custom field options.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $options    The options to add.
	 * @param    Field $field      The field to get the options for.
	 * @return   array    The options to add.
	 */
	public static function add_custom_field_choice_options( $options, $field ) {
		if ( count( $options ) > 0 ) {
			return $options;
		}
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $options;
		}
		if ( ! $field->get_attribute( 'dataCustomField' ) ) {
			return $options;
		}

		$custom_field_key = $field->get_attribute( 'dataCustomField' );

		// Determine if we should use the indexer.
		$use_indexer_query = false;
		// ID of `0` means an admin preview, which means we should use a regular query.
		if ( $field->get_id() > 0 ) {
			// Then check the useIndexer setting from the parent query.
			$parent_query = $field->get_query();
			if ( $parent_query && $parent_query->get_attribute( 'useIndexer' ) === 'yes' ) {
				$use_indexer_query = true;
			}
		}

		if ( $use_indexer_query ) {
			// Get unique indexed values for this field.
			$unique_values = \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct::get_unique_field_values( $field->get_id() );
		} else {
			// Use meta query to get distinct values.
			global $wpdb;
			$max_options = 100;
			if ( $field->get_attribute( 'dataTotalNumberOfOptions' ) ) {
				$max_options = absint( $field->get_attribute( 'dataTotalNumberOfOptions' ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$unique_values = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT(meta_value)
					FROM %i
					WHERE meta_key = %s AND meta_value != ''
					LIMIT 0, %d",
					$wpdb->postmeta,
					$custom_field_key,
					$max_options
				)
			);
		}

		if ( empty( $unique_values ) ) {
			return $options;
		}

		// Sort the values.
		$field_order     = $field->get_attribute( 'inputOptionsOrder' );
		$field_order_dir = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
		$sorted_values   = Util::sort_array( $unique_values, $field_order, $field_order_dir );

		// Build the options.
		$field_values = $field->get_values();
		foreach ( $sorted_values as $value ) {
			if ( $value === '' ) {
				continue;
			}

			$value = (string) $value;
			Choice::add_option_to_array(
				$options,
				array(
					'value' => $value,
					'label' => $value,
				),
				$field->get_id()
			);

			if ( in_array( $value, $field_values, true ) ) {
				$field->set_value_labels( array( $value => $value ) );
			}
		}

		// Support ordering by count (requires re-sorting after options are built).
		if ( $field_order === 'count' ) {
			$options = Util::sort_assoc_array_by_property( $options, 'count', 'numerical', $field_order_dir );
		}

		return $options;
	}
	/**
	 * Build the SQL order by clause from a field's settings.
	 *
	 * @since 3.0.0
	 *
	 * @param Field  $field    The field to get the order by for.
	 * @param string $property The property to order by.
	 * @return string The complete prepared SQL ORDER BY clause (or empty string for 'inherit').
	 */
	public static function build_sql_order_by( $field, $property ): string {
		$field_order     = $field->get_attribute( 'inputOptionsOrder' );
		$field_order_dir = $field->get_attribute( 'inputOptionsOrderDir' );

		if ( $field_order === 'inherit' ) {
			return '';
		}

		// Validate direction - ASC/DESC are SQL keywords, not placeholders.
		$direction = in_array( $field_order_dir, array( 'asc', 'desc' ), true )
			? strtoupper( $field_order_dir )
			: 'ASC';

		global $wpdb;
		if ( $field_order === 'alphabetical' ) {
			return $wpdb->prepare( ' ORDER BY %i', $property ) . ' ' . $direction;
		} elseif ( $field_order === 'numerical' ) {
			// Allow negatives so cast to signed.
			return $wpdb->prepare( ' ORDER BY CAST(%i AS SIGNED)', $property ) . ' ' . $direction;
		}

		return $wpdb->prepare( ' ORDER BY %i', $property ) . ' ' . $direction;
	}

	/**
	 * Add the custom field URL name.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $url_name    The URL name to add.
	 * @param    Field  $field       The field to add the URL name to.
	 * @return   string    The URL name.
	 */
	public static function add_custom_field_url_name( $url_name, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $url_name;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );

		if ( ! $custom_field_key ) {
			return $url_name;
		}

		return $custom_field_key;
	}

	/**
	 * Get the custom field WP query args.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @param    Field $field         The field to get the args for.
	 * @return   array    The updated WP query args.
	 */
	public static function get_custom_field_choice_wp_query_args( $query_args, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $query_args;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );
		if ( ! $custom_field_key ) {
			return $query_args;
		}

		$compare_type = 'IN';
		$match_mode   = $field->get_attribute( 'multipleMatchMethod' );
		$values       = $field->get_values();

		// Return early if no values are set.
		if ( empty( $values ) ) {
			return $query_args;
		}

		/**
		 * We are checking for multiple values to determine the query logic,
		 * but we don't check the field settigs itself.  This might be ok
		 * though, keep an eye on this.
		 *
		 * TODO - we could apply the same logic to the tax queries.
		 */
		$is_mutiple   = count( $values ) > 1;
		$compare_type = $match_mode === 'all' ? 'AND' : 'IN';

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( $is_mutiple && $compare_type === 'AND' ) {
			$sub_meta_query = array(
				'relation' => 'AND',
			);
			foreach ( $values as $value ) {
				$sub_meta_query[] = array(
					'key'     => sanitize_text_field( $custom_field_key ),
					'compare' => '=',
					'value'   => $value,
					'type'    => 'CHAR',
				);
			}
			$query_args['meta_query'][] = $sub_meta_query;
		} else {
			$query_args['meta_query'][] = array(
				'key'     => sanitize_text_field( $custom_field_key ),
				'value'   => array_map( 'sanitize_text_field', $values ),
				'compare' => 'IN',
				'type'    => 'CHAR',
			);
		}
		return $query_args;
	}
	/**
	 * Get the custom field WP query args.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @param    Field $field         The field to get the args for.
	 * @return   array    The updated WP query args.
	 */
	public static function get_custom_field_advanced_wp_query_args( $query_args, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $query_args;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );
		if ( ! $custom_field_key ) {
			return $query_args;
		}

		$values = $field->get_values();

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$values = $field->get_values();

		if ( count( $values ) === 1 ) {
			$query_args['meta_query'][] = array(
				'key'     => sanitize_text_field( $custom_field_key ),
				'value'   => sanitize_text_field( $values[0] ),
				'compare' => '=',
				'type'    => 'DATE',
			);
		}

		if ( count( $values ) === 2 ) {

			$from = $values[0];
			$to   = $values[1];

			// If there is no meta query key then create one.
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			// If there is no relation add it.
			if ( ! isset( $query_args['meta_query']['relation'] ) ) {
				$query_args['meta_query']['relation'] = 'AND';
			}

			$query_args['meta_query'][] = array(
				'key'     => sanitize_text_field( $custom_field_key ),
				'value'   => array( sanitize_text_field( $from ), sanitize_text_field( $to ) ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
		}
		return $query_args;
	}
	/**
	 * Get the custom field key for the range field when using price.
	 *
	 * @since 3.0.0
	 *
	 * @param string $custom_field_key    The custom field key.
	 * @param array  $attributes          The field attributes.
	 * @return string    The custom field key.
	 */
	public static function custom_field_range_auto_detect( $custom_field_key, $attributes ) {
		if ( ! isset( $attributes['dataType'] ) ) {
			return $custom_field_key;
		}
		if ( $attributes['dataType'] !== 'custom_field' ) {
			return $custom_field_key;
		}
		if ( ! isset( $attributes['dataCustomField'] ) ) {
			return $custom_field_key;
		}
		return $attributes['dataCustomField'];
	}
}
