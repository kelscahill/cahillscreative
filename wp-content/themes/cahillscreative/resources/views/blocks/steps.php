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
$context['steps']['header'] = get_field( 'steps_header' );
$context['steps']['items'] = get_field( 'steps_items' );

$templates = array(
  get_stylesheet_directory() . '/views/patterns/02-molecules/components/steps/steps.twig',
);
Timber::render( $templates, $context );