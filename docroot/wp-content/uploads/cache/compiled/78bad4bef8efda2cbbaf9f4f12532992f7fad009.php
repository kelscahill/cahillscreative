<?php $__env->startSection('content'); ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article <?php (post_class('article narrow spacing')); ?>>
        <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        <p><?php echo e(__('Sorry, no results were found.', 'sage')); ?></p>
        <?php echo get_search_form(false); ?>

      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>