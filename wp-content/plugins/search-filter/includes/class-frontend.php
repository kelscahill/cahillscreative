<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Dependants;
use Search_Filter\Core\Deprecations;
use Search_Filter\Core\Icons;
use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main class for initialising all things for the frontend.
 */
class Frontend {

	/**
	 * The shortcode tag.
	 *
	 * @since 3.0.0
	 */
	const SHORTCODE_TAG = 'searchandfilter';

	/**
	 * Filter next query ID.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	private static $filter_next_query_id = 0;

	/**
	 * Registered assets.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var    array    $registered_assets    The registered assets.
	 */
	private static $registered_assets = array();

	/**
	 * Enqueued assets.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var    array    $enqueued_assets    The enqueued assets.
	 */
	private static $enqueued_assets = array();

	/**
	 * Has registered assets.
	 *
	 * @since 3.2.0
	 * @var bool
	 */
	private static $has_registered_assets = false;


	/**
	 * Init the frontend.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		if ( ! self::should_init() ) {
			return;
		}

		\Search_Filter\Query\Selector::init();
		\Search_Filter\Query::init();

		// Register the shortcode.
		// Because the frontend can be initialised in multiple ways, we need to ensure
		// the shortcode is registered only once - we should probably move this out of
		// here.
		if ( ! shortcode_exists( self::SHORTCODE_TAG ) ) {
			add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'shortcode' ) );
		}

		add_action( 'search-filter/frontend/filter_next_query', array( __CLASS__, 'filter_next_query' ), 10, 1 );
		add_filter( 'search-filter/core/asset-loader/build', array( __CLASS__, 'apply_asset_filters' ), 10, 1 );

		// Scripts & css.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );

		add_filter( 'search-filter/core/asset-loader/enqueue_scripts', array( __CLASS__, 'filter_enqueue_scripts' ), 10, 1 );
		add_filter( 'search-filter/core/asset-loader/enqueue_styles', array( __CLASS__, 'filter_enqueue_styles' ), 10, 1 );

		// Use a really low priority so that we load after other plugins, eg Elementor loads popups in
		// `wp_footer` and we want to load after them just in case they have S&F fields that we need to
		// register.
		add_action( 'wp_footer', array( \Search_Filter\Core\SVG_Loader::class, 'output' ), 100 );
		add_action( 'wp_footer', array( __CLASS__, 'data' ), 100 );
	}

	/**
	 * Should init.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if the frontend should be initialised, false otherwise.
	 */
	public static function should_init() {
		return apply_filters( 'search-filter/frontend/should_init', true );
	}
	/**
	 * Get the asset configs for the frontend.
	 *
	 * @since 3.2.0
	 *
	 * @return array The asset configs.
	 */
	private static function get_base_asset_configs() {

		$asset_configs = array(
			array(
				'name'   => 'search-filter-frontend',
				'script' => array(
					'src'          => SEARCH_FILTER_URL . 'assets/frontend/app.js',
					'asset_path'   => SEARCH_FILTER_PATH . 'assets/frontend/app.asset.php',
					'dependencies' => array(),
				),
				'style'  => array(
					'src' => SEARCH_FILTER_URL . 'assets/frontend/app.css',
				),
			),
		);

		// UGC needs to be added with the frontend dependency, only after the frontend has
		// actually been added to the page otherwise it will fail to load in the head.
		// Need to find a hook for when a style is added to the page.
		if ( CSS_Loader::get_mode() === 'file-system' ) {
			$asset_configs[] = array(
				'name'  => 'search-filter-frontend-ugc',
				'style' => array(
					'src'          => CSS_Loader::get_css_url(),
					'dependencies' => array( 'search-filter-frontend' ),
					'version'      => CSS_Loader::get_version(),
					'style_media'  => 'all',
				),
			);
		}

		return $asset_configs;
	}


	/**
	 * Registers the assets.
	 *
	 * @since 3.2.0
	 *
	 * @param array|string $exclude_handles The asset handles to exclude.
	 */
	public static function register_assets( $exclude_handles = array() ) {

		// Only register assets once.
		if ( self::$has_registered_assets ) {
			return;
		}

		self::$has_registered_assets = true;

		if ( empty( $exclude_handles ) ) {
			// Ensure this is an array before passing to create assets.
			// WP hooks without arguments will pass an empty string as
			// the first paramater to a callback.
			$exclude_handles = array();
		}
		// Create frontend base assets.
		$asset_configs = self::get_base_asset_configs();
		$assets        = Asset_Loader::create( $asset_configs, $exclude_handles );

		// Allow assets to be modified.
		self::$registered_assets = apply_filters( 'search-filter/frontend/register_assets', $assets );

		Asset_Loader::register( self::$registered_assets );

		// Register the assets with WP.
		foreach ( self::$registered_assets as $asset_name => $args ) {
			if ( ! empty( $args['script']['src'] ) ) {
				wp_register_script( $asset_name, $args['script']['src'], $args['script']['dependencies'], $args['script']['version'], $args['script']['footer'] );
			}
			if ( ! empty( $args['style']['src'] ) ) {
				wp_register_style( $asset_name, $args['style']['src'], $args['style']['dependencies'], $args['style']['version'], $args['style']['media'] );
			}
		}

		// Register the icons.
		Icons::register();

		// Register the components.
		Components::register_assets();

		// Lets always add the base assets to the enqueued assets array,
		// components will be handled on demand.
		self::$enqueued_assets = array_keys( $assets );
	}

