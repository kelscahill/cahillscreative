<?php
/**
 * The template for displaying blocks in the Ajax Load More plugin
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.1
 */

$context = Timber::context();
$context['posts'] = Timber::get_posts($query);

$templates = array(
  '/resources/views/patterns/02-molecules/navigation/pagination/pagination.twig',
  get_stylesheet_directory() . '/resources/views/patterns/02-molecules/navigation/pagination/pagination.twig',
);
Timber::render( $templates, $context );
