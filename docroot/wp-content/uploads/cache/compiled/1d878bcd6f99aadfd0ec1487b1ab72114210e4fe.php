<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('patterns.section__hero', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <?php while(have_posts()): ?> <?php (the_post()); ?>
    <section class="section section__main">
      <div class="layout-container section__main--inner">
        <article <?php (post_class('article')); ?>>
          <div class="article__header text-align--center">
            <h1 class="font--primary--xl">Welcome to Cahill's Creative</h1>
            <hr class="divider" />
          </div>
          <div class="article__body narrow narrow--m spacing">
            <?php (the_content()); ?>
          </div>
        </article>
      </div>
    </section>
    <section class="section section__featured-about">
      <div class="grid grid--50-50">
        <div class="grid-item">
          <p>I currently reside in Wayne County, PA (Beach Lake, to be exact), but offer my graphic design, branding, and website services to many other areas, including Scranton, Milford, Pike County and the Poconos.</p>
          <p>I specialize in all aspects of branding and website design, and use my experience in graphic design to help clients bring their brand and ideas to life. I enjoy working with small businesses and any individual that’s as passionate about what they do as I am.</p>
          <p>When I’m not working with clients, I enjoy spending time on my own side projects. This helps me stay creatively energized while giving me new challenges to overcome. If you’d like to see some of my recent side work, check out the woodworking section of my portfolio.</p>
          <a href="/about">Learn More</a>
        </div>
        <div class="grid-item">
          <picture class="round">
            <img src="" alt="Kelsey Cahill" />
          </picture>
        </div>
      </div>
    </section>
    <?php 
      $args = array(
        'post_type' => 'post',
        'posts_per_page' => 4,
        'post_status' => 'publish',
        'order' => 'DESC',
      );
      $featured_posts = new WP_Query($args);
     ?>
    <?php if($featured_posts): ?>
      <section class="section section__featured-posts layout-container padding--double-top padding--double-bottom">
        <div class="grid grid--full">
          <?php while($featured_posts->have_posts()): ?> <?php ($featured_posts->the_post()); ?>
            <?php echo $__env->make('patterns.block', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
          <?php endwhile; ?>
          <?php (wp_reset_query()); ?>
        </div>
      </section>
    <?php endif; ?>
    <?php 
      $args = array(
        'post_type' => 'work',
        'posts_per_page' => 2,
        'post_status' => 'publish',
        'order' => 'DESC',
      );
      $featured_work = new WP_Query($args);
     ?>
    <?php if($featured_work): ?>
      <section class="section section__featured-work layout-container">
        <div class="grid grid--full">
          <?php while($featured_work->have_posts()): ?> <?php ($featured_work->the_post()); ?>
            <?php echo $__env->make('patterns.block__featured', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
          <?php endwhile; ?>
          <?php (wp_reset_query()); ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endwhile; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>