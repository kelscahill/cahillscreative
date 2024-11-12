<?php
/**
 * The template for displaying cards blocks
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.1
 */

$context = Timber::context();

$templates = array(
  '/resources/views/patterns/02-molecules/components/newsletter-signup/newsletter-signup-banner.twig',
  get_stylesheet_directory() . '/resources/views/patterns/02-molecules/components/newsletter-signup/newsletter-signup-banner.twig',
);
Timber::render( $templates, $context );