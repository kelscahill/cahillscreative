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

$id = get_queried_object_id();
$context = Timber::context();
$post = new TimberPost();
$context['post'] = $post;
$context['product'] = wc_get_product($id);

$related_products_args = array(
  'post__not_in' => array($id),
  'post_type' => 'product',
  'posts_per_page' => 4,
  'post_status' => 'publish',
  'tax_query' => array(
    array(
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => get_the_terms($id ,'product_cat')[0]->name,
    )
  )
);
$context['related_products'] = Timber::query_posts($related_products_args);

Timber::render(array('05-pages/single-product.twig'), $context);
