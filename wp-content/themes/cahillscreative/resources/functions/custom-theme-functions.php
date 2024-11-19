<?php
/**
 *
 * @file
 * Register custom theme functions.
 *
 * @package WordPress
 */

add_action('template_redirect', function () {
  // Check if the _s query parameter exists and has a hyphen or trailing slash
  if (isset($_GET['_s']) && (strpos($_GET['_s'], '-') !== false || strpos($_GET['_s'], "'") !== false || substr($_GET['_s'], -1) === '/')) {
    $search = $_GET['_s'];

    // Remove trailing slash, if present
    $search = rtrim($search, '/');

    // Replace hyphens with spaces
    $search = str_replace('-', ' ', $search);

    // Encode apostrophes as %27
    $search = str_replace("'", '%27', $search);

    // Build the corrected query string
    $corrected_url = '/shop/?_s=' . rawurlencode($search);

    // Check if the current URL already matches the corrected URL to avoid loops
    if ($_SERVER['REQUEST_URI'] !== $corrected_url) {
      wp_redirect($corrected_url, 301);
      exit;
    }
  }
});

/**
 * ACF Save json files
 */
add_filter('acf/settings/save_json', 'my_acf_json_save_point');
function my_acf_json_save_point($path) {
  $path = get_stylesheet_directory() . '/acf-json';
  return $path;
}

/**
 * Allow SVG's through WP media uploader
 */
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  $mimes['zip'] = 'application/zip';
  $mimes['gz'] = 'application/x-gzip';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * Use ACF options site wide
 */
add_filter('timber_context', 'mytheme_timber_context');
function mytheme_timber_context($context) {
  $context['options'] = get_fields('option');
  $context['password_protected'] = post_password_required();
  return $context;
}

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

/**
 * Change Term Description.
 */
remove_filter('term_description','wpautop');

/**
 * Add excerpt to pages.
 */
add_post_type_support( 'page', 'excerpt' );

/**
 * Disable Woocommerce default css.
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Remove woocommerce tags and categories.
 */
add_action('init', function() {
  register_taxonomy('product_tag', 'product', [
    'public'            => false,
    'show_ui'           => false,
    'show_admin_column' => false,
    'show_in_nav_menus' => false,
    'show_tagcloud'     => false,
  ]);
  register_taxonomy('product_cat', 'product', [
    'label'             => 'Product Category',
    'hierarchical'      => true,
    'public'            => false,
    'show_ui'           => true,
    'show_admin_column' => false,
    'show_in_nav_menus' => false,
  ]);
}, 100);

add_action( 'admin_init' , function() {
  add_filter('manage_product_posts_columns', function($columns) {
    unset($columns['product_tag']);
    unset($columns['product_cat']);
    return $columns;
  }, 100);
});

/**
 * Update main query post order.
 */
add_action( 'pre_get_posts', 'change_posts_order' );
function change_posts_order($query) {
  if ($query->is_main_query()) {
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
  }
}

