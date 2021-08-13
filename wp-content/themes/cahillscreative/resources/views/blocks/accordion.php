<?php
/**
 * The template for displaying accordion blocks
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.1
 */

$context = Timber::context();
$context['accordion']['header'] = get_field( 'accordion_header' );
$context['accordion']['items'] = get_field( 'accordion_items' );
$context['accordion']['expanded'] = get_field( 'accordion_expanded' );
if (!empty($block['anchor'])) {
  $context['accordion']['anchor'] = $block['anchor'];
}

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/02-molecules/components/accordion/accordion.twig',
);
Timber::render( $templates, $context );
