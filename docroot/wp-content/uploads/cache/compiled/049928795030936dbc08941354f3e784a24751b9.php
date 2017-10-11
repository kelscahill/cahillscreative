<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article narrow spacing')); ?>>
      <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      <div class="article__body spacing">
        <?php 
          $thumb_id = get_post_thumbnail_id();
          $image_small = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--s')[0];
          $image_medium = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--m')[0];
          $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
         ?>
        <picture class="block__thumb">
          <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
          <img src="<?php echo e($image_small); ?>" alt="<?php echo e($image_alt); ?>">
        </picture>
        <div class="article__content space--double-top">
          <div class="article__content--left spacing sticky">
            <div class="author-meta spacing--half">
              <div class="author-meta__image round">
                <?php  echo get_avatar(get_the_author_meta( 'ID', 80 ))  ?>
              </div>
              <div class="author-meta__name">
                <?php echo e(get_the_author_meta('first_name')); ?> <?php echo e(get_the_author_meta('last_name')); ?>

              </div>
            </div>
            <hr class="divider" />
            <?php echo $__env->make('partials/entry-meta', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
            <?php echo $__env->make('patterns.share-tools', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
            <?php if(get_field('etsy_link')): ?>
              <a href="<?php echo e(get_field('etsy_link')); ?>" class="btn"><span class="font--primary--xs">Download</span>PDF Plans</a>
            <?php endif; ?>
          </div>
          <div class="article__content--right spacing--double">
            <?php (the_content()); ?>
            <?php echo $__env->make('partials.comments', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
          </div>
        </div>
      </div>
    </article>
    <?php echo $__env->make('partials.sidebar', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  </div>
</section>
