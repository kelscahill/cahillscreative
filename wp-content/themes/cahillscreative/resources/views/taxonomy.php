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
$context['post']['kicker'] = "Tag";
$context['post']['title'] = get_queried_object()->name;
$context['tag'] = get_queried_object()->slug;
$context['category'] = get_query_var('taxonomy');
$context['posts'] = Timber::query_posts();
Timber::render('05-pages/page-types/index.twig', $context);