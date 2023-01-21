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
$context['yoast_meta_description'] = substr(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true), 0, 100);

if (get_the_terms($post->ID, 'renovation_category')) {
  $term = get_the_terms($post->ID, 'renovation_category');
} elseif (get_the_terms($post->ID, 'work_tag')) {
  $term = get_the_terms($post->ID, 'work_tag');
} elseif (get_the_category($post->ID)) {
  $term = get_the_category($post->ID);
} else {
  $term = NULL;
}

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

  $related_work = array(
    'post_type' => 'work',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'orderby' => 'rand',
    'order' => 'DESC',
    'post__not_in' => array($post->ID),
    'tax_query'      => array(
      array(
        'taxonomy' => 'work_tag',
        'field'    => 'id',
        'terms'    => wp_get_post_terms($post->ID, 'work_tag', array('fields' => 'ids')),
      ),
    ),
  );
  $context['related_work'] = Timber::query_posts($related_work);
}

Timber::render(array(
  '05-pages/post-types/single-' . $post->ID . '.twig',
  '05-pages/post-types/single-' . $post->post_type . '.twig',
  '05-pages/post-types/single.twig'
), $context);
