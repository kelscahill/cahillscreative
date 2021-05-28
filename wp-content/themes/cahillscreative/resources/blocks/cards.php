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
$context['cards']['heading'] = get_field( 'cards_heading' );
$context['cards']['description'] = get_field( 'cards_description' );
$context['cards']['buttons'] = get_field( 'cards_buttons' );
$context['cards']['items'] = get_field( 'cards' );

$templates = array(
	'views/_patterns/03-organisms/sections/section-cards.twig',
);
Timber::render( $templates, $context );