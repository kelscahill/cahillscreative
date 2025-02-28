<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Search;

use Search_Filter\Util;
use Search_Filter_Pro\Fields\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Autocomplete extends Search {

	/**
	 * The nonce key for the autocomplete suggestions.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	const SUGGESTIONS_NONCE = 'search';

	/**
	 * The supported icons.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $icons = array(
		'search',
		'clear',
		'spinner-circle',
	);

	public $supports = array(
		// 'autoSubmit',
	);

	/**
	 * The supported styles.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputSelectedColor',
		'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
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
	 * The input type.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $input_type = 'autocomplete';

	/**
	 * Gets the label for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public static function get_label() {
		return __( 'Autocomplete', 'search-filter' );
	}

	/**
	 * The data support for the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $data_support = array(
		array(
			'dataType'          => 'post_attribute',
			'dataPostAttribute' => array( 'default', 'post_type', 'post_status' ),
		),
		array(
			'dataType' => 'taxonomy',
		),
		array(
			'dataType' => 'custom_field',
		),
		array(
			'dataType' => 'acf_field',
		),
	);

	/**
	 * The setting support for the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $setting_support = array(
		'placeholder'             => true,
		'dataLimitOptionsCount'   => true,
		'taxonomyHierarchical'    => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderBy'         => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderDir'        => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTermsConditions' => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTerms'           => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputOptionsOrder'       => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
		'hideEmpty'               => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		/*
		 'showCount'               => array(
			'conditions' => array(
				'option'  => 'dataType',
				'compare' => '=',
				'value'   => 'taxonomy',
			),
		), */
		'inputShowIcon'           => true,
		'autoSubmit'              => true,
		'autoSubmitDelay'         => true,
		'showLabel'               => true,
		'labelInitialVisibility'  => true,
		'labelToggleVisibility'   => true,
	);

	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		parent::init();

		$value       = $this->get_value();
		$render_data = array(
			'uid'   => self::get_instance_id( 'autocomplete' ),
			'value' => $value,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'uid'         => 'absint',
			'value'       => 'esc_attr',
			'placeholder' => 'esc_attr',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );

		// TODO - we might need to move where we do this, depends if
		// we want to regenerate the link when attributes change.
		$this->set_api_url();
	}

	/**
	 * Sets the API URL for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public function set_api_url() {
		$api_url = get_rest_url( null, 'search-filter-pro/v1/fields/autocomplete/suggestions' );
		// TODO - decide if we need seperate endpoints for different field/data types?
		$this->update_connected_data( 'autocompletApiUrl', $api_url );
		return $api_url;
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
		$url_name = 's';
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * Parses a value from the URL.
	 *
	 * @since    3.0.0
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		if ( ! method_exists( '\Search_Filter\Util', 'get_request_var' ) ) {
			return;
		}
		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = $request_var !== null ? urldecode_deep( sanitize_text_field( wp_unslash( $request_var ) ) ) : '';

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}

	/**
	 * Get the autocomplete suggestions for the field.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $search    The search term.
	 * @return   array
	 */
	public function get_autocomplete_suggestions( $search ) {
		// Hook into the suggestions and return early if we have a result.
		$suggestions = apply_filters( 'search-filter-pro/field/search/autocomplete/suggestions', false, $search, $this );

		// Suggestions should be an array of strings.
		if ( $suggestions !== false ) {
			return $suggestions;
		}

		// Handle the built in data types.
		$data_type = $this->get_attribute( 'dataType' );

		if ( $data_type === 'post_attribute' ) {
			$attribute_data_type = $this->get_attribute( 'dataPostAttribute' );
			if ( ( $attribute_data_type === 'default' ) || ( $attribute_data_type === '' ) ) {
				$post_titles = $this->search_post_titles( $search );
				return $post_titles;
			} elseif ( $attribute_data_type === 'post_type' ) {
				$post_types = $this->search_post_type_labels( $search, 'label' );
				return $post_types;
			} elseif ( $attribute_data_type === 'post_status' ) {
				$post_stati = $this->search_post_stati_labels( $search, 'label' );
				return $post_stati;
			}
		} elseif ( $data_type === 'taxonomy' ) {
			$taxonomy_name = $this->get_attribute( 'dataTaxonomy' );
			$terms         = $this->search_taxonomy_term_labels( $search, $taxonomy_name, 'name' );
			return $terms;
		} elseif ( $data_type === 'custom_field' ) {
			$custom_field = $this->get_attribute( 'dataCustomField' );
			$terms        = $this->search_custom_fields( $search, $custom_field );
			return $terms;
		}

		return array();
	}

	/**
	 * Registers any settings/apis etc that need to exist
	 * permanently for this field.
	 *
	 * @since    3.0.0
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'search-filter/frontend/enqueue_scripts/data', array( __CLASS__, 'add_suggestions_nonce' ) );
	}

	/**
	 * Registers the REST API routes for the field.
	 *
	 * @since    3.0.0
	 */
	public static function routes() {
		// TODO - get rid off this from our storybook routes that connect to the test API endpoint.
		register_rest_route(
			'search-filter-pro/v1',
			'/fields/autocomplete/suggestions',
			array(
				array(
					'methods'             => array( \WP_REST_Server::READABLE ),
					'callback'            => array( __CLASS__, 'get_autocomplete_suggestions_for_field' ),
					'args'                => array(
						'search'  => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'fieldId' => array(
							'description'       => __( 'The field ID.', 'search-filter' ),
							'type'              => 'absint',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'nonce'   => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'permission_callback' => '__return_true',
				),
				array(
					// For some reason CREATABLE work with POST, so use CREATABLE instead.
					'methods'             => array( \WP_REST_Server::CREATABLE ),
					'callback'            => array( __CLASS__, 'get_autocomplete_suggestions_for_preview' ),
					'args'                => array(
						'search'     => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'attributes' => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
					),
					'permission_callback' => array( __CLASS__, 'rest_permissions' ),
				),
			)
		);
	}

	/**
	 * Adds the suggestions nonce to the REST API response.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $data    The REST API response data.
	 * @return   array
	 */
	public static function add_suggestions_nonce( $data ) {
		$data['suggestionsNonce'] = wp_create_nonce( self::SUGGESTIONS_NONCE );
		return $data;
	}

	/**
	 * Handles the REST API request for getting autocomplete suggestions.
	 *
	 * @since    3.0.0
	 *
	 * @param    \WP_REST_Request $request    The request object.
	 * @return   \WP_REST_Response
	 */
	public static function get_autocomplete_suggestions_for_field( $request ) {
		$search   = $request->get_param( 'search' );
		$field_id = $request->get_param( 'fieldId' );
		$nonce    = $request->get_param( 'nonce' );

		// TODO - we probably want to make this optional in case people want to use their WP
		// site as an API endpoint.
		if ( ! wp_verify_nonce( $nonce, self::SUGGESTIONS_NONCE ) ) {
			return \rest_convert_error_to_response( new \WP_Error( 'search-filter-pro/invalid-nonce', __( 'Invalid nonce.', 'search-filter-pro' ), array( 'status' => 403 ) ) );
		}
		$field = null;
		if ( $field_id !== 0 ) {
			$field = self::find( array( 'id' => $field_id ) );
			if ( is_wp_error( $field ) ) {
				return \rest_convert_error_to_response( $field );
			}

			if ( get_class( $field ) !== 'Search_Filter_Pro\Fields\Search\Autocomplete' ) {
				return \rest_convert_error_to_response( new \WP_Error( 'search-filter-pro/incorrect-field-class', __( 'Field is not an Autocomplete field.', 'search-filter-pro' ), array( 'status' => 400 ) ) );
			}
		} else {
			// Error.
			return \rest_convert_error_to_response( new \WP_Error( 'search-filter-pro/invalid-field-id', __( 'Bad field ID.', 'search-filter-pro' ), array( 'status' => 400 ) ) );
		}
		$result = $field->get_autocomplete_suggestions( $search );
		return rest_ensure_response( $result );
	}

	/**
	 * Handles the REST API request for getting autocomplete suggestions.
	 *
	 * @since    3.0.0
	 *
	 * @param    \WP_REST_Request $request    The request object.
	 * @return   \WP_REST_Response
	 */
	public static function get_autocomplete_suggestions_for_preview( $request ) {
		$search     = $request->get_param( 'search' );
		$attributes = $request->get_param( 'attributes' );
		// Create a field from attributes.
		$field = self::create( $attributes );
		if ( get_class( $field ) !== 'Search_Filter_Pro\Fields\Search\Autocomplete' ) {
			return \rest_convert_error_to_response( new \WP_Error( 'search-filter-pro/incorrect-field-class', __( 'Field is not an Autocomplete field.', 'search-filter-pro' ), array( 'status' => 400 ) ) );
		}
		$result = $field->get_autocomplete_suggestions( $search );
		return rest_ensure_response( $result );
	}

	/**
	 * Registers the REST API permissions for the field.
	 *
	 * @since    3.0.0
	 */
	public static function rest_permissions() {
		// TODO - need to create proper roles.
		return current_user_can( 'manage_options' );
	}
}
