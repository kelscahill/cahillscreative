<?php $__env->startSection('content'); ?>
  <section class="section section__main">
    <div class="layout-container">
      <article <?php (post_class('article narrow--xl center-block spacing--double')); ?>>
      <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        <?php if(have_posts()): ?>
          <?php while(have_posts()): ?> <?php (the_post()); ?>
            <?php 
              $id = get_the_ID();
              $title = get_the_title($id);
              $excerpt = get_the_excerpt($id);
              $thumb_id = get_post_thumbnail_id($id);
              $link = get_permalink($id);
              $date = date('F j, Y', strtotime(get_the_date()));
             ?>
            <?php echo $__env->make('patterns.block', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
          <?php endwhile; ?>
            <?php  echo do_shortcode('[ajax_load_more container_type="div" css_classes="spacing--double" post_type="post, page" scroll="false" transition_container="false" button_label="Load More" posts_per_page="5" offset="5"]');  ?>
        <?php else: ?>
          <p><?php echo e(__('Sorry, no results were found.', 'sage')); ?></p>
          <?php echo get_search_form(false); ?>

        <?php endif; ?>
      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>