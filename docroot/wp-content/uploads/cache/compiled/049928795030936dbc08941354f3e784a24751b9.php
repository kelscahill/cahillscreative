<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article spacing--double')); ?>>
      <div class="article__header spacing text-align--center narrow">
        <h2 class="article__header-kicker font--primary--s">Blog</h2>
        <hr class="divider" />
        <h1 class="article__header-title font--secondary--l"><?php echo e(the_title()); ?></h1>
      </div>
      <?php 
        $project = get_the_terms($post->ID, 'project');
        $room = get_the_terms($post->ID, 'room');
        $cost = get_the_terms($post->ID, 'cost');
        $skill = get_the_terms($post->ID, 'skill_level');
       ?>
      <?php if($project || $room || $cost || $skill): ?>
        <div class="article__categories narrow">
          <?php  $project = get_the_terms($post->ID, 'project');  ?>
          <?php if($project): ?>
            <div class="article__category">
              <span class="font--primary--xs">Project</span>
              <?php $__currentLoopData = $project; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(home_url('/')); ?><?php echo e($term->taxonomy); ?>/<?php echo e($term->slug); ?>"><?php echo e($term->name); ?></a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
          <?php  $room = get_the_terms($post->ID, 'room');  ?>
          <?php if($room): ?>
            <div class="article__category">
              <span class="font--primary--xs">Room</span>
              <?php $__currentLoopData = $room; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(home_url('/')); ?><?php echo e($term->taxonomy); ?>/<?php echo e($term->slug); ?>"><?php echo e($term->name); ?></a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
          <?php  $cost = get_the_terms($post->ID, 'cost');  ?>
          <?php if($cost): ?>
            <div class="article__category">
              <span class="font--primary--xs">Cost</span>
              <?php $__currentLoopData = $cost; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(home_url('/')); ?><?php echo e($term->taxonomy); ?>/<?php echo e($term->slug); ?>"><?php echo e($term->name); ?></a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
          <?php  $skill = get_the_terms($post->ID, 'skill_level');  ?>
          <?php if($skill): ?>
            <div class="article__category">
              <span class="font--primary--xs">Skill Level</span>
              <?php $__currentLoopData = $skill; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(home_url('/')); ?><?php echo e($term->taxonomy); ?>/<?php echo e($term->slug); ?>"><?php echo e($term->name); ?></a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="article__gallery narrow narrow--xl">
        <?php echo $__env->make('patterns.section__gallery', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
      <div class="article__body narrow narrow--xl">
        <div class="wrap--2-col sticky-parent">
          <div class="article__content shift-left wrap--2-col--small">
            <div class="article__content--left spacing--double sticky shift-left--small">
              <div class="author-meta spacing--half">
                <div class="author-meta__image round center-block">
                  <?php  echo get_avatar( get_the_author_meta( 'ID' ), 80 );  ?>
                </div>
                <div class="author-meta__name font--primary--xs">
                  <?php echo e(get_the_author_meta('first_name')); ?> <?php echo e(get_the_author_meta('last_name')); ?>

                </div>
                <hr class="divider" />
                <?php echo $__env->make('partials/entry-meta', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              </div>
              <?php echo $__env->make('patterns.share-tools', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              <?php if(get_field('etsy_link')): ?>
                <a href="<?php echo e(get_field('etsy_link')); ?>" class="btn btn--outline" target="_blank"><span class="font--primary--xs">Download</span><font>PDF Plans</font></a>
              <?php endif; ?>
            </div>
            <div class="article__content--right spacing--double shift-right--small">
              <?php (the_content()); ?>
              <?php echo $__env->make('patterns.section__accordion', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
              <?php echo $__env->make('patterns.section__pagination', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
            </div>
          </div> <!-- ./article__content -->
          <?php echo $__env->make('partials.sidebar', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        </div> <!-- ./wrap--2-col -->
        <div class="aricle__mobile-footer">
          <?php if(get_field('etsy_link')): ?>
            <a href="<?php echo e(get_field('etsy_link')); ?>" class="btn btn--outline btn--download hide-after--m" target="_blank"><span class="font--primary--xs">Download</span><font>PDF Plans</font></a>
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
                <a href="<?php echo e($link); ?>" class="font--primary--xs">Next Post<span class="icon icon--xs"><?php echo $__env->make('patterns/arrow__carousel', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span></a>
              <?php endif; ?>
            </div> <!-- ./block__toolbar--right -->
          </div> <!-- ./block__toolbar -->
        </div> <!-- ./article__mobile-footer -->
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
<?php echo $__env->make('patterns.section__favorites', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
