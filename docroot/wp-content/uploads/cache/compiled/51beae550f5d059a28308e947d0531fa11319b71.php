<!doctype html>
<html <?php (language_attributes()); ?>>
  <?php echo $__env->make('partials.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <div class="overlay"></div>
  <body id="top" <?php (body_class()); ?>>
    <?php (do_action('get_header')); ?>
    <?php echo $__env->make('partials.header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <main class="main" role="document">
      <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php (do_action('get_footer')); ?>
    <?php echo $__env->make('partials.footer', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php (wp_footer()); ?>
  </body>
</html>
