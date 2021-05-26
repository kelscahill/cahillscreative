<?php
/**
 *
 * @file
 * Register custom theme functions.
 *
 * @package WordPress
 */

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
 * Change Term Description
 */
remove_filter('term_description','wpautop');

/**
 * Add excerpt to pages
 */
add_post_type_support( 'page', 'excerpt' );