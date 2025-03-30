<?php

/*
|--------------------------------------------------------------------------
| Enable Timber Theme Support
|--------------------------------------------------------------------------
*/

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
  require_once $composer_autoload;
  Timber\Timber::init();
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'patterns' );

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

/**
 * Here's what's happening with these hooks:
 * 1. WordPress initially detects theme in themes/your-theme/resources
 * 2. Upon activation, we tell WordPress that the theme is actually in themes/your-theme/resources/views
 * 3. When we call get_template_directory() or get_template_directory_uri(), we point it back  to themes/your-theme/resources
 *
 * We do this so that the Template Hierarchy will look in themes/your-theme/resources/views for  core WordPress themes
 * But functions.php, style.css, and index.php are all still located in themes/your-theme/resources
 *
 * This is not compatible with the WordPress Customizer theme preview prior to theme activation
 *
 * get_template_directory()   -> /srv/www/example.com/current/web/app/themes/your-theme/resources
 * get_stylesheet_directory() -> /srv/www/example.com/current/web/app/themes/your-theme/resources
 * locate_template()
 * ├── STYLESHEETPATH         -> /srv/www/example.com/current/web/app/themes/your-theme/resources/views
 * └── TEMPLATEPATH           -> /srv/www/example.com/current/web/app/themes/your-theme/resources
 */

// Namespaces
add_filter(
  'timber/loader/loader',
  function ($loader) {
    $atoms_path = __DIR__ . '/resources/views/patterns/01-atoms';
    $molecules_path = __DIR__ . '/resources/views/patterns/02-molecules';
    $organisms_path = __DIR__ . '/resources/views/patterns/03-organisms';
    $templates_path = __DIR__ . '/resources/views/patterns/04-templates';

    if (file_exists($atoms_path)) {
      $loader->addPath($atoms_path, 'atoms');
    }

    if (file_exists($molecules_path)) {
      $loader->addPath($molecules_path, 'molecules');
    }

    if (file_exists($organisms_path)) {
      $loader->addPath($organisms_path, 'organisms');
    }

    if (file_exists($templates_path)) {
      $loader->addPath($templates_path, 'templates');
    }

    return $loader;
  }
);

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
      $context['disclaimer'] = 'DISCLOSURE: Some of the links are affiliate links, meaning, at no additional cost to you, I will earn a commission if you click through and make a purchase.';

      /* Menus */
      $context['primary_nav'] = Timber::get_menu( 'Primary Navigation' );
      $context['footer_nav']  = Timber::get_menu( 'Footer Navigation' );
      $context['plans_nav']  = Timber::get_menu( 'Plans Navigation' );

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

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

try {
  \Roots\bootloader();
} catch (Throwable $e) {
  wp_die(
    __('You need to install Acorn to use this theme.', 'sage'),
    '',
    [
      'link_url' => 'https://docs.roots.io/acorn/2.x/installation/',
      'link_text' => __('Acorn Docs: Installation', 'sage'),
    ]
  );
}

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters'])
  ->each(function ($file) {
    if (! locate_template($file = "app/{$file}.php", true, true)) {
      wp_die(
        /* translators: %s is replaced with the relative file path */
        sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
      );
    }
  });

/*
|--------------------------------------------------------------------------
| Enable Sage Theme Support
|--------------------------------------------------------------------------
|
| Once our theme files are registered and available for use, we are almost
| ready to boot our application. But first, we need to signal to Acorn
| that we will need to initialize the necessary service providers built in
| for Sage when booting.
|
*/

add_theme_support('sage');

/*
|--------------------------------------------------------------------------
| Register Additonal Functions
|--------------------------------------------------------------------------
*/
/**
 * Register Custom Theme Functions.
 */
$register_theme_functions = __DIR__ . '/resources/functions/custom-theme-functions.php';
if (file_exists($register_theme_functions)) {
  require_once $register_theme_functions;
}

/**
 * Register Custom Blocks.
 */
$register_custom_blocks = __DIR__ . '/resources/functions/custom-blocks.php';
if (file_exists($register_custom_blocks)) {
  require_once $register_custom_blocks;
}

/**
 * Register Custom Post Types.
 */
$register_custom_content_types = __DIR__ . '/resources/functions/custom-content-types.php';
if (file_exists($register_custom_content_types)) {
  require_once $register_custom_content_types;
}

/**
 * Register Custom Taxonomies.
 */
$register_custom_taxonomy = __DIR__ . '/resources/functions/custom-taxonomy.php';
if (file_exists($register_custom_taxonomy)) {
  require_once $register_custom_taxonomy;
}
