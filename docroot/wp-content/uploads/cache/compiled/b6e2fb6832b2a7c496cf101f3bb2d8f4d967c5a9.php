<!doctype html>
<html <?php  language_attributes()  ?>>
  <?php echo $__env->make('partials.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <body id="top" <?php  body_class('body-party page-birthday-party')  ?>>
    <?php  do_action('get_header')  ?>
    <main class="main" role="document">
      <div class="layout-container">
        <?php while(have_posts()): ?> <?php  the_post()  ?>
          <article <?php  post_class('article spacing--double')  ?>>
            <div class="article__title">
              <?php if(get_field('display_title')): ?>
                <h1 class="page-title color--white"><?php echo e(get_field('display_title')); ?></h1>
              <?php endif; ?>
            </div>
            <div class="article__buttons spacing">
              <a href="/birthday-party/invite" class="btn btn--red">Of Course!</a>
              <a href="/birthday-party/decline" class="btn btn--outline">Nah, secrets don&rsquo;t make friends</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    </main>
    <?php  do_action('get_footer')  ?>
    <?php  wp_footer()  ?>
  </body>
</html>
