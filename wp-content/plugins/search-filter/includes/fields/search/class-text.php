<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields\Search;

use Search_Filter\Fields\Search;
use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Text extends Search {

	/**
	 * The input type name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'text';

	/**
	 * The type of field.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $type = 'search';

	/**
	 * List of icons the input type supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $icons = array(
		'search',
		'clear',
	);

	/**
	 * List of styles the input type supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
		'inputActiveIconColor',
		'inputInactiveIconColor',
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

	/**
	 * Get the label for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Text', 'search-filter' );
	}


	/**
	 * Supported data types.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $data_support = array();

	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'showLabel'     => true,
		'placeholder'   => true,
		'inputShowIcon' => true,
	);

	/**
	 * Override the init_render_data and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {

		$value       = $this->get_value();
		$render_data = array(
			'uid'   => self::get_instance_id( 'text' ),
			'value' => $value,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'uid'         => 'absint',
			'value'       => 'esc_attr',
			'placeholder' => 'esc_attr',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );
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
		return 's';
	}

	/**
	 * Parses a value from the URL.
	 *
	 * @since 3.0.0
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = $request_var !== null ? sanitize_text_field( wp_unslash( $request_var ) ) : '';
		// Support multibyte space as a single space.
		$value = str_replace( '　', ' ', $value );
		// And trim any whitespace.
		$value = trim( $value );
		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only set post_type if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		$is_default_search = false;
		if ( empty( $this->get_attribute( 'dataType' ) ) || ( $this->get_attribute( 'dataType' ) === 'post_attribute' && $this->get_attribute( 'dataPostAttribute' ) === 'default' ) ) {
			$is_default_search = true;
		}
		if ( ! empty( $this->get_value() ) && $is_default_search ) {
			$query_args['s'] = $this->get_value();
			// If we have ordering by relevance set in the query, then override the orderby
			// completely.  Relevance doesn't work when combined with multiple order parameter,
			// and it should be the only one set when searching.
			if ( ! isset( $query_args['orderby'] ) || ! is_array( $query_args['orderby'] ) ) {
				$query_args['orderby'] = array();
			}

			$should_set_relevance = false;
			foreach ( $query_args['orderby'] as $order_by => $order_dir ) {
				if ( $order_by === 'relevance' ) {
					$should_set_relevance = true;
					break;
				}
			}
			if ( $should_set_relevance ) {
				$query_args['orderby'] = 'relevance';
				if ( isset( $query_args['order'] ) ) {
					unset( $query_args['order'] );
				}
			}
		}

		return parent::apply_wp_query_args( $query_args );
	}
}
