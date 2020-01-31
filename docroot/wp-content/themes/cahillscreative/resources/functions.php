<?php
/**
 * Do not edit anything in this file unless you know what you're doing
 */

use Roots\Sage\Config;
use Roots\Sage\Container;

/**
 * Helper function for prettying up errors
 * @param string $message
 * @param string $subtitle
 * @param string $title
 */
$sage_error = function ($message, $subtitle = '', $title = '') {
  $title = $title ?: __('Sage &rsaquo; Error', 'sage');
  $footer = '<a href="https://roots.io/sage/docs/">roots.io/sage/docs/</a>';
  $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
  wp_die($message, $title);
};

/**
 * Ensure compatible version of PHP is used
 */
if (version_compare('7', phpversion(), '>=')) {
  $sage_error(__('You must be using PHP 7 or greater.', 'sage'), __('Invalid PHP version', 'sage'));
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare('5.0.0', get_bloginfo('version'), '>=')) {
  $sage_error(__('You must be using WordPress 5.0.0 or greater.', 'sage'), __('Invalid WordPress version', 'sage'));
}

/**
 * Ensure dependencies are loaded
 */
if (!class_exists('Roots\\Sage\\Container')) {
  if (!file_exists($composer = __DIR__.'/../vendor/autoload.php')) {
    $sage_error(
      __('You must run <code>composer install</code> from the Sage directory.', 'sage'),
      __('Autoloader not found.', 'sage')
    );
  }
  require_once $composer;
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
  ['theme_file_path', 'theme_file_uri', 'parent_theme_file_path', 'parent_theme_file_uri'],
  array_fill(0, 4, 'dirname')
);
Container::getInstance()
  ->bindIf('config', function () {
    return new Config([
      'assets' => require dirname(__DIR__).'/config/assets.php',
      'theme' => require dirname(__DIR__).'/config/theme.php',
      'view' => require dirname(__DIR__).'/config/view.php',
    ]);
  }, true);

/**
 * Allow SVG's through WP media uploader
 */
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * ACF Options Page
 */
if (function_exists('acf_add_options_page')) {
  acf_add_options_page(array(
    'page_title'  => 'Theme General Settings',
    'menu_title'  => 'Theme Settings',
    'menu_slug'   => 'theme-general-settings',
    'capability'  => 'edit_posts',
    'redirect'    => false
  ));
}

function acf_timber_context( $context ) {
  $context['options'] = get_fields('option');
  return $context;
}
add_filter('timber_context', 'acf_timber_context');

function my_acf_json_save_point( $path ) {
  $path = get_stylesheet_directory() . '/acf-json';
  // return
  return $path;
}
add_filter('acf/settings/save_json', 'my_acf_json_save_point');

/**
 * Change Term Description
 */
remove_filter('term_description','wpautop');

/**
 * Add excerpt to pages
 */
add_post_type_support( 'page', 'excerpt' );

/**
 * Load ajax script on news template
 */
// function enqueue_ajax_load_more() {
//    wp_enqueue_script('ajax-load-more'); // Already registered, just needs to be enqueued
// }
// add_action('wp_enqueue_scripts', 'enqueue_ajax_load_more');

/**
 * Blog Filter
 */
function misha_filter_function(){

  $args = array(
    'post_type' => 'post',
    'orderby' => 'date',
  );

  if( isset( $_POST['projects'] ) )
    $args['tax_query'] = array(
      array(
        'taxonomy' => 'projects',
        'field' => 'term_id',
        'terms' => array($_POST['projects']),
        'operator' => 'IN',
      )
    );

  $query = new WP_Query( $args );

  if( $query->have_posts() ) :
    while( $query->have_posts() ): $query->the_post();
      echo '<h2>' . $query->post->post_title . '</h2>';
      echo $_POST['projects'];
    endwhile;
    wp_reset_postdata();
  else :
    echo 'No posts found';
  endif;

  die();
}

add_action('wp_ajax_myfilter', 'misha_filter_function');
add_action('wp_ajax_nopriv_myfilter', 'misha_filter_function');
