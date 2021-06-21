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
$context['cards']['header'] = get_field( 'cards_header' );
$context['cards']['items'] = get_field( 'cards_items' );

$templates = array(
  get_stylesheet_directory() . '/views/patterns/03-organisms/sections/cards/cards.twig',
);
Timber::render( $templates, $context );