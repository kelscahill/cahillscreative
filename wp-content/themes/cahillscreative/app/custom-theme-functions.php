<?php
/**
 *
 * @file
 * Register custom theme functions.
 *
 * @package WordPress
 */

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
 * ACF Save json files
 */
function my_acf_json_save_point($path) {
  $path = get_stylesheet_directory() . '/acf-json';
  return $path;
}
add_filter('acf/settings/save_json', 'my_acf_json_save_point');

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