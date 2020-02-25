<?php
  $prev_post = get_previous_post(true, '', 'category');
  $next_post = get_next_post(true, '', 'category');
?>
<div class="article__nav">
  <div class="article__nav--inner">
    <?php if(!empty($prev_post)): ?>
      <?php
        $prev_link = $prev_post->guid;
        $prev_title = $prev_post->post_title;
      ?>
      <a href="<?php echo e($prev_link); ?>" class="article__nav-item previous">
        <div class="article__nav-item-label font--primary--xs">
          <span class="icon icon--xs"><?php echo $__env->make('patterns/arrow--previous', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span><font>Previous</font>
        </div>
        <div class="font--primary--s"><?php echo e($prev_title); ?></div>
      </a>
    <?php endif; ?>
  </div>
  <div class="article__nav--inner">
    <?php if(!empty($next_post)): ?>
      <?php
        $next_link = $next_post->guid;
        $next_title = $next_post->post_title;
      ?>
      <a href="<?php echo e($next_link); ?>" class="article__nav-item next">
        <div class="article__nav-item-label font--primary--xs">
          <font>Next</font><span class="icon icon--xs"><?php echo $__env->make('patterns/arrow--next', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
        </div>
        <div class="font--primary--s"><?php echo e($next_title); ?></div>
      </a>
    <?php endif; ?>
  </div>
</div>