	/**
	 * Gets all the registered assets.
	 *
	 * @since 3.2.0
	 *
	 * @return array The registered assets.
	 */
	public static function get_registered_assets() {
		$component_assets = \Search_Filter\Components::get_assets();
		return self::$registered_assets + $component_assets;
	}

	/**
	 * Enqueue the assets.
	 *
	 * @since 3.2.0
	 *
	 * @param array $exclude_handles The asset handles to exclude.
	 * @param bool  $force_enqueue   Whether to force enqueue the assets, if dynamic asset loading is enabled.
	 */
	public static function enqueue_assets( $exclude_handles = array(), $force_enqueue = false ) {

		// This function can be called from the `wp_enqueue_scripts` action.
		// WP hooks always pass a default arg of "" when there are no parameters
		// so lets make sure $exclude is an array.
		if ( empty( $exclude_handles ) ) {
			$exclude_handles = array();
		}

		// If dynamic asset loading is enabled, we can rely on the other asset dependencies
		// to trigger the loading of the frontend when needed.
		if ( Features::is_enabled( 'dynamicAssetLoading' ) && ! $force_enqueue ) {
			return;
		}

		$enqueued_assets = apply_filters( 'search-filter/frontend/enqueue_assets', self::$enqueued_assets );
		foreach ( $exclude_handles as $exclude_handle ) {
			if ( in_array( $exclude_handle, $enqueued_assets, true ) ) {
				// Remove the handle from the enqueued assets.
				$enqueued_assets = array_diff( $enqueued_assets, array( $exclude_handle ) );
			}
		}

		Asset_Loader::enqueue( $enqueued_assets );

		// Enqueue components.
		Components::enqueue_assets( true );
	}


	/**
	 * Convenience wrappers for filtering scripts via the asset loader.
	 *
	 * @param array $script_handles The script handles to filter.
	 * @return array The filtered script handles.
	 */
	public static function filter_enqueue_scripts( $script_handles ) {
		if ( ! Util::is_frontend_only() ) {
			return $script_handles;
		}
		$script_handles = apply_filters( 'search-filter/frontend/enqueue_scripts', $script_handles );
		return $script_handles;
	}
	/**
	 * Convenience wrappers for filtering styles via the asset loader.
	 *
	 * @param array $style_handles The style handles to filter.
	 * @return array The filtered style handles.
	 */
	public static function filter_enqueue_styles( $style_handles ) {
		if ( ! Util::is_frontend_only() ) {
			return $style_handles;
		}
		$style_handles = apply_filters( 'search-filter/frontend/enqueue_styles', $style_handles );
		return $style_handles;
	}
	/**
	 * Outputs the JSON object for the active fields (inital render data), queries and template data.
	 */
	public static function data() {
		do_action( 'search-filter/frontend/data/start' );
		$should_mount = apply_filters( 'search-filter/frontend/register_scripts/should_mount', true );

		$data = array(
			'fields'      => Fields::get_active_fields(),
			'queries'     => Queries::get_used_queries(),
			'shouldMount' => $should_mount,
			'restNonce'   => wp_create_nonce( 'wp_rest' ),
			'homeUrl'     => home_url(),
			'isPro'       => Dependants::is_search_filter_pro_enabled(), // Need this for the debugger app (chrome extension).
			'popoverNode' => Features::get_setting_value( 'compatibility', 'popoverNode' ),
		);
		// Add filter to modify the data.
		$data    = apply_filters( 'search-filter/frontend/data', $data );
		$api_url = apply_filters( 'search-filter/frontend/api_url', '' );

		?>
		<script type="text/javascript" id="search-filter-data-js" data-search-filter-data="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			window.searchAndFilterData = JSON.parse( document.getElementById( 'search-filter-data-js' ).getAttribute( 'data-search-filter-data' ) );
		</script>
		<script type="text/javascript" id="search-filter-api-url-js">
			window.searchAndFilterApiUrl = '<?php echo esc_js( $api_url ); ?>';
		</script>
		<?php
	}

