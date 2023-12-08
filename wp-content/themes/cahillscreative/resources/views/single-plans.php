<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();
$post = new TimberPost();
$context['post'] = $post;
$context['is_single'] = true;
$context['ads'] = false;

Timber::render(array('05-pages/post-types/single-plans.twig'), $context);

