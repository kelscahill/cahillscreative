<?php $gallery = get_field('gallery') ?>
<?php if($gallery): ?>
  <div class="article__gallery gallery slick-gallery">
    <?php $__currentLoopData = $gallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="gallery__image">
        <picture class="gallery__picture">
          <source srcset="<?php echo e($image['sizes']['flex-height--l']); ?>" media="(min-width:650px)">
          <source srcset="<?php echo e($image['sizes']['flex-height--m']); ?>" media="(min-width:400px)">
          <img src="<?php echo e($image['sizes']['flex-height--s']); ?>" alt="<?php echo e($image['alt']); ?>">
        </picture>
        <?php if($image['caption']): ?>
          <div class="gallery__caption">
            <?php echo e($image['caption']); ?>

          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
<?php else: ?>
  <div class="article__image">
    <?php
      $thumb_id = get_post_thumbnail_id();
      $image_small = wp_get_attachment_image_src($thumb_id, 'flex-height--s')[0];
      $image_medium = wp_get_attachment_image_src($thumb_id, 'flex-height--m')[0];
      $image_large = wp_get_attachment_image_src($thumb_id, 'flex-height--l')[0];
      $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
    ?>
    <picture class="article__picture">
      <source srcset="<?php echo e($image_large); ?>" media="(min-width:650px)">
      <source srcset="<?php echo e($image_medium); ?>" media="(min-width:400px)">
      <img src="<?php echo e($image_small); ?>" alt="<?php echo e($image_alt); ?>">
    </picture>
  </div>
<?php endif; ?>
