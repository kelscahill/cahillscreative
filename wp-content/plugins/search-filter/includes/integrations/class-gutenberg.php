<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */

namespace Search_Filter\Integrations;

use Search_Filter\Admin\Screens;
use Search_Filter\Components;
use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\Icons;
use Search_Filter\Core\Scripts;
use Search_Filter\Core\SVG_Loader;
use Search_Filter\Features;
use Search_Filter\Features\Shortcodes\Shortcode_Parser;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Integrations;
use Search_Filter\Integrations\Gutenberg\Block_Parser;
use Search_Filter\Integrations\Gutenberg\Cron;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Integrations\Gutenberg\Legacy_Blocks;
use Search_Filter\Styles;
use Search_Filter\Queries\Settings as Queries_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Gutenberg integration functionality
 */
class Gutenberg {

	/**
	 * Track if a query is running (so we can attach our params the WP_Query)
	 *
	 * @since    3.0.0
	 *
	 * @var bool
	 */
	private static $is_tracking_query = false;
	/**
	 * Collected data from the current query block.  We need to pass data from the
	 * parent query block (ID) into the pre_get_posts hook as well as the child
	 * post-template block.
	 *
	 * @since    3.0.0
	 *
	 * @var array
	 */
	private static $current_query_data = array(
		'connected_queries' => array(),
	);

	/**
	 * A collection of the queried fields that are use in the current post.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $post_fields = array();
	/**
	 * A store of which post IDs are linked to which queries.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $post_queries = array();

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 1 );

		Legacy_Blocks::init();
	}

	/**
	 * Setup the integration.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {
		if ( ! Integrations::is_enabled( 'blockeditor' ) ) {
			return;
		}

		// Init the cron tasks.
		Cron::init();

		/**
		 * Admin facing.
		 */
		// Needs a low priority to get added before editor_script. TODO.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ), 1 );

		add_action( 'init', array( __CLASS__, 'register_blocks' ), 20 );

		add_filter( 'block_editor_rest_api_preload_paths', array( __CLASS__, 'get_preload_api_paths' ), 10, 2 );

