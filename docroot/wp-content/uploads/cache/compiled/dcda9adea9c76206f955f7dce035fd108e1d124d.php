<!doctype html>
<html <?php  language_attributes()  ?>>
  <?php echo $__env->make('partials.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <body id="top" <?php  body_class('body-party page-birthday-party__rsvp')  ?>>
    <?php  do_action('get_header')  ?>
    <main class="main" role="document">
      <div class="layout-container">
        <?php while(have_posts()): ?> <?php  the_post()  ?>
          <article <?php  post_class('article spacing--double')  ?>>
            <div class="article__title spacing--half">
              <?php if(get_field('display_title')): ?>
                <h2 class="page-title color--white"><?php echo e(get_field('display_title')); ?></h2>
              <?php endif; ?>
              <?php if(get_field('intro')): ?>
                <?php echo e(the_field('intro')); ?>

              <?php endif; ?>
            </div>
            <div class="article__body">
              <?php  the_content()  ?>
              <p class="space--half-top">Have a question? Email me at <a href="mailto:kelscahill@gmail.com">kelscahill@gmail.com</a></p>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    </main>
    <?php  do_action('get_footer')  ?>
    <?php  wp_footer()  ?>
  </body>
</html>
