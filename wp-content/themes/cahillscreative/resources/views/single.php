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

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['is_single'] = true;
Timber::render(array(
  '05-pages/post-types/single-' . $post->ID . '.twig',
  '05-pages/post-types/single-' . $post->post_type . '.twig',
  '05-pages/post-types/single.twig'
), $context);
