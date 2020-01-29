<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */
$args = array(
  'numberposts' => -1,
  'post_type' => 'menus',
  'orderby' => 'menu_order',
  'order' => 'ASC'
);

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
if ($post->post_type == 'menus') {
  $context['menus'] = Timber::get_posts($args);
}
Timber::render(array('04-pages/single-' . $post->ID . '.twig', '04-pages/single-' . $post->post_type . '.twig', '04-pages/single.twig'), $context);