	/**
	 * The main `[searchandfilter]` shortcode.
	 *
	 * @since    3.0.0
	 *
	 * @param array $attributes  The supplied shortcode attributes.
	 */
	public static function shortcode( $attributes ) {

		// This allows us to override the shortcode output in the legacy plugin.
		// TODO - remove this when we remove the legacy plugin.
		$override = apply_filters( 'search-filter/frontend/shortcode/override', false, $attributes );
		if ( $override ) {
			return $override;
		}

		$defaults = array(
			'field'  => '',
			'query'  => '',
			'action' => '',
			// 'skip'   => '', // Used for the filter_next_query action to skip the default output.
			/**
			 * Assume we're in the most likely sceanrio, a shortcode used within a rich
			 * text editor (ie after `the_content` has been applied). This will probably
			 * run esc_html on our attributes
			 */
			'decode' => in_the_loop() ? 'yes' : 'no',
		);

		$attributes = shortcode_atts( $defaults, $attributes, self::SHORTCODE_TAG );

		if ( 'yes' === $attributes['decode'] ) {
			$attributes = array_map( 'wp_specialchars_decode', $attributes );
		}

		$output = '';

		// Get the field data associated with the ID.
		$field = null;

		if ( empty( $attributes['field'] ) && empty( $attributes['action'] ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$output = '<p>Notice: No field or action passed to the [searchandfilter] shortcode.</p>';
			}
			return $output;
		}

		// Field takes precedence over action.
		if ( ! empty( $attributes['field'] ) ) {
			// Then we want to display a field.
			if ( is_numeric( $attributes['field'] ) ) {
				// Lookup by ID.
				$field = Field::get_instance( absint( $attributes['field'] ) );
			} else {
				$conditions = array(
					'status' => 'enabled',
					'name'   => $attributes['field'], // Search by name.
				);

				// If the query arg is passed, use that (because duplicate names are allowed).
				if ( isset( $attributes['query'] ) && '' !== $attributes['query'] ) {
					$conditions['query_id'] = absint( $attributes['query'] );
				}
				$field = Field::find( $conditions );
			}

			if ( is_wp_error( $field ) ) {
				$output = $field->get_error_message();
			} else {
				$output = $field->render( true );
			}
		} elseif ( ! empty( $attributes['action'] ) ) {

			if ( $attributes['action'] === 'filter_next_query' ) {

				if ( empty( $attributes['query'] ) && current_user_can( 'manage_options' ) ) {

					$output = '<p>Notice: No query passed to the [searchandfilter] shortcode.</p>';

				} elseif ( ! empty( $attributes['query'] ) ) {
					self::filter_next_query( absint( $attributes['query'] ) );
				}
			}
		}
		return $output;
	}

	/**
	 * Filters the next query.
	 *
	 * @param int|string $query_id The query ID as a string or int.
	 */
	public static function filter_next_query( $query_id ) {
		self::$filter_next_query_id = absint( $query_id );
		// Important: this needs to be attached below a priority of 20, as that's where
		// we run our logic to setup the relevant queries.
		add_action( 'pre_get_posts', array( __CLASS__, 'attach_to_next_query' ), 10 );
	}
	/**
	 * Filters the next query.
	 *
	 * @param \WP_Query $query The query object.
	 */
	public static function attach_to_next_query( $query ) {
		if ( ! empty( self::$filter_next_query_id ) ) {
			$query->set( 'search_filter_query_id', self::$filter_next_query_id );
			self::$filter_next_query_id = 0;
		}

		// Remove the action so that we don't end up in an infinite loop.
		remove_action( 'pre_get_posts', array( __CLASS__, 'attach_to_next_query' ), 10 );
	}

	/**
	 * Apply asset filters.
	 *
	 * @param array $asset The asset.
	 * @return array The asset.
	 */
	public static function apply_asset_filters( $asset ) {

		if ( ! isset( $asset['style'] ) ) {
			return $asset;
		}

		if ( ! isset( $asset['style']['src'] ) ) {
			return $asset;
		}

		// Track whether we've already applied the filename change.
		if ( isset( $asset['style']['has_increased_specificity'] ) ) {
			return $asset;
		}

		// If the user has enabled increased specificity, we should load our alternate stylesheets.
		$use_increased_specificity = Features::get_setting_value( 'compatibility', 'cssIncreaseSpecificity' );

		if ( $use_increased_specificity === 'yes' && Asset_Loader::get_db_version() > 1 ) {
			// Check the path contains `/frontend/` and then replace the `.css` with `.specific.css`.
			if ( strpos( $asset['style']['src'], '/frontend/' ) !== false ) {
				$asset['style']['src']                       = str_replace( '.css', '.specific.css', $asset['style']['src'] );
				$asset['style']['has_increased_specificity'] = true;
			}
		}
		return $asset;
	}


	/**
	 * Stub to prevent errors via extensions.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$message = 'Using deprecated method `Search_Filter\Frontend\enqueue_scripts()` (since 3.2.0). Update Search & Filter and extensions to the latest version to remove this warning.';
		Deprecations::add( $message );
	}
	/**
	 * Stub to prevent errors via extensions.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function get_js_data() {
		$message = 'Using deprecated method `Search_Filter\Frontend\get_js_data()` (since 3.2.0). Update Search & Filter and extensions to the latest version to remove this warning.';
		Deprecations::add( $message );
	}
	/**
	 * Stub to prevent errors via extensions.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		$message = 'Using deprecated method `Search_Filter\Frontend\enqueue_styles()` (since 3.2.0). Update Search & Filter and extensions to the latest version to remove this warning.';
		Deprecations::add( $message );
	}
}
