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

use Search_Filter\Core\Deprecations;
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
	 * List of components this field relies on.
	 *
	 * @var array
	 */
	public $components = array(
		'combobox',
	);

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

	/**
	 * Features supported by the autocomplete field.
	 *
	 * @var array
	 */
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

		'fieldMargin'                  => true,
		'inputMargin'                  => true,
		'labelBorderStyle'             => true,
		'labelBorderRadius'            => true,
		'descriptionBorderStyle'       => true,
		'descriptionBorderRadius'      => true,
		'inputClearPadding'            => true,
		'inputBorderRadius'            => true,

		'inputScale'                   => true,
		'inputColor'                   => true,
		'inputBackgroundColor'         => true,
		'inputPlaceholderColor'        => true,
		'inputSelectedColor'           => true,
		'inputSelectedBackgroundColor' => true,
		'inputBorder'                  => true,
		'inputBorderHoverColor'        => true,
		'inputBorderFocusColor'        => true,
		'inputIconColor'               => true,
		'inputClearColor'              => true,
		'inputClearHoverColor'         => true,
		'inputShadow'                  => true,
		'inputPadding'                 => true,
		'inputGap'                     => true,
		'inputIconSize'                => true,
		'inputIconPadding'             => true,
		'inputClearSize'               => true,
		'inputIconPosition'            => true,

		'labelColor'                   => true,
		'labelBackgroundColor'         => true,
		'labelPadding'                 => true,
		'labelMargin'                  => true,
		'labelScale'                   => true,

		'descriptionColor'             => true,
		'descriptionBackgroundColor'   => true,
		'descriptionPadding'           => true,
		'descriptionMargin'            => true,
		'descriptionScale'             => true,
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
	 * The input type.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $input_type = 'autocomplete';

	/**
	 * Track if the regiseterd function has been run.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	protected static $has_registered = false;

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
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to search with autocomplete suggestions.', 'search-filter' );
	}

	/**
	 * The setting support for the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $setting_support = array(
		'addClass'                      => true,
		'width'                         => true,
		'queryId'                       => true,
		'stylesId'                      => true,
		'type'                          => true,
		'label'                         => true,
		'showLabel'                     => true,
		'showLabelNotice'               => true,
		'showDescription'               => true,
		'description'                   => true,
		'dataPostAttribute'             => array(
			'values' => array(
				'default'     => true,
				'post_type'   => true,
				'post_status' => true,
			),
		),
		'dataTaxonomy'                  => true,
		'inputType'                     => true,
		'placeholder'                   => true,
		'dataType'                      => array(
			'values' => array(
				'post_attribute' => true,
				'taxonomy'       => true,
			),
		),
		'taxonomyHierarchical'          => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderBy'               => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyOrderDir'              => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTermsConditions'       => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'taxonomyTerms'                 => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputOptionsOrder'             => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '!=',
					'value'   => 'post_attribute',
				),
			),
		),
		'hideEmpty'                     => array(
			'conditions' => array(
				array(
					'option'  => 'dataType',
					'compare' => '=',
					'value'   => 'taxonomy',
				),
			),
		),
		'inputShowIcon'                 => true,
		'autoSubmit'                    => true,
		'autoSubmitDelay'               => true,
		'autoSubmitOnType'              => true,
		'labelInitialVisibility'        => true,
		'labelToggleVisibility'         => true,
		'inputNoResultsText'            => true,
		'inputSingularResultsCountText' => true,
		'inputPluralResultsCountText'   => true,
		'inputLoadingText'              => true,
		// 'inputSuggestionsSearchPattern' => true,
		'inputOptionsOrderDir'          => true,

		'dataUrlName'                   => true,
		'dataCustomField'               => true,
		'dataCustomFieldIndexerNotice'  => true,

		'defaultValueType'              => true,
		'defaultValueInheritArchive'    => true,
		'defaultValueInheritSearch'     => true,
		'defaultValueInheritPost'       => true,
		'defaultValueCustom'            => true,
		'defaultValueApplyToQuery'      => true,
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
		$url_name = 's';
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );

		return $url_name;
	}

	/**
	 * Parses a value from the URL.
	 *
	 * @since    3.0.0
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST
		// but with wp_unslash already applied.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = sanitize_text_field( $request_var ?? '' );

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}

	/**
	 * Get the autocomplete suggestions for the field.
	 *
	 * Supports both legacy single dataType format and new dataSources array format.
	 * Both formats are normalized to an array and processed through the same code path.
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

		// Get dataSources - or convert legacy single dataType to array format.
		$data_sources = $this->get_data_sources_for_suggestions();

		if ( empty( $data_sources ) ) {
			return array();
		}

		return $this->get_datasources_suggestions( $search, $data_sources );
	}

	/**
	 * Get data sources for suggestions, converting legacy format if needed.
	 *
	 * @since 3.1.0
	 *
	 * @return array Data sources array.
	 */
	protected function get_data_sources_for_suggestions() {
		// Check for new dataSources array format first.
		$data_sources = $this->get_attribute( 'dataSources' );
		if ( ! empty( $data_sources ) && is_array( $data_sources ) ) {
			return $data_sources;
		}

		// Legacy: convert single dataType to array format.
		$data_type = $this->get_attribute( 'dataType' );
		if ( empty( $data_type ) ) {
			return array();
		}

		// Build single-item array matching new format.
		$source = array( 'dataType' => $data_type );

		if ( $data_type === 'post_attribute' ) {
			$post_attribute              = $this->get_attribute( 'dataPostAttribute' );
			$source['dataPostAttribute'] = ! empty( $post_attribute ) ? $post_attribute : 'default';
		} elseif ( $data_type === 'taxonomy' ) {
			$source['dataTaxonomy'] = $this->get_attribute( 'dataTaxonomy' );
		} elseif ( $data_type === 'custom_field' ) {
			$source['dataCustomField'] = $this->get_attribute( 'dataCustomField' );
		}

		return array( $source );
	}

	/**
	 * Get suggestions from multiple data sources.
	 *
	 * @since 3.1.0
	 *
	 * @param string $search       The search term.
	 * @param array  $data_sources The data sources to query.
	 * @return array Suggestions sorted by relevance.
	 */
	protected function get_datasources_suggestions( $search, $data_sources ) {
		$suggestions = array();

		foreach ( $data_sources as $source ) {
			// Skip sources explicitly marked as not for suggestions.
			if ( isset( $source['useForSuggestions'] ) && $source['useForSuggestions'] === false ) {
				continue;
			}

			$source_suggestions = $this->get_suggestions_for_source( $source, $search );
			$suggestions        = array_merge( $suggestions, $source_suggestions );
		}

		// Dedupe.
		$suggestions = array_unique( $suggestions );

		// Sort by relevance (prefix matches first, then by length).
		$suggestions = $this->sort_suggestions_by_relevance( $suggestions, $search );

		// Limit.
		return array_slice( $suggestions, 0, 10 );
	}

	/**
	 * Get suggestions for a single data source using existing methods.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $source The data source config.
	 * @param string $search The search term.
	 * @return array Suggestions from this source.
	 */
	protected function get_suggestions_for_source( $source, $search ) {
		$data_type = $source['dataType'] ?? '';

		switch ( $data_type ) {
			case 'post_attribute':
				$attribute = $source['dataPostAttribute'] ?? 'default';
				if ( $attribute === 'default' || $attribute === 'post_title' || $attribute === '' ) {
					return $this->search_post_titles( $search );
				} elseif ( $attribute === 'post_type' ) {
					return $this->search_post_type_labels( $search, 'label' );
				} elseif ( $attribute === 'post_status' ) {
					return $this->search_post_stati_labels( $search, 'label' );
				}
				// Skip post_content, post_excerpt (too long for suggestions).
				return array();

			case 'taxonomy':
				$taxonomy = $source['dataTaxonomy'] ?? '';
				if ( empty( $taxonomy ) ) {
					return array();
				}
				return $this->search_taxonomy_term_labels( $search, $taxonomy, 'name' );

			case 'custom_field':
				$field_key = $source['dataCustomField'] ?? '';
				if ( empty( $field_key ) ) {
					return array();
				}
				return $this->search_custom_fields( $search, $field_key );

			default:
				return array();
		}
	}

	/**
	 * Sort suggestions by relevance to search term.
	 *
	 * Priority: prefix matches first, then shorter strings.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $suggestions The suggestions to sort.
	 * @param string $search      The search term.
	 * @return array Sorted suggestions.
	 */
	protected function sort_suggestions_by_relevance( $suggestions, $search ) {
		$search_lower = strtolower( $search );

		usort(
			$suggestions,
			function ( $a, $b ) use ( $search_lower ) {
				$a_lower = strtolower( $a );
				$b_lower = strtolower( $b );

				// Check if prefix match.
				$a_is_prefix = strpos( $a_lower, $search_lower ) === 0;
				$b_is_prefix = strpos( $b_lower, $search_lower ) === 0;

				// Prefix matches come first.
				if ( $a_is_prefix && ! $b_is_prefix ) {
					return -1;
				}
				if ( $b_is_prefix && ! $a_is_prefix ) {
					return 1;
				}

				// Within same category, shorter strings first.
				return strlen( $a ) - strlen( $b );
			}
		);

		return $suggestions;
	}

	/**
	 * Registers any settings/apis etc that need to exist
	 * permanently for this field.
	 *
	 * @since    3.0.0
	 */
	public static function register() {
		if ( self::$has_registered ) {
			return;
		}
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'search-filter/frontend/data', array( __CLASS__, 'add_suggestions_nonce' ) );

		self::$has_registered = true;
	}

	/**
	 * Registers the REST API routes for the field.
	 *
	 * @since    3.0.0
	 */
	public static function routes() {
		// TODO - remove this from our storybook routes that connect to the test API endpoint.
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

				/*
				 * For admin previews where we need to send a lot of args (and response time
				 * is not as important ) use POST.
				 */
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

		$use_nonce = apply_filters( 'search-filter/fields/search/autocomplete/use_nonce', false );
		if ( ! $use_nonce ) {
			return $data;
		}

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

		// This needs to be optional as caching plugins & CDNs can cache nonces causing them to be invalid.
		$use_nonce = apply_filters( 'search-filter/fields/search/autocomplete/use_nonce', false );
		if ( $use_nonce && ! wp_verify_nonce( $nonce, self::SUGGESTIONS_NONCE ) ) {
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
