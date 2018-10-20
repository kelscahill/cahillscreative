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
        <time class="updated color--gray font--s" datetime="<?php echo e(get_post_time('c', true)); ?>"><?php echo e($date); ?></time>
      </div>
    </div>
  </a>
</div>
