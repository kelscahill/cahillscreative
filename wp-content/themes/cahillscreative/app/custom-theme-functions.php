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
 * Hook to alter the Woocommerce product_cat taxonomy.
 */
function filter_woocommerce_taxonomy_args_product_cat( $array ) {
  $array = array(
    'label' => 'Categories',
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => true,
  );
  return $array;
};
add_filter( 'woocommerce_taxonomy_args_product_cat', 'filter_woocommerce_taxonomy_args_product_cat', 10, 1 );

/**
 * Hook to alter the Woocommerce product_tag taxonomy.
 */
function filter_woocommerce_taxonomy_args_product_tag( $array ) {
  $array = array(
    'label' => 'Tags',
    'show_admin_column' => true,
    'show_in_rest' => true,
  );
  return $array;
};
add_filter( 'woocommerce_taxonomy_args_product_tag', 'filter_woocommerce_taxonomy_args_product_tag', 10, 1 );
