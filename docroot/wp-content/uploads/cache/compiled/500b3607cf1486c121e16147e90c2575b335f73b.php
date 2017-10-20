<?php 
  $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
  $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
  $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
 ?>
<div class="block block__post background-color--white">
  <a href="<?php echo e($link); ?>" class="block__link spacing">
    <?php if(!empty($thumb_id)): ?>
      <picture class="block__thumb">
        <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
        <img src="<?php echo e($image_small); ?>" alt="<?php echo e($alt); ?>">
      </picture>
    <?php endif; ?>
    <div class="block__content spacing--half">
      <?php if(!empty($kicker)): ?>
        <div class="block__kicker font--primary--xs color--gray">
          <?php echo e($kicker[0]->name); ?>

        </div>
      <?php endif; ?>
      <div class="block__title font--primary--m color--black">
        <?php echo e($title); ?>

      </div>
      <div class="block__meta color--gray">
        <?php echo $__env->make('partials.entry-meta', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </a>
  <div class="block__toolbar">
    <div class="block__toolbar--left">
      <div class="block__toolbar-item block__toolbar-like space--right">
        <?php if(function_exists('wp_ulike')): ?>
          <?php  wp_ulike('get');  ?>
        <?php endif; ?>
      </div>
      <a href="<?php echo e($link); ?>#comments" class="block__toolbar-item block__toolbar-comment space--right">
        <span class="icon icon--s space--half-right"><?php echo $__env->make('patterns/icon__comment', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
        <span class="font--sans-serif font--sans-serif--small color--gray">
          <?php 
            comments_number('0', '1', '%');
           ?>
        </span>
      </a>
    </div>
    <div class="block__toolbar--right tooltip">
      <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
        <span class="font--primary--xs color--gray">Share</span>
        <span class="icon icon--s space--half-left"><?php echo $__env->make('patterns/icon__share', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
      </div>
      <div class="block__toolbar-share-tooltip tooltip-wrap">
        <div class="tooltip-item font--primary--xs text-align--center color--gray">Share Post</div>
        <div data-title="<?php echo e($title); ?>" data-image="<?php echo e($image_small); ?>" data-description="<?php echo e($excerpt); ?>" data-url="<?php echo e($link); ?>" data-network="facebook" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__facebook', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Facebook</div>
        <div data-title="<?php echo e($title); ?>" data-image="<?php echo e($image_small); ?>" data-description="<?php echo e($excerpt); ?>" data-url="<?php echo e($link); ?>" data-network="twitter" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__twitter', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Twitter</div>
        <div data-title="<?php echo e($title); ?>" data-image="<?php echo e($image_small); ?>" data-description="<?php echo e($excerpt); ?>" data-url="<?php echo e($link); ?>" data-network="pinterest" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__pinterest', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Pinterest</div>
        <div data-title="<?php echo e($title); ?>" data-image="<?php echo e($image_small); ?>" data-description="<?php echo e($excerpt); ?>" data-url="<?php echo e($link); ?>" data-network="linkedin" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__linkedin', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>LinkedIn</div>
        <div data-title="<?php echo e($title); ?>" data-image="<?php echo e($image_small); ?>" data-description="<?php echo e($excerpt); ?>" data-url="<?php echo e($link); ?>" data-network="email" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__email', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Email</div>
        <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
      </div>
    </div>
  </div>
</div>
