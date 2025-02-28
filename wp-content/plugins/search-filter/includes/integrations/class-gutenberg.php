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
use Search_Filter\Core\Exception;
use Search_Filter\Core\Icons;
use Search_Filter\Core\Scripts;
use Search_Filter\Core\SVG_Loader;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Integrations;
use Search_Filter\Integrations\Gutenberg\Block_Parser;
use Search_Filter\Integrations\Gutenberg\Cron;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\Gutenberg\Shortcode_Parser;
use Search_Filter\Styles;
use Search_Filter\Util;

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
	 * Block attributes that are added dynamically, ie by an extension plugin.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $extended_block_attributes = array();

	/**
	 * A backup of the global WP_Query object.
	 *
	 * @since 3.0.0
	 *
	 * @var \WP_Query
	 */
	private static $global_wp_query_backup = null;

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
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_extended_attributes' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_blocks' ), 20 );

		add_filter( 'block_editor_rest_api_preload_paths', array( __CLASS__, 'get_preload_api_paths' ), 10, 2 );
		add_filter( 'block_type_metadata', array( __CLASS__, 'block_type_metadata' ), 10, 1 );

		// Update fields when blocks are updated.
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 20, 2 );
		// When a post is deleted, remove entries from our tables.
		add_filter( 'delete_post', array( __CLASS__, 'delete_post' ), 10, 1 );

		add_action( 'rest_save_sidebar', array( __CLASS__, 'save_sidebar' ), 20 );
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
		add_filter( 'render_block', array( __CLASS__, 'render_query_block' ), 10, 2 );
		add_filter( 'render_block_data', array( __CLASS__, 'render_query_block_data' ), 100, 2 );
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
	 * Register the extended attributes.
	 *
	 * @since 3.0.0
	 */
	public static function register_extended_attributes() {
		$attributes = array(
			'search'         => array(),
			'choice'         => array(),
			'range'          => array(),
			'advanced'       => array(),
			'control'        => array(),
			'reusable-field' => array(),
		);

		$attributes = apply_filters( 'search-filter/integrations/gutenberg/add_attributes', $attributes );

		$extended_blocks = Fields_Settings::get_extended_blocks();
		foreach ( $extended_blocks as $block_type => $settings ) {
			foreach ( $settings as $setting ) {
				$attributes[ $block_type ][ $setting['name'] ] = array();
				if ( isset( $setting['type'] ) ) {
					$attributes[ $block_type ][ $setting['name'] ]['type'] = $setting['type'];
				}
				/*
				if ( isset( $setting['default'] ) ) {
					$attributes[ $block_type ][ $setting['name'] ]['default'] = $setting['default'];
				} */
			}
		}
		self::$extended_block_attributes = $attributes;
	}

	/**
	 * Add dynamic attributes the our blocks - useful when attributes (settings in our case) are added
	 * via PHP, and we need them to be added to our block registration.
	 *
	 * @since 3.0.0
	 */
	public static function add_dynamic_attributes() {
		return array( 'addAttributes' => self::$extended_block_attributes );
	}

	/**
	 * Register the stylesheets for the gutenberg editor.
	 *
	 * @since    3.0.0
	 */
	public static function register_blocks() {
		// TODO - I'm not sure instantiating the frontend class is the best way to do this.
		// I prefer to keep the frontend or script loader class as a singleton.
		// When on the frontend, we're actually doing this twice, once via the frontend init
		// and again here. Needs to be refactored.
		$plugin_frontend = new \Search_Filter\Frontend( SEARCH_FILTER_SLUG, SEARCH_FILTER_VERSION );
		$plugin_frontend->register_scripts();
		$plugin_frontend->add_js_data();
		// Instantiate the frontend so we can register the styles.
		$plugin_frontend->register_styles();

		// Setup the main Gutenberg script dependencies.
		// Load our plugins for the block editor.
		wp_register_script( 'search-filter-block-editor-plugins', Scripts::get_admin_assets_url() . 'js/admin/block-editor-plugins.js', array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'search-filter-gutenberg' ), SEARCH_FILTER_VERSION, false );

		$asset_file = SEARCH_FILTER_PATH . 'assets/js/admin/gutenberg.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset               = require $asset_file;
			$script_dependencies = array_merge( array( 'search-filter' ), $asset['dependencies'] );
			wp_register_script( 'search-filter-gutenberg', Scripts::get_admin_assets_url() . 'js/admin/gutenberg.js', $script_dependencies, $asset['version'], false );
		} else {
			Util::error_log( 'Block Editor script asset file not found: ' . $asset_file, 'error' );
		}

		$blocks_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'gutenberg' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR;

		register_block_type_from_metadata(
			$blocks_dir . 'search' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_search_field' ),
			)
		);
		register_block_type_from_metadata(
			$blocks_dir . 'choice' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_choice_field' ),
			)
		);
		register_block_type_from_metadata(
			$blocks_dir . 'range' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_range_field' ),
			)
		);
		register_block_type_from_metadata(
			$blocks_dir . 'advanced' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_advanced_field' ),
			)
		);
		register_block_type_from_metadata(
			$blocks_dir . 'control' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_control_field' ),
			)
		);
		register_block_type_from_metadata(
			$blocks_dir . 'reusable' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'   => 'search-filter-gutenberg',
				'style_handles'   => array( 'search-filter' ),
				'render_callback' => array( 'Search_Filter\\Integrations\\Gutenberg', 'render_reusable_field' ),
			)
		);
	}

	/**
	 * Filters a blocks metadata.
	 *
	 * We need to dynamically add the new attributes from any extensions in to the PHP registration of the block
	 * so that the the correct attributes and defaults are shown on the frontend.
	 *
	 * Specifically avoiding `register_block_type` (where we could dynamically create the attributes when we register
	 * the block) in favor of using `register_block_type_from_metadata` as it has better support in the .org
	 * repository, so we'll use this hook to add the additional attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $metadata The block metadata.
	 * @return array
	 */
	public static function block_type_metadata( $metadata ) {
		$search_filter_block_types = array(
			'search-filter/search'         => 'search',
			'search-filter/choice'         => 'choice',
			'search-filter/range'          => 'range',
			'search-filter/advanced'       => 'advanced',
			'search-filter/control'        => 'control',
			'search-filter/reusable-field' => 'reusable-field',
		);

		$search_filter_block_names = array_keys( $search_filter_block_types );

		if ( ! in_array( $metadata['name'], $search_filter_block_names, true ) ) {
			return $metadata;
		}

		if ( ! isset( $search_filter_block_types[ $metadata['name'] ] ) ) {
			return $metadata;
		}

		$block_type = $search_filter_block_types[ $metadata['name'] ];

		if ( ! isset( self::$extended_block_attributes[ $block_type ] ) ) {
			return $metadata;
		}

		$additional_attributes = self::$extended_block_attributes[ $block_type ];

		if ( ! isset( $metadata['attributes'] ) ) {
			$metadata['attributes'] = array();
		}
		$metadata['attributes'] = array_merge( $metadata['attributes'], $additional_attributes );
		return $metadata;
	}

	/**
	 * Adds our commonly used (required on init) rest api paths for blocks
	 *
	 * @since 3.0.0
	 *
	 * @param array $preload_paths  Existing api paths.
	 * @return array
	 */
	public static function get_preload_api_paths( $preload_paths, $block_editor_context ) {

		if ( Screens::is_search_filter_screen() ) {
			// If we're on one of our admin screens, then we don't need to add this to the preload.
			return;
		}

		$default_style_id = Styles::get_default_styles_id();
		$preload_paths[]  = '/search-filter/v1/queries';
		$preload_paths[]  = '/search-filter/v1/styles';
		$preload_paths[]  = '/search-filter/v1/admin/field-input-types';
		$preload_paths[]  = '/search-filter/v1/admin/styles/default';
		$preload_paths[]  = '/search-filter/v1/records/styles/' . $default_style_id;
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=queries';
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=fields';
		$preload_paths[]  = '/search-filter/v1/admin/settings?section=styles';

		// TODO - parse the page and look for our field ID, then preload those records, the select style + query records too.
		//
		if ( $block_editor_context->name === 'core/edit-post' ) {
			$post_content = $block_editor_context->post->post_content;
			$post_blocks  = Block_Parser::extract_blocks( $post_content );

			foreach ( $post_blocks as $post_block ) {
				if ( isset( $post_block['styleId'] ) ) {
					$style_path = '/search-filter/v1/records/styles/' . $post_block['styleId'];
					if ( ! in_array( $style_path, $preload_paths, true ) ) {
						$preload_paths[] = $style_path;
					}
				}

				if ( isset( $post_block['queryId'] ) ) {
					$query_path = '/search-filter/v1/records/queries/' . $post_block['queryId'] . '?context=edit';
					if ( ! in_array( $query_path, $preload_paths, true ) ) {
						$preload_paths[] = $query_path;
					}
				}

				if ( isset( $post_block['fieldId'] ) ) {
					$field_path = '/search-filter/v1/records/fields/' . $post_block['fieldId'] . '?context=edit';
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
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_field( $block_attributes ) {
		$args = array();
		$args = wp_parse_args( $args, $block_attributes );

		ob_start();
		try {
			$field = Field_Factory::create( $block_attributes );
		} catch ( \Exception $e ) {
			echo esc_html( $e->getMessage() );
		}
		if ( $field ) {
			$field->add_html_class( 'wp-block-search-filter-' . $block_attributes['type'], 'before' );
			$field->render();
		}
		$output = ob_get_clean();
		return $output;
	}
	/**
	 * Renders a search field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_search_field( $block_attributes ) {
		$block_attributes['type'] = 'search';
		if ( ! isset( $block_attributes['inputType'] ) ) {
			$block_attributes['inputType'] = 'search-text';
		}
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a choice field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_choice_field( $block_attributes ) {
		$block_attributes['type'] = 'choice';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a range field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_range_field( $block_attributes ) {
		$block_attributes['type'] = 'range';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a advanced field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_advanced_field( $block_attributes ) {
		$block_attributes['type'] = 'advanced';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a control field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_control_field( $block_attributes ) {
		$block_attributes['type'] = 'control';
		return self::render_field( $block_attributes );
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
		$field    = Field::find(
			array(
				'status' => 'enabled',
				'id'     => $field_id,
			)
		);
		if ( is_wp_error( $field ) ) {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				return '<em>' . __( 'Field not found', 'search-filter' ) . '</em>';
			}
			return '';
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
			// hook is called on our screens.
			// If we're on one of our admin screens, then we don't need to load the assets.
			return;
		}

		Icons::load();

		$css_file_ext = '.css';

		wp_enqueue_style( 'search-filter-gutenberg', Scripts::get_admin_assets_url() . 'css/admin/gutenberg' . $css_file_ext, array( 'wp-components' ), SEARCH_FILTER_VERSION, 'all' );
		wp_enqueue_script( 'search-filter-block-editor-plugins' );
		// Add our inline JS and SVGs.
		add_action( 'admin_print_footer_scripts', 'Search_Filter\\Integrations\\Gutenberg::footer_scripts', 1 );

		Scripts::attach_globals(
			'search-filter-gutenberg',
			'admin',
			self::add_dynamic_attributes()
		);

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
	 * Formats our settings into block attributes
	 *
	 * TODO - Currently not in use, but maybe we should take this approach again
	 *
	 * @since 3.0.0
	 *
	 * @param string $type The block type.
	 *
	 * @return array
	 */
	public static function get_block_attributes_json( $type ) {

		$fields_settings = Fields_Settings::get();

		$context    = 'block/field/' . $type;
		$attributes = array();
		// Loop through the settings object.
		foreach ( $fields_settings as $fields_setting ) {
			// Ensure that type + context are set.
			if ( isset( $fields_setting['type'] ) && isset( $fields_setting['context'] ) && is_array( $fields_setting['context'] ) ) {
				// if the setting has a matching context then add it to the block attributes.
				if ( in_array( $context, $fields_setting['context'], true ) ) {

					if ( $fields_setting['type'] !== 'slot' ) {
						$default   = isset( $fields_setting['default'] ) ? $fields_setting['default'] : '';
						$attribute = array(
							'default' => $default,
							'type'    => $fields_setting['type'],
						);
						if ( isset( $fields_setting['items'] ) ) {
							$attribute['items'] = $fields_setting['items'];
						}
						$attributes[ $fields_setting['name'] ] = $attribute;
					}
				}
			}
		}
		// Add fieldId attribute to the block.
		$attributes['fieldId']   = array(
			'default' => '',
			'type'    => 'string',
		);
		$attributes['alignment'] = array(
			'default' => '',
			'type'    => 'string',
		);
		return $attributes;
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

		$status = 'disabled';
		if ( $post->post_status === 'publish' ) {
			$status = 'enabled';
		}

		$updated_field_ids = array();
		if ( has_blocks( $post ) ) {
			// Check for any saved blocks and update their attributes.
			$updated_field_ids = self::update_fields_from_blocks_content( $post->post_content, $status );
		}

		// TODO - although not strictly necessary right now, we probably want to add IDs for fields that
		// don't have fieldId, usually this would be considered an error as one should be generated when
		// adding a post, but in the future it may be possible to create block editor posts server side,
		// and doing this would support that use case.

		// We also need to check our DB for any fields that are no longer in use and delete them.
		// TODO - check how this is working with the site editor updates.
		$post_field_records = self::get_post_field_records( $post_id );

		foreach ( $post_field_records as $item ) {
			// If there are fields assigned to this post, but no longer connected to any blocks
			// then we need to delete them.
			if ( ! in_array( $item->get_id(), $updated_field_ids, true ) ) {
				Field::destroy( $item->get_id() );
			}
		}
	}

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 * Taken from wp-includes/block-template-utils.phjp
	 *
	 * @since 3.0.0
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}

		while ( count( $queue ) > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}
		}

		return $all_blocks;
	}

	/**
	 * Update fields from blocks content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content from the post.
	 * @param string $status  The desired status to set.
	 *
	 * @return array The updated fields.
	 */
	public static function update_fields_from_blocks_content( $content, $status = '' ) {
		// Check for any saved blocks and update their attributes.
		$blocks = parse_blocks( $content );
		// TODO - maybe we should just parse this ourselves (the code for this is already on
		// github), and only look for our blocks.
		// Would surely be a faster regex as we don't care about the rest, or if blocks
		// are nested.

		$blocks = self::flatten_blocks( $blocks );
		// TODO - we should be able to extend these - so we can
		// include the range and advanced only in the pro plugin.
		$block_types_to_find = array(
			'search-filter/search',
			'search-filter/choice',
			'search-filter/range',
			'search-filter/advanced',
			'search-filter/control',
		);

		$block_types_to_attribute_type = array(
			'search-filter/search'   => 'search',
			'search-filter/choice'   => 'choice',
			'search-filter/range'    => 'range',
			'search-filter/advanced' => 'advanced',
			'search-filter/control'  => 'control',
		);
		// Track which fields are in use.
		$updated_field_ids = array();
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && in_array( $block['blockName'], $block_types_to_find, true ) ) {
				$attributes = $block['attrs'];
				// Update saved field attributes based on block attributes.
				if ( isset( $attributes['fieldId'] ) ) {
					$field_id = $attributes['fieldId'];

					// TODO - check if field_context is valid.
					$updated_field_ids[] = absint( $field_id );
					$field_record        = Field::find( array( 'id' => $field_id ), 'record' );
					if ( is_wp_error( $field_record ) ) {
						continue;
					}

					$field = Field::create_from_record( $field_record );
					if ( is_wp_error( $field ) ) {
						continue;
					}

					// Based on depends on conditions, get the settings for this field.
					// This will handle the issue of block attributes being missing when they're
					// set to the default value as well as popuplate anything thats missing.
					$args = array(
						'filters' => array(
							array(
								'type'  => 'context',
								'value' => 'admin/field/' . $field->get_attribute( 'type' ),
							),
						),
					);

					$attributes['type'] = $block_types_to_attribute_type[ $block['blockName'] ];

					$processed_settings = Fields_Settings::get_processed_settings( $attributes, $args );
					$new_attributes     = $processed_settings->get_attributes();

					$field->set_attributes( wp_parse_args( $attributes, $new_attributes ), true );

					if ( ! empty( $status ) ) {
						$field->set_status( $status );
					}
					$field->save();
				} else {
					Util::error_log( 'There is a block found in post content without a fieldId.', 'warning' );
				}
			}
		}

		return $updated_field_ids;
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

		// Check for any saved blocks and update their attributes.
		// Assume the existing sidebar ID is valid.
		$sidebar_id = $existing_sidebar_id;
		// If sidebar in request is empty, the widget changed sidebar.
		if ( ! empty( $request['sidebar'] ) ) {
			$sidebar_id = $request['sidebar'];
		}
		// Set any widgets in the inactive sidebar to disabled.
		$status = 'enabled';
		if ( $sidebar_id === 'wp_inactive_widgets' ) {
			$status = 'disabled';
		}

		$updated_field_ids = self::update_fields_from_blocks_content( $widget_content, $status );
		foreach ( $updated_field_ids as $field_id ) {
			$update_result = Field::update_meta( $field_id, 'widget_id', $id );
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
		// Lookup field with this widget ID.
		$query_args            = array(
			'meta_query' => array(
				array(
					'key'     => 'widget_id',
					'value'   => $widget_id,
					'compare' => '=',
				),
			),
		);
		$widget_fields_records = Fields::find( $query_args, 'records' );

		if ( count( $widget_fields_records ) === 0 ) {
			return;
		}

		// Delete the field.
		foreach ( $widget_fields_records as $item ) {
			Field::destroy( $item->get_id() );
		}
	}

	/**
	 * On save sidebar, update related fields.
	 *
	 * @since 3.0.0
	 */
	public static function save_sidebar() {
		/**
		 * We need to do an additional check to see if any of our fields
		 * are in the inactive sidebar, and if they are, then we need to make
		 * sure they are disabled.
		 *
		 * This only applies to the new block editor powered widget screen.
		 *
		 * This covers the unusual use case, where removing a saved widget
		 * (block) puts it in the inactive widgets (sometimes), but you can
		 * only see this after refreshing the widgets screen, after_save_widget
		 * is not fired for some reason.
		 *
		 * Steps to reproduce:
		 * 1. Ensure inactive sidebar is closed (not 100% sure this needs to be done)
		 * 2. Create a new field block in a widget sidebar (not in inactive sidebar)
		 * 3. Update some attribute in the block (label for example)
		 * 4. Press Update
		 * 5. From the dropdown menu (in the block toolbar) choose "Remove...[name]"
		 * 6. Press Update.
		 * 7. Notice inactive widgets is still empty
		 * 8. Refresh the widgets screen
		 * 9. Notice inactive widgets is now populated with the field block.
		 */

		$sidebars_widgets = wp_get_sidebars_widgets();

		// We only want to track the widgets that got moved into inactive in this weird way...
		if ( ! isset( $sidebars_widgets['wp_inactive_widgets'] ) ) {
			return;
		}
		$inactive_widget_ids = $sidebars_widgets['wp_inactive_widgets'];

		// Lookup fields with that are associated with widgets.
		$query_args            = array(
			'meta_query' => array(
				array(
					'key'     => 'widget_id',
					'compare' => 'EXISTS',
				),
			),
		);
		$widget_fields_records = Fields::find( $query_args, 'records' );
		if ( count( $widget_fields_records ) === 0 ) {
			return;
		}
		foreach ( $widget_fields_records as $item ) {
			$widget_id = Field::get_meta( $item->get_id(), 'widget_id', true );
			if ( in_array( $widget_id, $inactive_widget_ids, true ) ) {
				// We found a field that should be disabled.
				if ( $item->get_status() === 'enabled' ) {
					// Then we need to disable it.
					$field = Field_Factory::create_from_record( $item );
					if ( is_wp_error( $field ) ) {
						continue;
					}
					$field->set_status( 'disabled' );
					$field->save();
				}
			}
		}
	}
	/**
	 * Check if a post contains fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if the post has fields, false if not.
	 */
	private static function post_has_fields( $post_id ) {

		$post_fields = self::get_post_content_fields( $post_id );
		// Use post fields first.
		if ( count( $post_fields ) > 0 ) {
			return true;
		}
		return false;
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
		$post_blocks = Block_Parser::extract_blocks( $content );
		// $post_shortcodes = Shortcode_Parser::extract_shortcodes( $content );
		$fields = array();

		foreach ( $post_blocks as $post_block ) {
			$fields[] = $post_block;
		}

		$post_shortcodes = Shortcode_Parser::extract_shortcodes( $content );
		foreach ( $post_shortcodes as $post_shortcode ) {
			$fields[] = $post_shortcode;
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

		$query_id       = $connected_query_ids[0];
		$query          = \Search_Filter\Queries\Query::find( array( 'id' => $query_id ) );
		$posts_per_page = $query->get_attribute( 'postsPerPage' );

		if ( ! $posts_per_page ) {
			return $block_data;
		}
		$block_data['attrs']['query']['perPage'] = (string) $posts_per_page;
		return $block_data;
	}

	private static function needs_query_block_global_query_override( $block_data ) {
		$is_assigned_to_search_filter = isset( $block_data['attrs']['searchFilterQueryId'] ) && absint( $block_data['attrs']['searchFilterQueryId'] ) !== 0;
		if ( $block_data['attrs']['query']['inherit'] === true && $is_assigned_to_search_filter ) {
			return true;
		}
		return false;
	}


	/**
	 * Try to connect a query block to our query.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $block The block config.
	 * @param string $query_integration_name The name of the integration.
	 * @return bool
	 */
	public static function try_connect_to_query_loop( $block, $query_integration_name = 'query_block', $block_name = 'core/query' ) {
		if ( $block['blockName'] !== $block_name ) {
			return;
		}

		// Return early if we're already tracking a query (don't allow query blocks inside of query blocks).
		if ( self::$is_tracking_query ) {
			return;
		}

		/*
		 * First check to see if the query has specifically been assigned to this query block.
		 * If so that's the simplest case and we can return early.
		 */
		if ( self::needs_query_block_global_query_override( $block ) ) {
			Util::error_log( "Found a query loop that we can't reach, its set to 'default' but should be set to 'custom'.", 'warning' );
			return;
		}

		$query_search_filter_id = isset( $block['attrs']['searchFilterQueryId'] ) ? absint( $block['attrs']['searchFilterQueryId'] ) : 0;
		if ( $query_search_filter_id !== 0 ) {
			// Then we can connect this query to the query block.
			$query = Query::find( array( 'id' => $query_search_filter_id ) );
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
			return false;
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
			return false;
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
			$field_post_query_ids[] = $field['queryId'];
		}

		$related_query_ids = array_unique( array_merge( $post_query_ids, $field_post_query_ids ) );

		// Loop through the fields and check if they should affect the query.
		foreach ( $related_query_ids as $query_id ) {
			// If there are fields assigned to this post, but no longer connected to any blocks
			// then we need to delete them.
			$query = null;

			try {
				$query = new Query( $query_id );
			} catch ( \Exception $e ) {
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
	private static function attach_query_vars_filter() {
		add_action( 'query_loop_block_query_vars', array( __CLASS__, 'query_loop_block_query_vars' ), 20, 3 );
	}
	private static function detach_query_vars_filter() {
		remove_action( 'query_loop_block_query_vars', array( __CLASS__, 'query_loop_block_query_vars' ), 20, 3 );
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
	 */
	public static function cleanup_query_block( $block ) {

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
	 * @param array  $query_vars The query vars.
	 * @param Block  $block      The block config.
	 * @param string $page       The page.
	 *
	 * @return array The updated query vars.
	 */
	public static function query_loop_block_query_vars( $query_vars, $block, $page ) {
		// Still not sure why exactly, but we need to set the posts_per_page and
		// override the offest that gets added by `query_loop_block_query_vars`.
		$connected_queries                   = self::$current_query_data['connected_queries'];
		$query_vars['search_filter_queries'] = $connected_queries;
		// TODO - there should only be one connected query, lets throw an error if we find more.
		foreach ( $connected_queries as $query ) {
			$offset   = 0;
			$per_page = $query->get_attribute( 'postsPerPage' );
			if (
				isset( $block->context['query']['offset'] ) &&
				is_numeric( $block->context['query']['offset'] )
			) {
				$offset = absint( $block->context['query']['offset'] );
			}

			$query_vars['offset']         = ( $per_page * ( $page - 1 ) ) + $offset;
			$query_vars['posts_per_page'] = $per_page;
		}
		return $query_vars;
	}

	/**
	 * Remove fields from our tables when a post is deleted.
	 *
	 * @param number $post_id The post ID.
	 * @return void
	 */
	public static function delete_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$post_field_records = self::get_post_field_records( $post_id );

		foreach ( $post_field_records as $item ) {
			Field::destroy( $item->get_id() );
		}
	}
}
