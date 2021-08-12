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

if (get_the_terms($post->ID, 'affiliate_category')) {
  $term = get_the_terms($post->ID, 'affiliate_category');
} else {
  $term = NULL;
}

if ($term) {
  $context['term'] = $term[0];

  $related_products_posts = array(
    'post_type' => 'product',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'order' => 'DESC',
    'post__not_in' => array($post->ID),
    'tax_query'      => array(
      array(
        'taxonomy' => 'affiliate_category',
        'field'    => 'id',
        'terms'    => $term[0]->term_id,
      ),
    ),
  );
  $context['related_products'] = Timber::query_posts($related_products_posts);
}

Timber::render(array('05-pages/post-types/single-product.twig'), $context);
