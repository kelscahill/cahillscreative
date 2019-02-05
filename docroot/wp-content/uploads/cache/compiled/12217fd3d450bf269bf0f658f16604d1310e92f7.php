<?php 
  $args = array(
   'post_type' => array(
     'affiliate',
   ),
   'posts_per_page' => 12,
   'post_status' => 'publish',
   'order' => 'DESC',
   'tax_query' => array(
     array(
       'taxonomy' => 'post_tag',
       'field' => 'slug',
       'terms' => 'supplements'
     )
   )
 );
 $posts = new WP_Query($args);

 ?>

<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('patterns.section--hero', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article <?php  post_class('article spacing--double')  ?>>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">Recent Items</h2>
          <hr class="divider center-block" />
          <?php if($posts->have_posts()): ?>
            <div class="grid grid--full">
              <?php while($posts->have_posts()): ?> <?php  $posts->the_post()  ?>
                <?php echo $__env->make('patterns.block', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              <?php endwhile; ?>
              <?php  wp_reset_query()  ?>
            </div>
            <?php  echo do_shortcode('[ajax_load_more container_type="div" tag="supplements" post_type="affiliate" scroll="true" transition_container="false" button_label="Load More Items" posts_per_page="12" offset="12"]');  ?>
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