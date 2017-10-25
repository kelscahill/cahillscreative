<?php 
  $thumb_id = get_field('featured_banner_image')['ID'];
  $body = get_the_content();
  $category = strip_tags(get_the_tag_list('',', ',''));
  $title = get_the_title();
  $link = get_field('website_url');
  $featured_thumb_id = get_field('featured_work_image')['ID'];
 ?>
<section class="section section__hero background--cover background-image--<?php echo e($thumb_id); ?> image-overlay">
  <?php if(!empty($thumb_id) || $thumb_id != 'default'): ?>
    <style>
      .background-image--<?php echo e($thumb_id); ?> {
        background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "featured__hero--s")[0]); ?>);
      }
      @media (min-width: 800px) {
        .background-image--<?php echo e($thumb_id); ?> {
          background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "featured__hero--m")[0]); ?>);
        }
      }
      @media (min-width: 1100px) {
        .background-image--<?php echo e($thumb_id); ?> {
          background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "featured__hero--l")[0]); ?>);
        }
      }
      @media (min-width: 1600px) {
        .background-image--<?php echo e($thumb_id); ?> {
          background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "featured__hero--xl")[0]); ?>);
        }
      }
    </style>
  <?php endif; ?>
</section>

<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article <?php (post_class('article spacing--double')); ?>>
      <div class="article__header spacing text-align--center narrow narrow--m">
        <?php if(!empty($category)): ?>
          <h2 class="page-kicker font--primary--s color--white"><?php echo e($category); ?></h2>
          <hr class="divider background-color--white">
        <?php endif; ?>
        <h1 class="page-title color--white"><?php echo e($title); ?></h1>
        <?php if (!empty($featured_thumb_id)): ?>
          <picture class="block__thumb">
            <source srcset="<?php echo e(wp_get_attachment_image_src($featured_thumb_id, 'flex-height--l')[0]); ?>" media="(min-width:900px)">
            <source srcset="<?php echo e(wp_get_attachment_image_src($featured_thumb_id, 'flex-height--m')[0]); ?>" media="(min-width:650px)">
            <img src="<?php echo e(wp_get_attachment_image_src($featured_thumb_id, 'flex-height--s')[0]); ?>" alt="<?php echo e(get_post_meta($featured_thumb_id, '_wp_attachment_image_alt', true)); ?>">
          </picture>
        <?php endif; ?>
      </div>
      <div class="article__body narrow spacing--double">
        <div class="narrow narrow--s spacing text-align--center">
          <p><?php echo e($body); ?></p>
          <?php if($link): ?>
            <a href="<?php echo e($link); ?>" class="btn btn--outline center space--top" target="_blank">View Website</a>
          <?php endif; ?>
        </div>
        <?php if(have_rows('work')): ?>
          <?php while(have_rows('work')): ?>
            <?php 
              the_row();
              $work_section_title = get_sub_field('work_section_title');
              $work_section_images = get_sub_field('work_section_images');
             ?>
            <div class="work">
              <div class="work-item spacing--double">
                <div class="work-item__title">
                  <span class="font--primary--s"><?php echo e($work_section_title); ?></span>
                </div>
                <?php $__currentLoopData = $work_section_images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <div class="work-item__image">
                    <picture class="work__image">
                      <source srcset="<?php echo e(wp_get_attachment_image_src($image['ID'], 'flex-height--l')[0]); ?>" media="(min-width:900px)">
                      <source srcset="<?php echo e(wp_get_attachment_image_src($image['ID'], 'flex-height--m')[0]); ?>" media="(min-width:650px)">
                      <img src="<?php echo e(wp_get_attachment_image_src($image['ID'], 'flex-height--s')[0]); ?>" alt="<?php echo e(get_post_meta($image['ID'], '_wp_attachment_image_alt', true)); ?>">
                    </picture>
                  </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
<?php 
  $prev_post = get_previous_post(true, '', 'category');
  $next_post = get_next_post(true, '', 'category');
 ?>
<?php if($prev_post || $next_post): ?>
  <section class="section section__pagination background-color--off-white">
  	<div class="narrow narrow--xl">
      <div class="pagination">
        <div class="pagination-item">
          <?php if(!empty($prev_post)): ?>
            <a href="<?php echo e($prev_post->guid); ?>" class="prev pagination-link">
        			<span class="icon icon--l"><?php echo $__env->make('patterns/arrow__carousel', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
              <p class="font--primary--xs">Previous Project</p>
        		</a>
          <?php endif; ?>
        </div>
        <div class="pagination-item">
      		<a href="/work" class="all pagination-link">
            <span class="icon icon--l"><?php echo $__env->make('patterns/icon__close--large', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
            <p class="font--primary--xs">All Work</p>
          </a>
        </div>
        <div class="pagination-item">
          <?php if(!empty($next_post)): ?>
        		<a href="<?php echo e($next_post->guid); ?>" class="next pagination-link">
        			<span class="icon icon--l"><?php echo $__env->make('patterns/arrow__carousel', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
              <p class="font--primary--xs">Next Project</p>
        		</a>
          <?php endif; ?>
        </div>
      </div>
  	</div>
  </section>
<?php endif; ?>
