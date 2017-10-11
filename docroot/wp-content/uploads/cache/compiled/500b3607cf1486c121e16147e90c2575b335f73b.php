<?php 
  $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
  $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
  $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
 ?>
<div class="block background-color--white">
  <a href="<?php echo e($link); ?>" class="block__link">
    <?php if(!empty($thumb_id)): ?>
      <picture class="block__thumb">
        <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
        <img src="<?php echo e($image_small); ?>" alt="<?php echo e($alt); ?>">
      </picture>
    <?php endif; ?>
    <div class="block__content spacing--half">
      <?php if(!empty($kicker)): ?>
        <div class="block__kicker font--primary--xs">
          <?php echo e($kicker[0]->name); ?>

        </div>
      <?php endif; ?>
      <div class="block__title font--primary--m">
        <?php echo e($title); ?>

      </div>
      <div class="block__meta color--gray">
        <?php echo $__env->make('partials.entry-meta', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </a>
  <div class="block__toolbar">
    <div class="block__toolbar--left">
      <div class="block__toolbar-like space--right">
        <span class="icon icon--s space--half-right"><?php echo $__env->make('patterns/icon__like', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
        <span class="font--primary--xs color--gray">
          <?php if(function_exists('wp_ulike')): ?>
            <?php  wp_ulike('get');  ?>
          <?php endif; ?>
        </span>
      </div>
      <div class="block__toolbar-comment space--right">
        <span class="icon icon--s space--half-right"><?php echo $__env->make('patterns/icon__comment', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
        <span class="font--primary--xs color--gray">
          <?php 
            comments_number('0', '1', '%');
           ?>
        </span>
      </div>
    </div>
    <div class="block__toolbar--right">
      <div class="block__toolbar-share">
        <span class="font--primary--xs color--gray">Share</span>
        <span class="icon icon--s space--half-left"><?php echo $__env->make('patterns/icon__share', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
      </div>
    </div>
  </div>
</div>
