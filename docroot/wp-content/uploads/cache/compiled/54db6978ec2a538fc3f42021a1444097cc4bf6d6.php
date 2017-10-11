<div class="block block__latest">
  <a href="<?php echo e($link); ?>" class="block__link">
    <?php if(!empty($thumb_id)): ?>
      <picture class="block__thumb round space--right">
        <img src="<?php echo e($image); ?>" alt="<?php echo e($alt); ?>">
      </picture>
    <?php endif; ?>
    <div class="block__content">
      <div class="block__title font--primary--xs">
        <?php echo e($title); ?>

      </div>
      <div class="block__meta color--gray">
        <?php echo $__env->make('partials.entry-meta', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </a>
</div>
