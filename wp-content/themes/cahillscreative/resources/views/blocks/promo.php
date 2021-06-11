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

$context          = Timber::context();
$context['promo'] = get_field( 'promo' );

$templates = array(
	get_stylesheet_directory() . '/views/patterns/03-organisms/sections/promo/promo.twig',
);
Timber::render( $templates, $context );
