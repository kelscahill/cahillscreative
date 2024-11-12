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

$templates = array(
  '/resources/views/patterns/01-atoms/text/disclaimer/disclaimer.twig',
  get_stylesheet_directory() . '/resources/views/patterns/01-atoms/text/disclaimer/disclaimer.twig',
);
Timber::render( $templates, $context );
