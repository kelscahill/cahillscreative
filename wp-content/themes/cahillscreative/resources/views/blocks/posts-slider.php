<?php
/**
 * The template for displaying posts slider blocks
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.1
 */

$context = Timber::context();
$context['posts_slider']['header'] = get_field( 'posts_slider_header' );
$context['posts_slider']['posts'] = get_field( 'posts_slider_posts' );
if (!empty($block['anchor'])) {
  $context['posts_slider']['anchor'] = $block['anchor'];
}

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/03-organisms/sections/feeds/posts-slider.twig',
);
Timber::render( $templates, $context );
