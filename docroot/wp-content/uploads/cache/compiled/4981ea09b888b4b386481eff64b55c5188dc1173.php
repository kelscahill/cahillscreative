<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article spacing--double')); ?>>
      <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      <div class="article__body spacing narrow narrow--xl">
        <?php (the_content()); ?>
      </div>
    </article>
    <?php echo $__env->make('partials.sidebar', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  </div>
</section>
