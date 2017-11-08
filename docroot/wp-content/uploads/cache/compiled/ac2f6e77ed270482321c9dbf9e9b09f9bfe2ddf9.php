<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article spacing--double')); ?>>
      <div class="article__header spacing text-align--center narrow">
        <h2 class="article__header-kicker font--primary--s">Shop</h2>
        <hr class="divider" />
        <h1 class="article__header-title font--secondary--l"><?php echo e(the_title()); ?></h1>
      </div>
      <div class="article__body narrow narrow--xl">
        <div class="wrap--2-col sticky-parent">
          <div class="article__content shift-left wrap--2-col--small">
            <div class="article__content--left spacing--double sticky shift-left--small">
              <?php echo $__env->make('patterns.share-tools', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              <?php if(get_field('affiliate_link')): ?>
                <a href="<?php echo e(get_field('affiliate_link')); ?>" class="btn btn--outline" target="_blank">Buy now</a>
              <?php endif; ?>
            </div>
            <div class="article__content--right spacing--double shift-right--small">
              <div class="article__image">
                <?php 
                  $thumb_id = get_post_thumbnail_id();
                  $image_small = wp_get_attachment_image_src($thumb_id, 'flex-height--s')[0];
                  $image_medium = wp_get_attachment_image_src($thumb_id, 'flex-height--m')[0];
                  $image_large = wp_get_attachment_image_src($thumb_id, 'flex-height--l')[0];
                  $image_xlarge = wp_get_attachment_image_src($thumb_id, 'flex-height--xl')[0];
                  $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
                 ?>
                <picture class="article__picture">
                  <source srcset="<?php echo e($image_xlarge); ?>" media="(min-width:1100px)">
                  <source srcset="<?php echo e($image_large); ?>" media="(min-width:800px)">
                  <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
                  <img src="<?php echo e($image_small); ?>" alt="<?php echo e($image_alt); ?>">
                </picture>
              </div>
              <?php (the_content()); ?>
              <?php echo $__env->make('patterns.section__pagination', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
            </div> <!-- ./article__content--right -->
          </div> <!-- ./article__content -->
          <?php echo $__env->make('partials.sidebar', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        </div> <!-- ./wrap--2-col -->
        <div class="aricle__mobile-footer">
          <?php if(get_field('affiliate_link')): ?>
            <a href="<?php echo e(get_field('affiliate_link')); ?>" class="btn btn--outline btn--download hide-after--m" target="_blank"><span class="font--primary--xs">Buy now</a>
          <?php endif; ?>
          <div class="article__toolbar block__toolbar">
            <div class="block__toolbar--left">
              <div class="block__toolbar-item block__toolbar-like space--right">
                <?php if(function_exists('wp_ulike')): ?>
                  <?php  wp_ulike('get');  ?>
                <?php endif; ?>
              </div>
              <a href="<?php echo e($link); ?>#comments" class="block__toolbar-item block__toolbar-comment space--right">
                <span class="icon icon--s space--half-right"><?php echo $__env->make('patterns/icon__comment', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
                <span class="font--sans-serif font--sans-serif--small color--gray">
                  <?php 
                    comments_number('0', '1', '%');
                   ?>
                </span>
              </a>
              <div class="block__toolbar-item block__toolbar-share tooltip">
                <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
                  <span class="icon icon--s space--half-left"><?php echo $__env->make('patterns/icon__share', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
                </div>
                <?php echo $__env->make('patterns/share-tooltip', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              </div>
            </div> <!-- ./block__toolbar--left -->
            <div class="block__toolbar--right">
              <?php  $next_post = get_next_post(true, '', 'category');  ?>
              <?php if( !empty($next_post) ): ?>
                <?php  $link = get_permalink($next_post->ID);  ?>
                <a href="<?php echo e($link); ?>" class="font--primary--xs">Next Item<span class="icon icon--s"><?php echo $__env->make('patterns/arrow__carousel', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span></a>
              <?php endif; ?>
            </div> <!-- ./block__toolbar--right -->
          </div> <!-- ./block__toolbar -->
        </div> <!-- ./article__mobile-footer -->
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
<?php echo $__env->make('patterns.section__favorites', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