		// Update fields when blocks are updated.
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 20, 2 );

		add_action( 'rest_after_save_widget', array( __CLASS__, 'save_widget' ), 20, 3 );
		add_action( 'rest_delete_widget', array( __CLASS__, 'delete_widget' ), 20, 1 );

		// Add custom category to block inserter.
		add_filter( 'block_categories_all', array( __CLASS__, 'block_categories' ), 10 );

		// TODO - need to handle FSE and template parts (in edit-post), eg cover the contexts - edit-post, edit-widgets, edit-site.

		/**
		 * Frontend facing.
		 */

		// Add support for filtering the Query Loop block.
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_query_block' ), 10, 2 );
		// Modify the rest request to see the query with our integration.
		add_filter( 'rest_post_query', array( __CLASS__, 'update_rest_post_query' ), 10, 2 );
		// Cleanup the connected query IDs after render.  Priority is important, hooks will usually use a priority of `10`
		// so we need to be higher in case extensions (ie, the pro plugin) need access to the connected query IDs.
		add_filter( 'render_block', array( __CLASS__, 'render_query_block' ), 11, 2 );
		add_filter( 'render_block_data', array( __CLASS__, 'render_query_block_data' ), 100, 2 );
		add_filter( 'render_block_context', array( __CLASS__, 'render_query_block_context' ), 100, 1 );

		add_action( 'search-filter/features/dynamic-assets/preload_assets', array( __CLASS__, 'preload_assets' ), 10, 1 );

		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 2, 2 );

		self::register_settings();
	}

	/**
	 * Update the query attributes.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $attributes    The attributes to update.
	 * @param object $query         The query object.
	 * @return array    The updated attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {

		$id = $query->get_id();

		// Set the `queryContainer` automatically for adding our a11y labels and props.
		if ( ! isset( $attributes['integrationType'] ) ) {
			return $attributes;
		}

		if ( ! isset( $attributes['queryIntegration'] ) ) {
			return $attributes;
		}
		$query_integration = $attributes['queryIntegration'];

		if ( $query_integration === 'query_block' ) {
			$attributes['queryContainer'] = '.search-filter-query--id-' . $id;
		}

		return $attributes;
	}

	/**
	 * Add conditions to the queryContainer setting so its hidden when using the query loop block
	 *
	 * @since 3.2.0
	 */
	public static function register_settings() {

		$depends_conditions = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'option'  => 'queryIntegration',
					'compare' => '!=',
					'value'   => 'query_block',
				),
			),
		);

		// Get the object for the data_type setting so we can grab its options.
		$query_container = Queries_Settings::get_setting( 'queryContainer' );
		if ( $query_container ) {
			$query_container->add_depends_condition( $depends_conditions );
		}
	}

	/**
	 * Add custom category to block inserter.
	 *
	 * @since 3.0.0
	 *
	 * @param array $categories Array of block categories.
	 * @return array
	 */
	public static function block_categories( $categories ) {
		$categories[] = array(
			'slug'  => 'search-filter',
			'title' => __( 'Search & Filter', 'search-filter' ),
		);
		return $categories;
	}

	/**
	 * Add editor input type data to the block editor.
	 *
	 * Builds and outputs JavaScript data for the block editor about available
	 * input types, including whether they require Pro features.
	 *
	 * @since 3.0.0
	 */
	public static function add_editor_input_type_data() {
		$input_type_matrix = Field_Factory::get_field_input_types();
		$input_type_data   = array();
		foreach ( $input_type_matrix as $field_type => $input_types ) {

			$input_type_data[ $field_type ] = array();

			foreach ( $input_types as $input_type => $input_type_class ) {
				$input_type_data[ $field_type ][ $input_type ] = array(
					// 'label'       => $input_type_class::get_label(),
					'requiresPro' => $input_type_class::requires_pro(),
				);
			}
		}

		$data   = array(
			'inputTypeData' => $input_type_data,
			'isProEnabled'  => \Search_Filter\Core\Dependants::is_search_filter_pro_enabled(),
			'popoverNode'   => Features::get_setting_value( 'compatibility', 'popoverNode' ),
			'adminUrl'      => admin_url( 'admin.php?page=search-filter' ),
		);
		$script = '
		if ( ! window.searchAndFilter.admin ) {
			window.searchAndFilter.admin = {};
		}
		window.searchAndFilter.admin.blockEditor = ' . wp_json_encode( $data ) . ';';
		wp_add_inline_script( 'search-filter-gutenberg', $script, 'before' );
	}

	/**
	 * Register the assets for the Gutenberg editor.
	 *
	 * @since 3.2.0
	 */
	private static function register_assets() {

		$component_handles = Components::get_assets_handles();

		$asset_configs = array(
			array(
				'name'   => 'search-filter-gutenberg',
				'script' => array(
					'src'          => SEARCH_FILTER_URL . 'assets/admin/block-editor.js',
					'asset_path'   => SEARCH_FILTER_PATH . 'assets/admin/block-editor.asset.php',
					'dependencies' => array_merge( array( 'search-filter-frontend' ), $component_handles['scripts'] ), // Additional dependencies.
				),
				'style'  => array(
					'src'          => SEARCH_FILTER_URL . 'assets/admin/block-editor.css',
					'dependencies' => array_merge( array( 'wp-components', 'search-filter-frontend' ), $component_handles['styles'] ),
				),
			),
		);

		$asset_configs = apply_filters( 'search-filter/integrations/gutenberg/register_assets/configs', $asset_configs );

		$assets = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );

		do_action( 'search-filter/integrations/gutenberg/register_assets' );
	}

	/**
	 * Get the asset handle for Gutenberg integration.
	 *
	 * @since 3.0.0
	 *
	 * @return string The asset handle.
	 */
	public static function get_asset_handle() {
		return apply_filters( 'search-filter/integrations/gutenberg/asset_handle', 'search-filter-gutenberg' );
	}


	/**
	 * Register the blocks for the gutenberg editor.
	 *
	 * @since    3.0.0
	 */
	public static function register_blocks() {
		// If we're on one of our admin screens, then we don't need to load our own blocks.
		// Setup is already done via class-admin.php.
		if ( Screens::is_search_filter_screen() ) {
			return;
		}

		// Register the frontend assets.
		\Search_Filter\Frontend::register_assets();
		// Load all registered components assets.
		\Search_Filter\Components::register_assets();

		// Register our gutenberg scripts & styles.
		self::register_assets();

		// Add global variables that need to be available before the
		// block editor JS is initialised.
		self::add_editor_input_type_data();

		// Register block dynamically from the field factory.
		$input_type_matrix = Field_Factory::get_field_input_types();

		$blocks_dir   = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'gutenberg' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR;
		$asset_handle = self::get_asset_handle();

		// Important: if we don't register this block via PHP, in some setups, it
		// won't show up in the block inserter.
		register_block_type(
			$blocks_dir . 'load-field' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
			)
		);

		foreach ( $input_type_matrix as $field_type => $input_types ) {

			foreach ( $input_types as $input_type => $input_type_class ) {
				register_block_type(
					$blocks_dir . $field_type . DIRECTORY_SEPARATOR . $input_type . DIRECTORY_SEPARATOR,
					array(
						'editor_script'        => $asset_handle,
						'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
						'render_callback'      => array( __CLASS__, 'render_field' ),
					)
				);
			}
		}
	}

	/**
	 * Adds our commonly used (required on init) rest api paths for blocks
	 *
	 * @since 3.0.0
	 *
	 * @param (string|string[])[]      $preload_paths  Existing api paths.
	 * @param \WP_Block_Editor_Context $block_editor_context The block editor context.
	 * @return (string|string[])[]
	 */
	public static function get_preload_api_paths( $preload_paths, \WP_Block_Editor_Context $block_editor_context ) {

		if ( Screens::is_search_filter_screen() ) {
			// If we're on one of our admin screens, then we don't need to add this to the preload.
			return $preload_paths;
		}

		$default_style_id = Styles::get_default_styles_id();
		$preload_paths[]  = '/search-filter/v1/queries';
		$preload_paths[]  = '/search-filter/v1/admin/field-input-types';
		$preload_paths[]  = '/search-filter/v1/admin/styles/defaults/preset';
		$preload_paths[]  = '/search-filter/v1/admin/styles/tokens';
		$preload_paths[]  = '/search-filter/v1/records/styles/' . $default_style_id;
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=queries';
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=fields';
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=styles';

		// TODO - parse the page and look for our field ID, then preload those records, the select style + query records too.
		//
		if ( $block_editor_context->name === 'core/edit-post' ) {
			$post_content = $block_editor_context->post->post_content;
			$post_fields  = Block_Parser::extract_fields( $post_content );

			foreach ( $post_fields as $post_field ) {
				if ( is_wp_error( $post_field ) ) {
					continue;
				}
				$field_attributes = $post_field->get_attributes();
				if ( isset( $field_attributes['styleId'] ) ) {
					$style_path = '/search-filter/v1/records/styles/' . $field_attributes['styleId'];
					if ( ! in_array( $style_path, $preload_paths, true ) ) {
						$preload_paths[] = $style_path;
					}
				}

				if ( isset( $field_attributes['queryId'] ) ) {
					$query_path = '/search-filter/v1/records/queries/' . $field_attributes['queryId'] . '?context=edit';
					if ( ! in_array( $query_path, $preload_paths, true ) ) {
						$preload_paths[] = $query_path;
					}
				}

				if ( isset( $field_attributes['fieldId'] ) ) {
					$field_path = '/search-filter/v1/records/fields/' . $field_attributes['fieldId'] . '?context=edit';
					if ( ! in_array( $field_path, $preload_paths, true ) ) {
						$preload_paths[] = $field_path;
					}
				}
			}
		}

		$first_query_id = Queries::get_queries_list_first_id();
		if ( $first_query_id > 0 ) {
			$preload_paths[] = '/search-filter/v1/records/queries/' . $first_query_id . '?context=edit';
		}

		global $pagenow;
		global $post_id;

		$context_path = '';
		if ( $pagenow === 'widgets.php' ) {
			$context_path = 'widgets';
		} elseif ( isset( $post_id ) ) {
			$context_path = 'post/' . $post_id;
		}
		// Preload context field IDs so we can track copy + pasted fields from other contexts.
		$preload_paths[] = '/search-filter/v1/fields/context/ids?context=block-editor&context_path=' . $context_path;
		$preload_paths   = apply_filters( 'search-filter/integrations/gutenberg/get_preload_api_paths', $preload_paths );

		return $preload_paths;
	}

	/**
	 * Parse the post content and preload field assets.
	 *
	 * @param string $post_content The post content.
	 * @return void
	 */
	public static function preload_assets( $post_content ) {
		// TODO - parse the page and look for our field ID, then preload those records (✔️), plus the selected style + query records (❌).
		$field_blocks = Block_Parser::extract_fields( $post_content );

		foreach ( $field_blocks as $field_block ) {
			if ( is_wp_error( $field_block ) ) {
				continue;
			}
			$field_block->enqueue_assets();
		}
	}
	/**
	 * Display inline footer scripts and SVGs
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function footer_scripts() {
		SVG_Loader::output();
	}
	/**
	 * Renders a fields html using block attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $block_attributes  The saved block attributes.
	 * @param string $content           The block content.
	 * @param object $block             The block object.
	 * @return string
	 */
	public static function render_field( $block_attributes, $content = '', $block = null ) {
		$args = array();
		$args = wp_parse_args( $args, $block_attributes );

		if ( ! isset( $block_attributes['fieldId'] ) ) {
			return '';
		}
		ob_start();
		$field = Field::get_instance( absint( $block_attributes['fieldId'] ) );
		if ( is_wp_error( $field ) ) {
			echo esc_html( $field->get_error_message() );
			return ob_get_clean();
		}

		$wrapper_attributes = \WP_Block_Supports::get_instance()->apply_block_supports();

		// We can have `style`, `class`, `id`, or `aria-label` in the wrapper attributes.
		foreach ( $wrapper_attributes as $attribute_name => $attribute_value ) {
			if ( $attribute_name === 'class' ) {
				$field->add_html_class( $attribute_value, 'before' );
			} else {
				$field->add_html_attribute( $attribute_name, $attribute_value );
			}
		}

		// Add legacy CSS classes for backwards compatibility when on old assets version.
		if ( Asset_Loader::get_db_version() === 1 ) {
			$legacy_class = self::get_legacy_class_for_block( $block );
			if ( $legacy_class ) {
				$field->add_html_class( $legacy_class );
			}
		}

		$field->render();
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get legacy CSS class name for a new block.
	 *
	 * Maps new block names back to their legacy equivalents for backwards compatibility.
	 *
	 * @param \WP_Block|null $block The block object.
	 * @return string|null Legacy CSS class name, or null if not applicable.
	 */
	private static function get_legacy_class_for_block( $block ) {
		if ( ! $block ) {
			return null;
		}

		$block_name = $block->name;

		// Map new block names to legacy CSS classes.
		if ( strpos( $block_name, 'search-filter/search-' ) === 0 ) {
			return 'wp-block-search-filter-search';
		} elseif ( strpos( $block_name, 'search-filter/choice-' ) === 0 ) {
			return 'wp-block-search-filter-choice';
		} elseif ( strpos( $block_name, 'search-filter/range-' ) === 0 ) {
			return 'wp-block-search-filter-range';
		} elseif ( strpos( $block_name, 'search-filter/advanced-' ) === 0 ) {
			return 'wp-block-search-filter-advanced';
		} elseif ( strpos( $block_name, 'search-filter/control-' ) === 0 ) {
			return 'wp-block-search-filter-control';
		}

		return null;
	}

	/**
	 * Renders a reusable field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_reusable_field( $block_attributes ) {
		$field_id = absint( $block_attributes['fieldId'] );
		$field    = Field::get_instance( $field_id );

		$display_errors = is_user_logged_in() && current_user_can( 'manage_options' );

		if ( is_wp_error( $field ) ) {
			if ( $display_errors ) {
				return '<em>' . __( 'Field not found', 'search-filter' ) . '</em>';
			}
			return '';
		}

		if ( $field->get_status() !== 'enabled' ) {
			if ( $display_errors ) {
				return '<em>' . __( 'Field not enabled', 'search-filter' ) . '</em>';
			}
			return '';
		}

		$wrapper_attributes = \WP_Block_Supports::get_instance()->apply_block_supports();

		// We can have `style`, `class`, `id`, or `aria-label` in the wrapper attributes.
		foreach ( $wrapper_attributes as $attribute_name => $attribute_value ) {
			if ( $attribute_name === 'class' ) {
				$field->add_html_class( $attribute_value, 'before' );
			} else {
				$field->add_html_attribute( $attribute_name, $attribute_value );
			}
		}

		return $field->render( true );
	}

	/**
	 * Load assets required for the block editor.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function editor_assets() {
		if ( Screens::is_search_filter_screen() ) {
			// For some reason, using some FSE / block editor themes, the `enqueue_block_editor_assets`
			// hook is called on our screens aside from when we run it.
			// Return early as we don't want to load our block editor specific assets.
			return;
		}

		Icons::load();

		// Add our inline JS and SVGs.
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'footer_scripts' ), 1 );

		// Preload settings data for settings.  It's important to only load the queries list + styles list
		// to reduce the number of api requests when fields initially resolve.
		Scripts::preload_api_requests(
			array(
				'/search-filter/v1/settings/options/queries',
				'/search-filter/v1/settings/options/styles',
			),
			'search-filter-gutenberg'
		);
	}

	/**
	 * Get the post fields records.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array
	 */
	public static function get_post_field_records( $post_id ) {
		$query_args                  = array();
		$query_args['context']       = 'block-editor';
		$query_args['context_path']  = 'post/' . $post_id;
		$block_editor_fields_records = Fields::find( $query_args, 'records' );
		return $block_editor_fields_records;
	}

	/**
	 * Detects when our blocks are saved and stores the settings in our fields table.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $post_id The post ID.
	 * @param object $post The post object.
	 */
	public static function save_post( $post_id, $post ) {

		if ( wp_is_post_revision( $post ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post ) ) {
			return;
		}

		// Edge case - sometimes a post can be NULL and still trigger `save_post`.
		if ( ! $post ) {
			return;
		}

		// Edge case - sometimes a post's content can be NULL.
		if ( ! $post->post_content ) {
			return;
		}

		$fields          = Block_Parser::extract_fields( $post->post_content );
		$found_field_ids = array();

		foreach ( $fields as $field ) {
			$found_field_ids[] = $field->get_id();

			// Ensure we add the location to the field.
			$field->add_location( 'post/' . $post_id );
		}

		$existing_fields_for_location = Fields::find_fields_by_location( 'post/' . $post_id );

		// We need to remove any fields that are no longer in the content.
		foreach ( $existing_fields_for_location as $existing_field ) {
			if ( in_array( $existing_field->get_id(), $found_field_ids, true ) ) {
				continue;
			}
			$existing_field->remove_location( 'post/' . $post_id );
		}
	}

	/**
	 * On save widget, update related fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $id The widget ID.
	 * @param int    $existing_sidebar_id The existing sidebar ID.
	 * @param object $request The request object.
	 */
	public static function save_widget( $id, $existing_sidebar_id, $request ) {
		if ( ! isset( $request['instance'] ) ) {
			return;
		}
		if ( ! isset( $request['instance']['raw'] ) ) {
			return;
		}
		if ( ! isset( $request['instance']['raw']['content'] ) ) {
			return;
		}

		$widget_content = $request['instance']['raw']['content'];

		// Ensure content has blocks.
		if ( ! has_blocks( $widget_content ) ) {
			return;
		}

		$fields = Block_Parser::extract_fields( $widget_content );

		foreach ( $fields as $field ) {
			$field->add_location( 'widget/' . $id );
		}
	}

	/**
	 * On delete widget, delete related fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $widget_id The widget ID.
	 */
	public static function delete_widget( $widget_id ) {
		Fields::remove_fields_from_location( 'widget/' . $widget_id );
	}

	/**
	 * Get the post content fields.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $post_id The post ID.
	 *
	 * @return mixed The post content fields.
	 */
	public static function get_post_content_fields( $post_id ) {
		// If we've already looked up the fields, then just return them.
		if ( isset( self::$post_fields[ $post_id ] ) ) {
			return self::$post_fields[ $post_id ];
		}

		$post = get_post( $post_id );

		if ( $post === null ) {
			return array();
		}

		$content = $post->post_content;
		// Extract the fields on the page.
		$post_blocks = Block_Parser::extract_fields( $content );
		$fields      = array();

		foreach ( $post_blocks as $post_block ) {
			$fields[] = $post_block;
		}

		// Check for any shortcodes in the block editor content.
		if ( Features::is_enabled( 'shortcodes' ) ) {
			$post_shortcodes = Shortcode_Parser::extract_fields( $content );
			foreach ( $post_shortcodes as $post_shortcode ) {
				$fields[] = $post_shortcode;
			}
		}

		self::$post_fields[ $post_id ] = $fields;
		return self::$post_fields[ $post_id ];
	}

	/**
	 * Get the post queries.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array
	 */
	private static function get_post_queries( $post_id ) {
		// If we've already looked up the fields, then just return them.
		if ( isset( self::$post_queries[ $post_id ] ) ) {
			return self::$post_queries[ $post_id ];
		}

		$post_queries = Queries::find(
			array(
				'integration' => 'single/' . $post_id,
			)
		);

		self::$post_queries[ $post_id ] = $post_queries;
		return self::$post_queries[ $post_id ];
	}

	/**
	 * Find a query block that should be filtered
	 *
	 * @since 3.0.0
	 *
	 * @param string $pre_render The pre rendered HTML.
	 * @param array  $block      The block config.
	 * @return string
	 */
	public static function pre_render_query_block( $pre_render, $block ) {

		if ( $block['blockName'] !== 'core/query' ) {
			return $pre_render;
		}
		if ( isset( $block['attrs']['namespace'] ) && $block['attrs']['namespace'] !== '' ) {
			return $pre_render;
		}

		self::try_connect_to_query_loop( $block );
		return $pre_render;
	}

	/**
	 * Wire up the S&F query to the query block preview query.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $args    The WP_Query args.
	 * @param mixed $request The REST request.
	 * @return mixed
	 */
	public static function update_rest_post_query( $args, $request ) {
		if ( isset( $request['searchFilterQueryId'] ) && ! empty( $request['searchFilterQueryId'] ) ) {
			// Modify $args (WP_Query args) to add our query ID.
			$args['search_filter_query_id'] = absint( $request['searchFilterQueryId'] );
		}
		return $args;
	}

	/**
	 * Filters the query block data so we can a posts per page setting.
	 *
	 * It seems using pre_get_posts doesn't on its own doesn't have the desired effect.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_data The block data.
	 * @param array $block      The block config.
	 * @return array
	 */
	public static function render_query_block_data( $block_data, $block ) {
		$block_type = $block['blockName'];

		if ( $block_type !== 'core/query' ) {
			return $block_data;
		}

		// For some reason, only setting posts per page in our plugin doesn't work.
		// Pagination is updated, but some of the pages end up empty / without results.
		// To fix this, we just need to override the posts per page block attribute.
		$connected_query_ids = self::get_active_query_ids();
		if ( count( $connected_query_ids ) === 0 ) {
			return $block_data;
		}

		// Update the block attributes not to use the global query.
		$block_data['attrs']['query']['inherit'] = false;

		$query_id = $connected_query_ids[0];
		$query    = Query::get_instance( absint( $query_id ) );

		if ( is_wp_error( $query ) ) {
			return $block_data;
		}

		$posts_per_page = $query->get_query_posts_per_page();

		if ( empty( $posts_per_page ) ) {
			return $block_data;
		}

		$block_data['attrs']['query']['perPage'] = (string) $posts_per_page;
		return $block_data;
	}

	/**
	 * Filters the query block data so we can a posts per page setting.
	 *
	 * It seems using pre_get_posts doesn't on its own doesn't have the desired effect.
	 *
	 * @since 3.0.0
	 *
	 * @param array $context The block context data.
	 * @return array
	 */
	public static function render_query_block_context( $context ) {
		// Target any block that receives query context from a parent Query block.
		if ( ! isset( $context['query'] ) ) {
			return $context;
		}

		// Make sure we're in a connected S&F query.
		$connected_query_ids = self::get_active_query_ids();
		if ( count( $connected_query_ids ) === 0 ) {
			return $context;
		}

		// Disable the inherit flag so the post template runs a normal query.
		if ( isset( $context['query']['inherit'] ) ) {
			$context['query']['inherit'] = false;
		}

		return $context;
	}

	/**
	 * Try to connect a query block to our query.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $block The block config.
	 * @param string $query_integration_name The name of the integration.
	 * @param string $block_name The name of the block.
	 */
	public static function try_connect_to_query_loop( $block, $query_integration_name = 'query_block', $block_name = 'core/query' ) {
		if ( $block['blockName'] !== $block_name ) {
			return;
		}

		// Return early if we're already tracking a query (don't allow query blocks inside of query blocks).
		if ( self::$is_tracking_query ) {
			return;
		}

		$query_search_filter_id = isset( $block['attrs']['searchFilterQueryId'] ) ? absint( $block['attrs']['searchFilterQueryId'] ) : 0;
		if ( $query_search_filter_id !== 0 ) {
			// Then we can connect this query to the query block.
			$query = Query::get_instance( absint( $query_search_filter_id ) );
			if ( ! is_wp_error( $query ) ) {
				$pagination_key = isset( $block['attrs']['queryId'] ) ? 'query-' . $block['attrs']['queryId'] . '-page' : 'query-page';
				$query->set_render_config_value( 'paginationKey', $pagination_key );
				self::$current_query_data = array(
					'connected_queries' => array( $query ),
				);
				self::$is_tracking_query  = true;
				self::attach_query_vars_filter();
				return;
			}
		}

		if ( ! is_singular() ) {
			// This method only supports query loops on singular pages
			// We have a different implementation for accessing the archives.
			return;
		}
		// Get current post ID.
		$post_id = get_queried_object_id();

		$post_queries   = self::get_post_queries( $post_id );
		$post_query_ids = array_map(
			function ( $query ) {
				return $query->get_id();
			},
			$post_queries
		);
		$post_fields    = self::get_post_content_fields( $post_id ); // Get the fields embedded in the post.

		if ( count( $post_query_ids ) === 0 && count( $post_fields ) === 0 ) {
			return;
		}
		self::$is_tracking_query = true;
		$attributes              = $block['attrs'];
		$query_search_filter_id  = isset( $attributes['searchFilterQueryId'] ) ? absint( $attributes['searchFilterQueryId'] ) : 0;

		// Lookup any fields on this post, so we can check if they should affect the query or not.
		// TODO - we should also lookup any queries (saved) that are connected to this post as we
		// can have admin queries + fields affecting query blocks.
		$connected_queries    = array();
		$field_post_query_ids = array();
		// Loop through the fields once and setup the data we need.
		foreach ( $post_fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			$field_attributes       = $field->get_attributes();
			$field_post_query_ids[] = absint( $field_attributes['queryId'] );
		}

		$related_query_ids = array_unique( array_merge( $post_query_ids, $field_post_query_ids ) );

		// Loop through the fields and check if they should affect the query.
		foreach ( $related_query_ids as $query_id ) {
			// If there are fields assigned to this post, but no longer connected to any blocks
			// then we need to delete them.
			$query = Query::get_instance( $query_id );

			if ( is_wp_error( $query ) ) {
				continue;
			}

			// Now we need to check, if the query is set to "single".
			$integration_type = $query->get_attribute( 'integrationType' );
			if ( $integration_type !== 'single' && $integration_type !== 'dynamic' ) {
				continue;
			}

			$is_single = $integration_type === 'single';
			// Stop if we're using single integration and the post IDs don't match.
			$single_location = $query->get_attribute( 'singleLocation' );
			if ( $is_single && ( absint( $single_location ) !== absint( $post_id ) ) ) {
				continue;
			}

			$query_integration = $query->get_attribute( 'queryIntegration' );
			if ( $query_integration !== $query_integration_name ) {
				continue;
			}

			$query_loop_autodetect = $query->get_attribute( 'queryLoopAutodetect' );

			/**
			 * Check if a field should affect the query.
			 *
			 * If a field doesn't have a query loop ID, then it will auto detect any on this post,
			 * so it should affect the loop.
			 *
			 * If field does have an ID, it needs to match the query loop ID in order to affect
			 * the loop.
			 */
			if ( $query_loop_autodetect === 'yes' ) {
				// Blocks have their own unique pagination key, so lets set that in the S&F query.
				$pagination_key = isset( $block['attrs']['queryId'] ) ? 'query-' . $block['attrs']['queryId'] . '-page' : 'query-page';
				$query->set_render_config_value( 'paginationKey', $pagination_key );
				$connected_queries[] = $query;
			}
		}

		self::$current_query_data = array(
			'connected_queries' => $connected_queries,
		);
		if ( count( $connected_queries ) > 0 ) {
			self::attach_query_vars_filter();
		}
	}
	/**
	 * Attach the query vars filter.
	 *
	 * @since 3.0.0
	 */
	private static function attach_query_vars_filter() {
		add_filter( 'query_loop_block_query_vars', array( __CLASS__, 'query_loop_block_query_vars' ), 20, 3 );
	}
	/**
	 * Detach the query vars filter.
	 *
	 * @since 3.0.0
	 */
	private static function detach_query_vars_filter() {
		remove_filter( 'query_loop_block_query_vars', array( __CLASS__, 'query_loop_block_query_vars' ), 20 );
	}
	/**
	 * Get the IDs of the active queries.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_active_query_ids() {
		$query_data = self::$current_query_data['connected_queries'];
		$query_ids  = array();
		foreach ( $query_data as $query ) {
			$query_ids[] = $query->get_id();
		}
		return $query_ids;
	}

	/**
	 * Unhook our pre_get_posts hook after the post template block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The full block, including name and attributes.
	 *
	 * @return string
	 */
	public static function render_query_block( $block_content, $block ) {
		if ( $block['blockName'] !== 'core/query' ) {
			return $block_content;
		}

		if ( ! self::$is_tracking_query ) {
			return $block_content;
		}

		self::cleanup_query_block( $block );
		return $block_content;
	}

	/**
	 * Cleanup the query block hooks and tracking data.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block The block data.
	 */
	public static function cleanup_query_block( $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Unused for now, probably needed later.
		if ( ! self::$is_tracking_query ) {
			return;
		}

		// Reset hooks and associated data.
		self::detach_query_vars_filter();

		self::$is_tracking_query  = false;
		self::$current_query_data = array(
			'connected_queries' => array(),
		);
	}

	/**
	 * Query loop block query vars.
	 *
	 * @since 3.0.0
	 *
	 * @param array     $query_vars The query vars.
	 * @param \WP_Block $block      The block config.
	 * @param int       $page       The page.
	 *
	 * @return array The updated query vars.
	 */
	public static function query_loop_block_query_vars( array $query_vars, \WP_Block $block, int $page ) {
		// Still not sure why exactly, but we need to set the posts_per_page and
		// override the offest that gets added by `query_loop_block_query_vars`.
		$connected_queries = self::$current_query_data['connected_queries'];

		$query_vars['search_filter_queries'] = $connected_queries;

		// TODO - there should only be one connected query, lets throw an error if we find more.
		foreach ( $connected_queries as $query ) {
			$offset       = 0;
			$per_page     = $query->get_query_posts_per_page();
			$query_offset = $query->get_attribute( 'offset' );

			if ( $query_offset !== null ) {
				// Use our own offset value if it exists (3.2.0 and higher).
				$offset = (int) $query_offset;
			} elseif (
				isset( $block->context['query']['offset'] ) &&
				is_numeric( $block->context['query']['offset'] )
			) {
				// Fallback to supporting offset from the Query Loop.
				$offset = absint( $block->context['query']['offset'] );
			}

			$query_vars['offset']         = ( $per_page * ( $page - 1 ) ) + $offset;
			$query_vars['posts_per_page'] = $query->get_query_posts_per_page();

			// For some reason, if the post type is  set to `post` our plugin doesn't
			// override it via the normal means, so lets manually set it in this hook.
			$query_vars['post_type'] = $query->get_attribute( 'postTypes' );
		}
		return $query_vars;
	}
}
