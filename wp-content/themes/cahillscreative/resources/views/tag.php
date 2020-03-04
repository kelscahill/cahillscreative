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
$context = Timber::get_context();
$context['post']['kicker'] = "Tag";
$context['post']['title'] = get_term($id)->name;
$context['tag'] = get_term($id)->name;

$args = array(
  'posts_per_page' => 12,
  'post_status' => 'publish',
  'order' => 'DESC',
  'tax_query' => array(
    array(
      'taxonomy' => 'post_tag',
      'field' => 'slug',
      'terms' => get_term($id)->name
    )
  )
);
$context['posts'] = Timber::query_posts($args);

Timber::render('04-pages/index.twig', $context);
