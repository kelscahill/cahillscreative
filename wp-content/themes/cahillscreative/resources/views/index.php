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
$post = new TimberPost();
$term = get_queried_object();
$context['post'] = $post;
$context['term'] = $term;
$context['posts'] = new Timber\PostQuery();

if (is_tax('affiliate_category') || is_tax('affiliate_tag') || is_tax('store')) {
 $context['kicker'] = 'Shop';
 $context['title'] = $term->name;
 $context['description'] = $term->description;
} elseif (is_category() || is_tag() || is_tax()) {
  $context['kicker'] = 'Blog';
  $context['title'] = $term->name;
  $context['description'] = $term->description;
}

$templates = array(
  'patterns/05-pages/page-types/archive.twig',
  'patterns/05-pages/page-types/index.twig',
);
Timber::render( $templates, $context );