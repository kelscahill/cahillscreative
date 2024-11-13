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

use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Indexer\Database\Index_Query;

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
	 * Track if the regiseterd function has been run.
	 *
	 * This is used to prevent the function from running multiple times.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private static $has_registered = false;

	/**
	 * The type of the field.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $type = 'range';

	/**
	 * Register the field.
	 *
	 * @since 3.0.0
	 */
	public static function register() {
		if ( self::$has_registered ) {
			return;
		}
		add_action( 'search-filter/settings/register/fields', array( __CLASS__, 'setup' ), 2 );

		// Register the min/max values if auto detect is enabled.
		add_action( 'search-filter/record/set_attributes', array( __CLASS__, 'set_auto_detect_attributes' ), 1, 3 );
		self::$has_registered = true;
	}

	/**
	 * Add range specific settings.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {

		$add_setting_args = array(
			'extend_block_types' => array( 'range' ),
		);

		$setting = array(
			'name'      => 'rangeAutodetectMin',
			'label'     => __( 'Auto detect minimum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
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

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeMin',
			'label'     => __( 'Minimum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => '0',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'rangeAutodetectMin',
						'value'   => 'no',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeAutodetectMax',
			'label'     => __( 'Auto detect maximum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
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

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeMax',
			'label'     => __( 'Maximum value', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => '100',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'rangeAutodetectMax',
						'value'   => 'no',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeStep',
			'label'     => __( 'Step amount', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Number',
			'default'   => 10,
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

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
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeDecimalCharacter',
			'label'     => __( 'Decimal character', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '.',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'rangeDecimalPlaces',
						'value'   => 0,
						'compare' => '>',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeThousandCharacter',
			'label'     => __( 'Thousand character', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => ',',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeValuePrefix',
			'label'     => __( 'Value prefix', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeValueSuffix',
			'label'     => __( 'Value suffix', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'rangeSeparator',
			'label'     => __( 'Separator text', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

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
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

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
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );


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
			'context'   => array( 'admin/field', 'admin/field/range', 'block/field/range' ),
			'supports'  => array(
				'previewAPI' => true,
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'rangeSliderShowReset',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args ); */

	}


	public static function get_auto_detected_min_max( $attributes, $query_id, $field_id = 0 ) {

		$default_attributes = array(
			'rangeMin'           => 0,
			'rangeMax'           => 100,
			'rangeAutodetectMin' => 'no',
			'rangeAutodetectMax' => 'no',
		);

		$attributes = wp_parse_args( $attributes, $default_attributes );

		// TODO - use the indexer tables if the query is using the indexer, but not if we're in admin
		// as we don't know if the field has been indexed yet.
		$return_values = array(
			'min' => $attributes['rangeMin'],
			'max' => $attributes['rangeMax'],
		);

		// TODO we need to cache these queries locally so we don't keep running queries.
		$query = Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			return $return_values;
		}

		// Only when using the indexer, and the record ID is not 0 (so a saved field,
		// rather than admin preview) shoudl we use the indexer table to auto detect min/max.
		if ( $query->get_attribute( 'useIndexer' ) === 'yes' && $field_id !== 0 ) {

			$decimal_places = 0;
			if ( isset( $attributes['rangeDecimalPlaces'] ) ) {
				$decimal_places = absint( $attributes['rangeDecimalPlaces'] );
			}
			$cast_type = self::get_cast_type_from_decimal_places( $decimal_places );

			// Run the query for min/max on the index table.
			if ( $attributes['rangeAutodetectMin'] === 'yes' ) {
				// TODO - these could be more efficient queries by using MIN() and MAX() in the sql.
				$min_query = new Index_Query(
					array(
						'fields'   => array( 'value' ),
						'groupby'  => 'value',
						'field_id' => $field_id,
						'number'   => 1,
						'orderby'  => 'value',
						'orderas'  => $cast_type,
						'order'    => 'ASC',
					)
				);
				if ( count( $min_query->items ) === 0 ) {
					return $return_values;
				}
				$return_values['min'] = (string) $min_query->items[0]->value;
			}

			if ( $attributes['rangeAutodetectMax'] === 'yes' ) {
				// TODO - these could be more efficient queries by using MIN() and MAX() in the sql.
				$max_query = new Index_Query(
					array(
						'fields'   => array( 'value' ),
						'groupby'  => 'value',
						'field_id' => $field_id,
						'number'   => 1,
						'orderby'  => 'value',
						'orderas'  => $cast_type,
						'order'    => 'DESC',
					)
				);
				if ( count( $max_query->items ) === 0 ) {
					return $return_values;
				}

				$return_values['max'] = (string) $max_query->items[0]->value;

			}

			return $return_values;
		}

		if ( $attributes['rangeAutodetectMin'] === 'no' && $attributes['rangeAutodetectMax'] === 'no' ) {
			return $return_values;
		}

		// Then we're not using the indexer, try to use the supplied custom field key.
		$custom_field_key = apply_filters( 'search-filter/field/range/auto_detect_custom_field', '', $attributes );

		if ( empty( $custom_field_key ) ) {
			return $return_values;
		}

		if ( $attributes['rangeAutodetectMin'] === 'yes' ) {
			// Lookup max query taking into consideration the post type and status.
			// TODO - cache this repsonse as it's quite expensive.
			// Only reset it when a post of the connected post type is changed or saved.
			$min_query = new \WP_Query(
				array(
					'post_type'      => $query->get_attribute( 'postTypes' ),
					'post_status'    => $query->get_attribute( 'postStatus' ),
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'meta_key'       => $custom_field_key,
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
			$max_query = new \WP_Query(
				array(
					'post_type'      => $query->get_attribute( 'postTypes' ),
					'post_status'    => $query->get_attribute( 'postStatus' ),
					'orderby'        => 'meta_value_num',
					'order'          => 'DESC',
					'meta_key'       => $custom_field_key,
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

	 * @param mixed $decimal_places
	 * @return string
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

	public static function set_auto_detect_attributes( $attributes, $record_type, $record ) {
		if ( $record_type !== 'field' ) {
			return $attributes;
		}

		if ( $record->get_attribute( 'type' ) !== 'range' ) {
			return $attributes;
		}

		$query_id = $record->get_attribute( 'queryId' );

		$min_max                = self::get_auto_detected_min_max( $record->get_attributes(), $query_id, $record->get_id() );
		$attributes['rangeMin'] = $min_max['min'];
		$attributes['rangeMax'] = $min_max['max'];

		// TODO - move this into the WooCommerce integration class.
		if ( $record->get_attribute( 'dataType' ) === 'woocommerce' ) {
			$data_woocommerce = $record->get_attribute( 'dataWoocommerce' );

			if ( $data_woocommerce !== 'price' ) {
				return $attributes;
			}

			$custom_field_key       = '_price';
			$query_id               = $record->get_attribute( 'queryId' );
			$min_max                = self::get_auto_detected_min_max( $record->get_attributes(), $query_id, $record->get_id() );
			$attributes['rangeMin'] = $min_max['min'];
			$attributes['rangeMax'] = $min_max['max'];
		}

		return $attributes;
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
			$number_of_values++;
		}

		if ( $to !== '' ) {
			$number_of_values++;
		}

		if ( $number_of_values === 0 ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Now check things like data type and data source, to figure out which part of the query should be updated.
		if ( $this->attributes['dataType'] === 'custom_field' ) {

			$custom_field_key = $this->get_attribute( 'dataCustomField' );
			$decimal_places   = $this->get_attribute( 'rangeDecimalPlaces' );
			$cast_type        = self::get_cast_type_from_decimal_places( absint( $decimal_places ) );

			if ( $custom_field_key ) {

				if ( ! isset( $query_args['meta_query'] ) ) {
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
				} else {
					if ( $from !== '' ) {
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
		$query_args = \apply_filters( 'search-filter/field/range/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}

	/**
	 * Parses a value from the URL.
	 *
	 * @since 3.0.0
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();
		$values         = isset( $_GET[ $url_param_name ] ) ? urldecode_deep( sanitize_text_field( wp_unslash( $_GET[ $url_param_name ] ) ) ) : '';
		if ( $values !== '' ) {
			$this->set_values( explode( ',', $values ) );
		}
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_url_name() {
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}
		$url_name = '';
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
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
		$min_max   = self::get_auto_detected_min_max( $attributes, $query_id, $id );
		$json_data = parent::get_json_data();
		if ( $this->get_attribute( 'rangeAutodetectMin' ) === 'yes' ) {
			$json_data['overrideAttributes']['rangeMin'] = $min_max['min'];

		}
		if ( $this->get_attribute( 'rangeAutodetectMax' ) === 'yes' ) {
			$json_data['overrideAttributes']['rangeMax'] = $min_max['max'];
		}

		return $json_data;
	}
}
