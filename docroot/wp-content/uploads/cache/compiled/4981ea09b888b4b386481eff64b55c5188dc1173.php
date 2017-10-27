<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article')); ?>>
      <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      <div class="article__body spacing">
        <?php 
          $thumb_id = get_post_thumbnail_id();
          $image_small = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--s')[0];
          $image_medium = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--m')[0];
          $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
         ?>
        <picture class="block__thumb narrow narrow--l">
          <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
          <img src="<?php echo e($image_small); ?>" alt="<?php echo e($image_alt); ?>">
        </picture>
        <div class="narrow narrow--m">
          <?php (the_content()); ?>
        </div>
      </div>
    </article>
  </div>
</section>
