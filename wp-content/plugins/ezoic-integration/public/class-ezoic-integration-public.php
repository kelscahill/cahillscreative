<?php
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 * @author     Ezoic Inc. <support@ezoic.com>
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-factory.php';

class Ezoic_Integration_Public {
	/**
	 * The loader of this plugin.
	 *
	 * @since    2.0
	 * @access   protected
	 */
	protected $loader;

	private $title_call_count = 0;

	private $footer_call_count = 0;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string                   $plugin_name           The name of the plugin.
	 * @param      string                   $version               The version of this plugin.
	 * @param      Ezoic_Integration_Loader $loader The loader of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function register_hooks( $loader ) {
		$this->loader = $loader;

		// Do not run any hooks/filters if disabled
		if ( defined( 'EZOIC__DISABLE' ) && EZOIC__DISABLE ) {
			return;
		}

		$this->bypass_cache_filters();

		if ( isset( $_SERVER['HTTP_X_EZOIC_MICRODATA'] ) && $_SERVER['HTTP_X_EZOIC_MICRODATA'] == 'true' ) {
			$this->register_ez_hooks();

			$this->loader->add_filter( 'the_content', $this, 'content_filter', 10, 2 );
			$this->loader->add_filter( 'wp_footer', $this, 'footer_filter' );
			$this->loader->add_filter( 'the_title', $this, 'title_filter', 10, 2 );
			$this->loader->add_action( 'dynamic_sidebar_params', $this, 'dynamic_sidebar_params_filter' );
			$this->loader->add_filter( 'get_sidebar', $this, 'sidebar_comment' );
		}

		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );

