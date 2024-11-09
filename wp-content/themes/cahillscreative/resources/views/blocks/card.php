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
$post = Timber::get_post($query->post);
$context['card'] = $post;

$templates = array(
  '/resources/views/patterns/02-molecules/cards/card-' . $post->post_type . '.twig',
  '/resources/views/patterns/02-molecules/cards/card.twig',
  get_stylesheet_directory() . '/resources/views/patterns/02-molecules/cards/card-' . $post->post_type . '.twig',
  get_stylesheet_directory() . '/resources/views/patterns/02-molecules/cards/card.twig',
);
Timber::render( $templates, $context );
