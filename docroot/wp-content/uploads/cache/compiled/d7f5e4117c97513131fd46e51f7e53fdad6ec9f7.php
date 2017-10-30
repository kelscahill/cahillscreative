<?php 
  $link = get_the_permalink();
  $title = get_the_title();
  if (get_the_excerpt() != '') {
    $excerpt = get_the_excerpt('',FALSE,'');
  } else {
    $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
  }
  $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'horiz__4x3--m')[0];
 ?>
<div class="block__toolbar-share-tooltip tooltip-wrap">
  <div class="tooltip-item font--primary--xs text-align--center color--gray">Share Post</div>
  <a aria-label="Share on Facebook" href="https://facebook.com/sharer/sharer.php?u=<?php echo e($link); ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__facebook', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Facebook</a>
  <a aria-label="Share on Twitter" href="https://twitter.com/intent/tweet/?text=<?php echo e($title); ?><?php echo e(': ' . $excerpt . ' ' . $link); ?>&amp;url=<?php echo e($link); ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__twitter', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Twitter</a>
  <a aria-label="Share on Pinterest" href="https://pinterest.com/pin/create/button/?url=<?php echo e($link); ?>&amp;media=<?php echo e($image_medium); ?>&amp;description=<?php echo e($title); ?><?php echo e(': ' . $excerpt . ' ' . $link); ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__pinterest', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Pinterest</a>
  <a aria-label="Share on LinkedIn" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo e($link); ?>&amp;title=<?php echo e($title); ?>&amp;summary=<?php echo e($excerpt . ' ' . $link); ?>&amp;source=<?php echo e($link); ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__linkedin', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>LinkedIn</a>
  <a aria-label="Share by E-Mail" href="mailto:?subject=<?php echo e($title); ?>&amp;body=<?php echo e($excerpt . ' ' . $link); ?>" target="_self" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php echo $__env->make('patterns.icon__email', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Email</a>
  <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
</div>
