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
if (!empty($block['anchor'])) {
  $context['steps']['anchor'] = $block['anchor'];
}

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/02-molecules/components/steps/steps.twig',
);
Timber::render( $templates, $context );