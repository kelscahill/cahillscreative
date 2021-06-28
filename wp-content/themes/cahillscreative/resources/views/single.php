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

$term = get_the_category($post->ID);
if ($term) {
  $context['term'] = $term[0];
  $related_blog_posts = array(
    'post_type' => 'post',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'order' => 'DESC',
    'post__not_in' => array($post->ID),
    'tax_query'      => array(
      array(
        'taxonomy' => 'category',
        'field'    => 'id',
        'terms'    => $term[0]->term_id,
      ),
    ),
  );
  $context['related_blog_posts'] = Timber::query_posts($related_blog_posts);
}

Timber::render(array(
  '05-pages/post-types/single-' . $post->ID . '.twig',
  '05-pages/post-types/single-' . $post->post_type . '.twig',
  '05-pages/post-types/single.twig'
), $context);
