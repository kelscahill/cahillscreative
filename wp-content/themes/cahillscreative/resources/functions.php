<?php
/**
 * Do not edit anything in this file unless you know what you're doing
 *
 * @package WordPress
 */

use Roots\Sage\Config;
use Roots\Sage\Container;

/**
* Helper function for prettying up errors
*
* @param string $message
* @param string $subtitle
* @param string $title
*/
$sage_error = function ( $message, $subtitle = '', $title = '' ) {
  $title   = $title ?: __( 'Sage &rsaquo; Error', 'sage' );
  $footer  = '<a href="https://roots.io/sage/docs/">roots.io/sage/docs/</a>';
  $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
  wp_die( esc_html( $message ), esc_html( $title ) );
};

/**
* Ensure compatible version of PHP is used
*/
if ( version_compare( '7', phpversion(), '>=' ) ) {
  $sage_error( __( 'You must be using PHP 7 or greater.', 'sage' ), __( 'Invalid PHP version', 'sage' ) );
}

/**
* Ensure compatible version of WordPress is used
*/
if ( version_compare( '5.0.0', get_bloginfo( 'version' ), '>=' ) ) {
  $sage_error( __( 'You must be using WordPress 5.0.0 or greater.', 'sage' ), __( 'Invalid WordPress version', 'sage' ) );
}

/**
* Ensure dependencies are loaded
*/
if ( ! class_exists( 'Roots\\Sage\\Container' ) ) {
  if ( ! file_exists( $composer = __DIR__ . '/../vendor/autoload.php' ) ) {
    $sage_error(
      __( 'You must run <code>composer install</code> from the Sage directory.', 'sage' ),
      __( 'Autoloader not found.', 'sage' )
    );
  }
  include_once $composer;
}

/**
* Sage required files
*
* The mapped array determines the code library included in your theme.
* Add or remove files to the array as needed. Supports child theme overrides.
*/
array_map(function ($file) use ($sage_error) {
  $file = "../app/{$file}.php";
  if (!locate_template($file, true, true)) {
      $sage_error(sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file), 'File not found');
  }
}, ['helpers', 'setup', 'filters', 'admin', 'timber']);

if ( ! class_exists( 'StarterSite' ) ) {
  /**
   * We're going to configure our theme inside of a subclass of Timber\Site
   * You can move this to its own file and include here via php's include("MySite.php")
   */
  class StarterSite extends Timber\Site {

    /**
     * Add timber support.
     */
    public function __construct() {
      add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
      add_filter( 'timber/context', array( $this, 'add_to_context' ) );
      add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );

      parent::__construct();
    }

    /**
     * This is where you add some context
     *
     * @param string $context context['this'] Being the Twig's {{ this }}.
     */
    public function add_to_context( $context ) {
      $context['foo']  = 'bar';
      $context['site'] = $this;

      /* Global */
      $context['image_path'] = '/wp-content/themes/cahillscreative/resources/assets/images/';
      $context['disclaimer'] = 'DISCLOSURE: Some of the links are affiliate links, meaning, at no additional cost to you, I will earn a commission if you click through and make a purchase.';

      /* Menus */
      $context['primary_nav'] = new TimberMenu( 'Primary Navigation' );
      $context['footer_nav']  = new TimberMenu( 'Footer Navigation' );

      /* WooCommerce */
      global $woocommerce;
      $context['woocommerce'] = $woocommerce;
      $context['cart_count'] = $woocommerce->cart->cart_contents_count;

      if (is_main_site()) {
        $context['is_main_site'] = TRUE;
      } else {
        $context['is_main_site'] = FALSE;
      }

      return $context;
    }

    /**
     * BEM function to pass in bem style classes.
     *
     * @param string $context BEM main class.
     * @param string $block_class BEM block class.
     * @param string $element_class BEM element class.
     * @param string $modifiers BEM modifiers class(es).
     * @param string $extra BEM extra class(es).
     */
    public function bem_classes( $context, $block_class, $element_class = '', $modifiers = array(), $extra = array() ) {
      $base = $block_class;
      $mods = null;
      $xtra = null;
      if ( isset( $element_class ) && $element_class ) {
        $base = $base . '__' . $element_class;
      }
      if ( isset( $modifiers ) && ! empty( $modifiers ) ) {
        if ( ! is_array( $modifiers ) ) {
          $mods .= ' ' . $base . '--' . $modifiers;
        } else {
          foreach ( $modifiers as $mod ) {
            if ( $mod ) {
                $mods .= ' ' . $base . '--' . $mod;
            }
          };
        }
      }
      if ( isset( $extra ) && ! empty( $extra ) ) {
        if ( ! is_array( $extra ) ) {
          $xtra .= ' ' . $extra;
        } else {
          foreach ( $extra as $xtra_item ) {
            if ( $xtra_item ) {
                $xtra .= ' ' . $xtra_item;
            }
          };
        }
      }
      return $base . $mods . $xtra;
    }

    /**
     * Add Attributes function to pass in multiple attributes including bem style classes.
     *
     * @param string $context Attributes content.
     * @param string $additional_attributes Additional attributes for context.
     */
    public function add_attributes( $context, $additional_attributes = array() ) {
      $attribute = null;
      if ( isset( $additional_attributes ) && ! empty( $additional_attributes ) ) {
        foreach ( $additional_attributes as $key => $value ) {
          $attribute .= ' ' . $key . '=' . $value;
        };
      }
      return $attribute;
    }

    /**
     * Custom WordPress functions.
     *
     * @param string $twig get extension.
     */
    public function add_to_twig( $twig ) {
      $twig->addExtension( new Twig\Extension\StringLoaderExtension() );
      $twig->addFunction( new Twig\TwigFunction( 'bem_classes', array( $this, 'bem_classes' ), array( 'needs_context' => true ), array( 'is_safe' => array( 'html' ) ) ) );
      $twig->addFunction( new Twig\TwigFunction( 'add_attributes', array( $this, 'add_attributes' ), array( 'needs_context' => true ), array( 'is_safe' => array( 'html' ) ) ) );
      return $twig;
    }
  }
}

