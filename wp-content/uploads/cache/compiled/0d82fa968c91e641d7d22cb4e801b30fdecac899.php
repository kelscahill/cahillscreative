<div class="page-header spacing text-align--center narrow narrow--m">
  <?php if(get_field('display_title')): ?>
    <h2 class="page-kicker font--primary--s"><?php echo e(the_title()); ?></h2>
    <hr class="divider" />
    <h1 class="page-title"><?php echo e(the_field('display_title')); ?></h1>
  <?php else: ?>
    <h1 class="page-title"><?php echo e(the_title()); ?></h1>
    <hr class="divider" />
  <?php endif; ?>
  <?php if(get_field('intro')): ?>
    <div class="page-intro text-align--center">
      <?php echo e(the_field('intro')); ?>

    </div>
  <?php endif; ?>
</div>
