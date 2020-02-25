<?php 
  $id = get_queried_object_id();
  if (is_tax()) {
    $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
    $args = array(
      'post_type' => 'post',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
      'tax_query' => array(
        array(
          'taxonomy' => $term->taxonomy,
          'field' => 'slug',
          'terms' => $term->slug
        )
      )
    );
  } elseif (is_tag()) {
    $args = array(
     'post_type' => array(
       'post',
       'affiliate',
     ),
     'posts_per_page' => 12,
     'post_status' => 'publish',
     'order' => 'DESC',
     'tax_query' => array(
       array(
         'taxonomy' => 'post_tag',
         'field' => 'slug',
         'terms' => get_queried_object()->slug
       )
     )
   );
  } elseif (is_category()) {
     $args = array(
      'post_type' => 'post',
      'category_name' => get_cat_name($id),
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
    );
  } elseif (is_archive('work')) {
    $args = array(
      'post_type' => 'work',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
    );
  } else {
    $args = array(
      'post_type' => 'post',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
    );
  }
  $posts = new WP_Query($args);
 ?>

<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('patterns.section--hero', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <?php  /* <?php echo $__env->make('patterns.section--filter', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?> */  ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article <?php  post_class('article spacing--double')  ?>>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">
            <?php if(is_tax('work')): ?>
              Recent Work
            <?php else: ?>
              Recent Posts
            <?php endif; ?>
          </h2>
          <hr class="divider center-block" />
          <?php if(get_field('intro')): ?>
            <p class="page-intro"><?php echo e(get_field('intro')); ?></p>
          <?php endif; ?>
          <?php /* <div id="response" class="filter-response"></div> */ ?>
          <?php if($posts->have_posts()): ?>
            <div class="grid grid--full">
              <?php while($posts->have_posts()): ?> <?php  $posts->the_post()  ?>
                <?php echo $__env->make('patterns.block', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              <?php endwhile; ?>
              <?php  wp_reset_query()  ?>
            </div>
            <?php 
              if (is_tag()) {
                echo do_shortcode('[ajax_load_more tag="' .get_the_category()[0]->slug .'" container_type="div" post_type="post, affiliate" scroll="false" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } elseif (is_category()) {
                echo do_shortcode('[ajax_load_more category="' . get_the_category()[0]->slug .'" container_type="div" post_type="post" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } elseif (is_archive('work')) {
                echo do_shortcode('[ajax_load_more container_type="div" post_type="work" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } else {
                echo do_shortcode('[ajax_load_more container_type="div" post_type="post" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              }
             ?>
          <?php else: ?>
            <p><?php echo e(__('Sorry, no posts were found.', 'sage')); ?></p>
            <?php echo get_search_form(false); ?>

          <?php endif; ?>
        </div>
      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>