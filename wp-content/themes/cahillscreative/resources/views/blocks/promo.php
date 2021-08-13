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
$context['promo']['items'] = get_field( 'promo' );
if (!empty($block['anchor'])) {
  $context['promo']['anchor'] = $block['anchor'];
}

$templates = array(
  '/wp-content/themes/cahillscreative/resources/views/patterns/03-organisms/sections/promo/promo.twig',
);
Timber::render( $templates, $context );
