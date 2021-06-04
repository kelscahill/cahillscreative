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

$context                         = Timber::context();
$context['accordion']['heading'] = get_field( 'accordion_heading' );
$context['accordion']['items']   = get_field( 'accordion_items' );

$templates = array(
	'patterns/02-molecules/components/accordion/accordion.twig',
);
Timber::render( $templates, $context );
