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
$context['cards']['kicker'] = get_field( 'cards_kicker' );
$context['cards']['title'] = get_field( 'cards_title' );
$context['cards']['description'] = get_field( 'cards_description' );
$context['cards']['button'] = get_field( 'cards_button' );
$context['cards']['items'] = get_field( 'cards' );

$templates = array(
  get_stylesheet_directory() . '/views/patterns/03-organisms/sections/cards/cards.twig',
);
Timber::render( $templates, $context );