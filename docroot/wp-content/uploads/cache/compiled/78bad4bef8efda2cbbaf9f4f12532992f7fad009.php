<?php $__env->startSection('content'); ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article <?php (post_class('article narrow spacing--double')); ?>>
        <div class="page-header spacing text-align--center narrow narrow--m">
          <h2 class="page-kicker font--primary--s">404</h2>
          <hr class="divider">
          <h1 class="page-title">Page Not Found</h1>
          <div class="page-intro">
            <p>We couldn't find the page you were looking for. Please go back to <a href="<?php echo e(home_url()); ?>" class="text-link">home</a> or try the search below.</p>
          </div>
        </div>
        <?php echo get_search_form(false); ?>

      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>