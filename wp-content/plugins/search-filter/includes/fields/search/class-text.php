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
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		return 'search';
	}

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
		'fieldMargin'                => true,
		// 'fieldPadding'               => true,
		'inputMargin'                => true,
		'labelBorderStyle'           => true,
		'labelBorderRadius'          => true,
		'descriptionBorderStyle'     => true,
		'descriptionBorderRadius'    => true,
		'inputClearPadding'          => true,
		'inputBorderRadius'          => true,


		'inputScale'                 => true,
		'inputColor'                 => true,
		'inputBackgroundColor'       => true,
		'inputPlaceholderColor'      => true,
		'inputBorder'                => true,
		'inputBorderHoverColor'      => true,
		'inputBorderFocusColor'      => true,
		'inputIconColor'             => true,
		'inputActiveIconColor'       => true,
		'inputInactiveIconColor'     => true,
		'inputClearColor'            => true,
		'inputClearHoverColor'       => true,
		'inputShadow'                => true,
		'inputPadding'               => true,
		'inputGap'                   => true,
		'inputIconSize'              => true,
		'inputIconPadding'           => true,
		'inputClearSize'             => true,
		'inputIconPosition'          => true,

		'labelColor'                 => true,
		'labelBackgroundColor'       => true,
		'labelPadding'               => true,
		'labelMargin'                => true,
		'labelScale'                 => true,

		'descriptionColor'           => true,
		'descriptionBackgroundColor' => true,
		'descriptionPadding'         => true,
		'descriptionMargin'          => true,
		'descriptionScale'           => true,
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
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to search by text input.', 'search-filter' );
	}


	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'          => true,
		'width'             => true,
		'queryId'           => true,
		'stylesId'          => true,
		'type'              => true,
		'label'             => true,
		'showLabel'         => true,
		'showLabelNotice'   => true,
		'showDescription'   => true,
		'description'       => true,
		'dataType'          => array(
			'values' => array(
				'post_attribute' => true,
			),
		),
		'dataPostAttribute' => array(
			'values' => array(
				'default' => true,
			),
		),
		'dataTaxonomy'      => true,
		'inputType'         => true,
		'placeholder'       => true,
		'inputShowIcon'     => true,
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
		return 's';
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
		$value       = $request_var ?? '';
		// Support multibyte space as a single space.
		$value = str_replace( '　', ' ', $value );
		// Santize after str_replace.
		$value = sanitize_text_field( $value );
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
	public function apply_wp_query_args( $query_args = array() ) {

		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only apply if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		if ( ! empty( $this->get_value() ) ) {
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
