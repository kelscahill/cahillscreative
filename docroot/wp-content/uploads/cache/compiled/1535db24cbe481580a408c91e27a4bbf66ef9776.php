<!doctype html>
<html <?php (language_attributes()); ?>>
  <?php echo $__env->make('partials.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <body id="top" <?php (body_class('page-birthday-party')); ?>>
    <?php (do_action('get_header')); ?>
    <main class="main" role="document">
      <div class="layout-container">
        <article <?php (post_class('article spacing--double')); ?>>
          <?php if(get_field('display_title')): ?>
            <h1 class="page-title color--white"><?php echo e(get_field('display_title')); ?></h1>
          <?php endif; ?>
          <a href="/birthday-party/invite" class="btn btn--red">Of Course!</a>
          <a href="/birthday-party/decline" class="btn btn--outline">Nah, secrets don't make friends</a>
        </article>
      </div>
    </main>
    <?php (do_action('get_footer')); ?>
    <?php (wp_footer()); ?>
  </body>
</html>
