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
$context['post']['kicker'] = "Blog";
$context['post']['title'] = get_cat_name($id);
$context['post']['intro'] = get_queried_object()->description;
$context['post_type'] = 'post';
$context['category'] = get_queried_object()->slug;
$context['posts'] = Timber::query_posts();

Timber::render('05-pages/page-types/index.twig', $context);
