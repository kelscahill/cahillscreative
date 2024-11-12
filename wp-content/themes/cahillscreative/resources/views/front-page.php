<?php
/**
 * The template for the home page only.
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();
$post = Timber::get_post();
$context['is_front_page'] = true;

$latest_blog_posts = array(
  'post_type' => 'post',
  'posts_per_page' => 8,
  'post_status' => 'publish',
  'order' => 'DESC',
);
$context['latest_blog_posts'] = Timber::get_posts($latest_blog_posts);

Timber::render(array(
  '05-pages/page-types/page-' . $post->post_name . '.twig',
  '05-pages/page-types/front-page.twig'
), $context);
