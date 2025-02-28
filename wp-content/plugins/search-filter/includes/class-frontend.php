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

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Icons;
use Search_Filter\Core\Scripts;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main class for initialising all things for the frontend.
 */
class Frontend {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param string $plugin_name  The name of the plugin.
	 * @param string $version      The version of this plugin.
	 */

	private $registered_styles = array();

	/**
	 * The array of registered scripts.
	 *
	 * @var array
	 */
	private $registered_scripts = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_name The internal name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Init the frontend.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		\Search_Filter\Query\Selector::init();
		\Search_Filter\Query::init();
	}

	/**
	 * Register the stylesheets for the frontend.
	 *
	 * @since 3.0.0
	 */
	public function register_styles() {

		Icons::register();

		$this->registered_styles = array(
			$this->plugin_name                => array(
				'src'     => Scripts::get_frontend_assets_url() . 'css/frontend/frontend.css',
				'deps'    => array( $this->plugin_name . '-flatpickr' ),
				'version' => $this->version,
				'media'   => 'all',
			),
			// TODO - use the HMR url? Scripts::get_frontend_assets_url()
			$this->plugin_name . '-flatpickr' => array(
				'src'     => trailingslashit( plugin_dir_url( __DIR__ ) ) . 'assets/css/vendor/flatpickr.min.css',
				'deps'    => array(),
				'version' => $this->version,
				'media'   => 'all',
			),
		);

		if ( CSS_Loader::get_mode() === 'file-system' ) {
			$this->registered_styles[ $this->plugin_name . '-ugc-styles' ] = array(
				'src'     => CSS_Loader::get_css_url(),
				'deps'    => array( $this->plugin_name ),
				'version' => CSS_Loader::get_version(),
				'media'   => 'all',
			);
		}

		$this->registered_styles = apply_filters( 'search-filter/frontend/register_styles', $this->registered_styles );

		foreach ( $this->registered_styles as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
		}
	}

	/**
	 * Register the scripts for the frontend.
	 *
	 * @since 3.0.0
	 */
	public function register_scripts() {

		$this->registered_scripts = array(
			$this->plugin_name        => array(
				'src'     => Scripts::get_frontend_assets_url() . 'js/frontend/frontend.js',
				'deps'    => array( 'search-filter-flatpickr' ),
				'version' => $this->version,
				'footer'  => false,
			),
			'search-filter-flatpickr' => array(
				'src'     => trailingslashit( plugin_dir_url( __DIR__ ) ) . 'assets/js/vendor/flatpickr.min.js',
				'deps'    => array(),
				'version' => $this->version,
				'footer'  => false,
			),
		);

		$this->registered_scripts = apply_filters( 'search-filter/frontend/register_scripts', $this->registered_scripts );

		foreach ( $this->registered_scripts as $handle => $args ) {
			wp_register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
		}
	}
	/**
	 * Register the stylesheets for the public-facing side of the plugin.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {
		$this->register_styles();

		// TODO - need to re-implement dynamic file extension - we're not building unminifed files.

		$enqueued_styles = array_keys( $this->registered_styles );

		$enqueued_styles = apply_filters( 'search-filter/frontend/enqueue_styles', $enqueued_styles );

		foreach ( $enqueued_styles as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}
	/**
	 * Register the JavaScript for the frontend
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

		$this->register_scripts();

		$enqueued_scripts = array_keys( $this->registered_scripts );
		$enqueued_scripts = apply_filters( 'search-filter/frontend/enqueue_scripts', $enqueued_scripts );

		foreach ( $enqueued_scripts as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_enqueue_script( $handle );
			}
		}
	}

	/**
	 * Gets the initial JS data for the page.
	 */
	public function get_js_data() {
		// Due to the way webpack externals works, these need to exist
		// in the page load even though we populate them later.
		$data = array(
			'fields'    => (object) array(),
			'queries'   => (object) array(),
			'library'   => (object) array(
				'fields'     => (object) array(),
				'components' => (object) array(),
			),
			/**
			 * Custom nonce verification doesn't work in the rest api endpoint (which we need for
			 * things like autocomplete suggestions, eg, mostly pro features), unless we first set
			 * the X-WP-Nonce header in the api request, which means we also need to create a
			 * `wp_rest` to pass the header check.
			 */
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'homeUrl'   => home_url(),
			'isPro'     => false,
		);

		$data = apply_filters( 'search-filter/frontend/enqueue_scripts/data', $data );

		return $data;
	}

	/**
	 * Adds the initial JS objects to the page.
	 */
	public function add_js_data() {

		$data = $this->get_js_data();

		Scripts::attach_globals(
			$this->plugin_name,
			'frontend',
			$data
		);
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
		);
		// Add filter to modify the data.
		$data    = apply_filters( 'search-filter/frontend/data', $data );
		$api_url = apply_filters( 'search-filter/frontend/api_url', '' );

		?>
		<script type="text/javascript" id="search-filter-data-js">
			window.searchAndFilterData = <?php echo wp_json_encode( $data ); ?>;
		</script>
		<script type="text/javascript" id="search-filter-api-url-js">
			window.searchAndFilterApiUrl = '<?php echo esc_js( $api_url ); ?>';
		</script>
		<?php
	}
}

