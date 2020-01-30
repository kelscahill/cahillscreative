<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */
$id = get_queried_object_id();
if (is_tax()) {
  $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
  $args = array(
    'post_type' => 'post',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'order' => 'DESC',
    'tax_query' => array(
      array(
        'taxonomy' => $term->taxonomy,
        'field' => 'slug',
        'terms' => $term->slug
      )
    )
  );
} elseif (is_tag()) {
  $args = array(
   'post_type' => array(
     'post',
     'affiliate',
   ),
   'posts_per_page' => 12,
   'post_status' => 'publish',
   'order' => 'DESC',
   'tax_query' => array(
     array(
       'taxonomy' => 'post_tag',
       'field' => 'slug',
       'terms' => get_queried_object()->slug
     )
   )
 );
} elseif (is_category()) {
   $args = array(
    'post_type' => 'post',
    'category_name' => get_cat_name($id),
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'order' => 'DESC',
  );
} elseif (is_archive('work')) {
  $args = array(
    'post_type' => 'work',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'order' => 'DESC',
  );
} else {
  $args = array(
    'post_type' => 'post',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'order' => 'DESC',
  );
}

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['posts'] = Timber::get_posts($args);
$context['template'] = 'index';
Timber::render('04-pages/index.twig', $context);