		if ( \defined( 'EZOIC_DEBUG' ) && EZOIC_DEBUG ) {
			$this->loader->add_action( 'shutdown', $this, 'ez_debug_output' );
		}

	}

	private function register_ez_hooks() {
		$this->loader->add_filter( 'ez_widget_output', $this, 'widget_filter', 5 );
		$this->loader->add_filter( 'ez_buffered_final_content', $this, 'process_final_content' );
		$this->loader->add_filter( 'ez_headline', $this, 'set_headline_comment' );
	}


	public function process_final_content( $content ) {
		$content = $this->modify_headline( $content );
		$modified_content = $this->modify_head_tag( $content );
		$content = $this->modify_body_tag( $modified_content['content'], $modified_content['feedAdded'], $modified_content['commentsfeedAdded'] );
		$content = $this->modify_main_tag( $content );
		$content = $this->modify_sidebar( $content );
		$content = $this->modify_author_tag( $content );
		$content = $this->modify_pagination_links( $content );
		$content = $this->modify_ez_comments( $content );

		return $content;
	}

	public function set_headline_comment( $title ) {
		return $title . "<!-- ez_headline -->";
	}

	public function dynamic_sidebar_params_filter( $sidebar_params ) {
		if ( is_admin() ) {
			return $sidebar_params;
		}

		global $wp_registered_widgets;
		$widget_id = $sidebar_params[0]['widget_id'];

		$wp_registered_widgets[$widget_id]['original_callback'] = $wp_registered_widgets[$widget_id]['callback'];
		$wp_registered_widgets[$widget_id]['callback'] = [$this, 'custom_widget_callback'];

		return $sidebar_params;
	}

	public function custom_widget_callback() {
		global $wp_registered_widgets;
		$original_callback_params = func_get_args();
		$widget_id = $original_callback_params[0]['widget_id'];

		$original_callback = $wp_registered_widgets[$widget_id]['original_callback'];
		$wp_registered_widgets[$widget_id]['callback'] = $original_callback;

		$widget_id_base = $wp_registered_widgets[$widget_id]['callback'][0]->id_base;

		if ( is_callable( $original_callback ) ) {

			ob_start();
			call_user_func_array( $original_callback, $original_callback_params );
			$widget_output = ob_get_clean();
			echo apply_filters( 'ez_widget_output', $widget_output, $widget_id_base, $widget_id );
		}
	}

	public function sidebar_comment() {
		echo '<!-- ez_sidebar -->';
	}

	public function ez_debug_output() {
		$debuggers = [];

		// Output debugging information
		$debuggers[] = new Ezoic_Integration_WP_Debug( Ezoic_Cache_Type::NO_CACHE );

		foreach ( $debuggers as $debugger ) {
			echo $debugger->get_debug_information();
		}

		\do_action( 'ez_debug_output' );
	}

	public function footer_filter() {
		$this->footer_call_count = $this->footer_call_count + 1;

		if ( $this->footer_call_count == 1 ) {
			echo apply_filters( 'ez_bottom_of_page', null );
		}
	}

	public function content_filter( $content, $id = null ) {
		if ( $this->is_list_page() ) {
			return apply_filters( 'ez_the_content_for_list', $content, $id );
		} else {
			return apply_filters( 'ez_the_content_for_page', $content, $id );
		}
	}

	public function title_filter( $title, $id = null ) {
		if ( is_admin() ) {
			return $title; // don't run in the backend
		}

		if ( empty( $title ) || $id < 1 ) {
			return $title; // invalid values received
		}

		global $wp_current_filter;

		global $post;
		if ( isset( $post ) ) {
			if ( get_post_type( $post->ID ) != "post" ) {
				return $title; // only process post titles
			}

			if ( doing_action( 'wp_head' ) ) {
				return $title; // Don't run this filter if wp_head calls it
			}

			$next = get_next_post();
			$prev = get_previous_post();
			if ( $next !== '' && $id == $next->ID ) {
				return $title;
			}

			if ( $prev !== '' && $id == $prev->ID ) {
				return $title;
			}
		}

		/**
		 * PREVENTATIVE MEASURE...
		 * only apply the filter to the current page's title,
		 * and not to the other title's on the current page
		 */
		global $wp_query;
		if ( $id !== $wp_query->queried_object_id ) {
			return apply_filters( 'ez_title_secondary', $title, $id );
		}

		$title = apply_filters( 'ez_title_primary', $title . "", $id );

		if ( $this->title_call_count === 0 ) {
			$title = apply_filters( 'ez_headline', $title, $id );
		}

		$this->title_call_count = $this->title_call_count + 1;

		return $title;
	}

	public function widget_filter( $widget_output, $widget_id_base = null, $widget_id = null ) {
		$widget_output = apply_filters( 'ez_widget_content', $widget_output );

		if ( strpos( $widget_output, 'widget_categories' ) !== false ) {
			return apply_filters( 'ez_widget_categories', $widget_output );
		}

		if ( strpos( $widget_output, 'widget_recent_entries' ) !== false ) {
			return apply_filters( 'ez_widget_recent_entries', $widget_output );
		}

		if ( strpos( $widget_output, 'widget_archive' ) !== false ) {
			return apply_filters( 'ez_widget_archive', $widget_output );
		}

		if ( strpos( $widget_output, 'widget_meta' ) !== false ) {
			return apply_filters( 'ez_widget_meta', $widget_output );
		}

		return $widget_output;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ezoic-integration-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ezoic-integration-public.js', array( 'jquery' ), $this->version, false );

	}

	private function bypass_cache_filters() {
		// Prevent WP-Touch Cache(s)
		$this->loader->add_filter( 'wptouch_addon_cache_current_page', '__return_false', 99 );
	}

	private function is_list_page() {
		return is_category() || is_archive() || is_home() || ( is_front_page() && is_home() );
	}

	private function modify_headline( $content ) {
		$under_page_title = apply_filters( 'ez_under_page_title', '' );
		$filtered = preg_replace( '/<!-- ez_headline -->(<\/.*>)/', '$1' . $under_page_title, $content );
		if ( is_null( $filtered ) ) {
			return $content;
		}
		return $filtered;
	}

	private function modify_body_tag( $content, $feedAdded, $commentsfeedAdded ) {
		$top_of_page = apply_filters( 'ez_top_of_page', '' );
		$body_attributes = apply_filters( 'ez_body_attributes', '' );

		// Only want to add the itemref attribute if the corresponding id attributes were added to the links
		// in the head tag, otherwise it's an invalid structure
		if ( $feedAdded && $commentsfeedAdded ) {
			$attrs .= ' itemref="feed commentsfeed"';
		} elseif ( $feedAdded ) {
			$attrs .= ' itemref="feed"';
		} elseif ( $commentsfeedAdded ) {
			$attrs .= ' itemref="commentsfeed"';
		}

		$filtered = preg_replace( '/<body(.*?)>/', '<body$1' . $body_attributes . '>' . $top_of_page, $content );
		if ( is_null( $filtered ) ) {
			return $content;
		}
		return $filtered;
	}

	private function modify_main_tag( $content ) {
		$main_attributes = apply_filters( 'ez_main_attributes', '' );
		$filtered = preg_replace( '/(<main.*?|<.*class="main".*?|<.*id="main".*?)/i', '$1 ' . $main_attributes . ' $2', $content );
		if ( is_null( $filtered ) ) {
			return $content;
		}
		return $filtered;
	}

	private function modify_sidebar( $content ) {
		$sidebar_index = strpos( $content, "<!-- ez_sidebar -->" ) + strlen( "<!-- ez_sidebar -->" );
		$search_index = $sidebar_index;
		$stack = array();
		$skip = false;
		// First loop just to grab initial opening tag
		while ( $search_index < strlen( $content ) ) {
			if ( $content[$search_index] == '<' ) {
				array_push( $stack, '<' );
				$search_index++;
				break;
			}
			$search_index++;
		}

		// Continue until stack is empty
		while ( $search_index < strlen( $content ) ) {
			$current = $content[$search_index];
			// If a string, wait until it closes before reading again
			if ( $current == '"' ) {
				$skip = !$skip;
			}

			if ( !$skip ) {
				if ( $current == '/' && $content[$search_index + 1] == '>' ) {
					array_pop( $stack );
				}
				if ( $current == '<' ) {
					if ( $content[$search_index + 1] == '/' ) {
						array_pop( $stack );
					} else {
						array_push( $stack, $current );
					}
				}

				if ( empty( $stack ) ) {
					// Found end of sidebar, need to continue to the end of current tag
					while ( $search_index < strlen( $content ) ) {
						$current = $content[$search_index];
						if ( $current == '"' ) {
							$skip = !$skip;
						}
						if ( !$skip ) {
							if ( $current == '>' ) {
								$search_index++;
								return $this->call_sidebar($content, $search_index);
							}
							$search_index++;
						}
					}
				}
			}
			$search_index++;
		}
		// Was not successful in finding sidebar, returning unmodified content
		return $content;
	}

	private function call_sidebar( $content, $search_index ) {
		// We reached the end can finally call the filter
		$insert = '<!-- ez_end_sidebar -->';
		$content = substr_replace( $content, $insert, $search_index, 0 );
		// add a comment to end of sidebar to get entire contents
		preg_match( '/<!-- ez_sidebar -->(.*)<!-- ez_end_sidebar -->>/is', $content, $matches, PREG_OFFSET_CAPTURE );
		$has_sidebar = !is_null($matches) && count($matches) > 1 && !is_null($matches[1][0]);
		if ( $has_sidebar ) {
			$sidebar = $matches[1][0];
			// if there is a sidebar then you can call the filter to modify sidebar content
			$sidebar_modified = apply_filters( 'ez_sidebar', $sidebar );
			$filtered = preg_replace( '/<!-- ez_sidebar -->(.*)<!-- ez_end_sidebar -->/is', $sidebar_modified, $content );
			if ( is_null( $filtered ) ) {
				return $content;
			}
		}
		$filtered = preg_replace( '/<!-- ez_sidebar -->(.*)<!-- ez_end_sidebar -->/is', '$1', $content );
		if ( is_null( $filtered ) ) {
			return $content;
		}
		return $filtered;
	}

	private function modify_author_tag( $content ) {
		$meta_attributes = apply_filters( 'ez_author_meta', '' );
		$author_attributes = apply_filters( 'ez_author_attributes', '' );
		if ( $meta_attributes != '' ) {
			$filtered = preg_replace( '/(<a.+author.*?("|\')).*?>(.*?)(<\/a>)/i', ' $1 ' . $author_attributes . '><meta ' . $meta_attributes . '/>$3</a>', $content );
			if ( is_null( $filtered ) ) {
				return $content;
			}
			return $filtered;
		} else {
			$filtered = preg_replace( '/(<a.+author.*?("|\')).*?>(.*?)(<\/a>)/i', ' $1 ' . $author_attributes . '>$3</a>', $content );
			if ( is_null( $filtered ) ) {
				return $content;
			}
			return $filtered;
		}

	}

	private function modify_pagination_links( $content ) {
		return apply_filters( 'ez_pagination_links', $content );
	}

	// This hook is specifically to replace <!-- EZ_XXXXX --> comments in html
	private function modify_ez_comments( $content ) {
		return apply_filters( 'ez_comment_replace', $content );
	}

	private function modify_head_tag( $content ) {
		return apply_filters( 'ez_head_tag', $content );
	}

	private function modify_navigation( $content ) {
		return apply_filters( 'navigation_markup_template', $content );
	}
}
