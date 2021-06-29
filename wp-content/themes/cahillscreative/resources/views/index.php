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
$term = get_queried_object();
$context['term'] = $term;
$context['posts'] = new Timber\PostQuery();

if (is_tax('renovation_category')) {
  $context['post']['kicker'] = 'Renovation';
} elseif (is_tax('work_category')) {
  $context['post']['kicker'] = 'Work';
} elseif (is_tax('affiliate_category') || is_tax('affiliate_tag') || is_tax('store')) {
  $context['post']['kicker'] = 'Shop';
} elseif (is_category() || is_tag() || is_tax()) {
  $context['post']['kicker'] = 'Blog';
}

if (is_category() || is_tag() || is_tax()) {
  $context['post']['title'] = $term->name;
  $context['post']['content'] = $term->description;
} else {
  $context['post'] = new TimberPost();
}

$templates = array(
  'patterns/05-pages/page-types/archive.twig',
  'patterns/05-pages/page-types/index.twig',
);
Timber::render( $templates, $context );