new StarterSite();

/**
* Here's what's happening with these hooks:
* 1. WordPress initially detects theme in themes/sage/resources
* 2. Upon activation, we tell WordPress that the theme is actually in themes/sage/resources/views
* 3. When we call get_template_directory() or get_template_directory_uri(), we point it back to themes/sage/resources
*
* We do this so that the Template Hierarchy will look in themes/sage/resources/views for core WordPress themes
* But functions.php, style.css, and index.php are all still located in themes/sage/resources
*
* This is not compatible with the WordPress Customizer theme preview prior to theme activation
*
* get_template_directory()   -> /srv/www/example.com/current/web/app/themes/sage/resources
* get_stylesheet_directory() -> /srv/www/example.com/current/web/app/themes/sage/resources
* locate_template()
* ├── STYLESHEETPATH         -> /srv/www/example.com/current/web/app/themes/sage/resources/views
* └── TEMPLATEPATH           -> /srv/www/example.com/current/web/app/themes/sage/resources
*/
array_map(
  'add_filter',
  array( 'theme_file_path', 'theme_file_uri', 'parent_theme_file_path', 'parent_theme_file_uri' ),
  array_fill( 0, 4, 'dirname' )
);
Container::getInstance()
->bindIf(
  'config',
  function () {
    return new Config(
      array(
        'assets' => include dirname( __DIR__ ) . '/config/assets.php',
        'theme'  => include dirname( __DIR__ ) . '/config/theme.php',
        'view'   => include dirname( __DIR__ ) . '/config/view.php',
      )
    );
  },
  true
);

// Namespaces
add_filter(
  'timber/loader/loader',
  function ( $loader ) {
    $loader->addPath( __DIR__ . '/views/patterns/01-atoms', 'atoms' );
    $loader->addPath( __DIR__ . '/views/patterns/02-molecules', 'molecules' );
    $loader->addPath( __DIR__ . '/views/patterns/03-organisms', 'organisms' );
    $loader->addPath( __DIR__ . '/views/patterns/04-templates', 'templates' );
    return $loader;
  }
);

/**
 * Register Custom Theme Functions.
 */
$register_theme_functions = __DIR__ . '/../app/custom-theme-functions.php';
if (file_exists($register_theme_functions)) {
  require_once $register_theme_functions;
}

/**
 * Register Custom Post Types.
 */
$register_custom_content_types = __DIR__ . '/../app/custom-content-types.php';
if (file_exists($register_custom_content_types)) {
  require_once $register_custom_content_types;
}

/**
 * Register Custom Taxonomies.
 */
$register_custom_taxonomy = __DIR__ . '/../app/custom-taxonomy.php';
if (file_exists($register_custom_taxonomy)) {
  require_once $register_custom_taxonomy;
}

/**
 * Register Custom Blocks.
 */
$register_custom_blocks = __DIR__ . '/../app/custom-blocks.php';
if (file_exists($register_custom_blocks)) {
  require_once $register_custom_blocks;
}
