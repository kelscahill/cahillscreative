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

$context = Timber::get_context();
$post = new TimberPost($query->post);
$context['card'] = $post;

if ($post->post_type == 'product' || $post->post_type == 'affiliate') {
  $template = 'product';
} else {
  $template = $post->post_type;
}

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/02-molecules/cards/card-' . $template . '.twig',
  '/wp-content/themes/cahillscreative/resources/views/patterns/02-molecules/cards/card.twig',
  get_stylesheet_directory() . '/views/patterns/02-molecules/cards/card-' . $template . '.twig',
  get_stylesheet_directory() . '/views/patterns/02-molecules/cards/card.twig',
);
Timber::render( $templates, $context );
