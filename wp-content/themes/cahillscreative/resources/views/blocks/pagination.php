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
$context['posts'] = new Timber\PostQuery($query);

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/02-molecules/navigation/pagination/pagination.twig',
);
Timber::render( $templates, $context );
