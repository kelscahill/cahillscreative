<?php 
  $id = get_queried_object_id();
  if (is_tag()) {
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
         'terms' => get_cat_name($id)
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
  <?php echo $__env->make('patterns.section__hero', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <?php /* @include('patterns.section__filter') */ ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article <?php (post_class('article spacing--double')); ?>>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">Recent Posts</h2>
          <hr class="divider center-block" />
          <?php if(get_field('intro')): ?>
            <p class="page-intro"><?php echo e(get_field('intro')); ?></p>
          <?php endif; ?>
          <?php /* <div id="response" class="filter-response"></div> */ ?>
          <?php if($posts->have_posts()): ?>
            <div class="grid grid--full">
              <?php while($posts->have_posts()): ?> <?php ($posts->the_post()); ?>
                <?php 
                  $post_id = get_the_ID();
                  $title = get_the_title($post_id);
                  $excerpt = get_the_excerpt($post_id);
                  $thumb_id = get_post_thumbnail_id($post_id);
                  $thumb_size = 'square';
                  $link = get_permalink($post_id);
                  $date = date('F j, Y', strtotime(get_the_date($post_id)));
                  $post_type = get_post_type($post_id);
                  if ($post_type == 'affiliate') {
                    $kicker = 'Shop';
                  } else {
                    $kicker = get_the_category($post_id)[0]->name;
                  }
                 ?>
                <div class="grid-item">
                  <?php echo $__env->make('patterns.block', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                </div>
              <?php endwhile; ?>
              <?php (wp_reset_query()); ?>
            </div>
            <?php  echo do_shortcode('[ajax_load_more container_type="div" css_classes="spacing--double" post_type="post" scroll="false" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');  ?>
          <?php else: ?>
            <p><?php echo e(__('Sorry, no results were found.', 'sage')); ?></p>
            <?php echo get_search_form(false); ?>

          <?php endif; ?>
        </div>
      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>