<!doctype html>
<html <?php  language_attributes()  ?>>
  <?php echo $__env->make('partials.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <body id="top" <?php  body_class('body-party page-birthday-party__invite')  ?>>
    <?php  do_action('get_header')  ?>
    <main class="main" role="document">
      <div class="layout-container">
        <?php while(have_posts()): ?> <?php  the_post()  ?>
          <div class="article__alert">
            <small>Welcome to the circle of trust</small>
            <p>It&rsquo;s a surprise, so don&rsquo;t blow it!</p>
          </div>
          <article <?php  post_class('article spacing--double')  ?>>
            <div class="article__title spacing--half">
              <?php if(get_field('display_title')): ?>
                <h1 class="page-title color--white"><?php echo e(get_field('display_title')); ?></h1>
              <?php endif; ?>
              <?php if(get_field('intro')): ?>
                <?php echo e(the_field('intro')); ?>

              <?php endif; ?>
            </div>

            <div class="article__body">
              <?php  the_content()  ?>
              <div class="person">
                <div class="person-row">
                  <span class="kicker">30</span>
                  <h3>Bryan Ploransky</h3>
                  <p>January 6th 1989</p>
                </div>
                <div class="person-row">
                  <span class="kicker">60</span>
                  <h3>George Ploransky</h3>
                  <p>January 24th 1969</p>
                </div>
                <div class="person-row">
                  <span class="kicker">30</span>
                  <h3>Travis Cahill</h3>
                  <p>January 29th 1989</p>
                </div>
              </div>
            </div>

            <div class="article__details">
              <div class="article__details--row">
                <h4>Location</h4>
                <p>Capri Restaurant<br>
                  447 Lakeshore Dr<br>
                  Lakeville, PA 18438
                </p>
              </div>
              <div class="article__details--row">
                <h4>Date</h4>
                <p>Saturday, January 5 2019</p>
              </div>
              <div class="article__details--row">
                <h4>Time</h4>
                <p>Please arrive at 5pm</p>
              </div>
            </div>
            <div class="article__buttons spacing">
              <a href="/birthday-party/rsvp" class="btn btn--red">Rsvp Now</a>
              <a href="/birthday-party" class="btn btn--outline">Decide Later</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    </main>
    <?php  do_action('get_footer')  ?>
    <?php  wp_footer()  ?>
  </body>
</html>
