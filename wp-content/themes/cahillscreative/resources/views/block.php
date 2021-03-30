<?php
/**
 * The template for displaying blocks in the Ajax Load More plugin
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.1
 */

$context = Timber::get_context();
$post = new TimberPost($query->post);
$context['post'] = $post;
Timber::render('01-molecules/blocks/block.twig', $context);
