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
$product = wc_get_product($post->ID);
$context['product'] = $product;

if (get_the_terms($post->ID, 'affiliate_category')) {
  $term = get_the_terms($post->ID, 'affiliate_category');
} else {
  $term = NULL;
}

if (get_field('related_plan')) {
  // Get the related plan post
  $related_plan = new TimberPost(get_field('related_plan')[0]->ID);
  // Parse the content into blocks
  $blocks = parse_blocks($related_plan->post_content);
  // Loop through each block
  foreach ($blocks as $block) {
    if ($block['blockName'] === 'acf/accordion') {
      acf_setup_meta( $block['attrs']['data'], acf_get_block_id( $block['attrs']['data'] ), true );
      $context['related_plan_accordion_items'] = get_field('accordion_items');
    }
  }
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

