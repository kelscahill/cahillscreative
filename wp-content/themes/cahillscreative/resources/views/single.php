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

if (is_main_site()) {
  $context['is_main_site'] = true;
}

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
Timber::render(array('04-pages/single-' . $post->ID . '.twig', '04-pages/single-' . $post->post_type . '.twig', '04-pages/single.twig'), $context);
