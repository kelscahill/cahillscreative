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

$context = Timber::get_context();
$post = Timber::query_post();
$context['post']['kicker'] = 'Blog';
$context['post']['title'] = 'Recent Posts';
$context['post_type'] = 'post';

$args = array(
  'post_type' => 'post',
  'posts_per_page' => 12,
  'post_status' => 'publish',
  'order' => 'DESC',
);
$context['posts'] = Timber::query_posts($args);

Timber::render('04-pages/index.twig', $context);
