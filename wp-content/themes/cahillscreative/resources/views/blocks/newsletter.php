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
// $context['newsletter']['heading'] = get_field( 'accordion_heading' );
// $context['newsletter']['des'] = get_field( 'accordion_items' );

$templates = array(
  get_stylesheet_directory() . '/views/patterns/02-molecules/components/newsletter-signup/newsletter-signup-banner.twig',
);
Timber::render( $templates, $context );