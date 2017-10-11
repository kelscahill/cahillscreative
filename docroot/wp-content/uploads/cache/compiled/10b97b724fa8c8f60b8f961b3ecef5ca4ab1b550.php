<div class="page-header spacing--double">
  <?php if(is_singular('events')): ?>
    <div class="kicker color--gray">
      <span class="icon icon--m icon--events space--half-right"></span>
      <p class="font--m">Event</p>
    </div>
  <?php elseif(is_single()): ?>
    <div class="kicker color--gray">
      <span class="icon icon--m icon--news space--half-right"></span>
      <p class="font--m">News</p>
    </div>
  <?php elseif(get_queried_object() && get_queried_object()->post_parent != 0): ?>
    <?php 
      $id = get_queried_object();
      $page_parent = $id->post_parent;
     ?>
    <div class="kicker color--gray">
      <?php if(get_field('page_icon', $page_parent)): ?>
        <span class="icon icon--m icon--<?php echo e(the_field('page_icon', $page_parent)); ?> space--half-right"></span>
      <?php endif; ?>
      <p class="font--m"><?php echo e(get_the_title($page_parent)); ?></p>
    </div>
  <?php elseif(get_field('page_icon', get_the_ID())): ?>
    <div class="kicker color--gray">
      <span class="icon icon--m icon--<?php echo e(the_field('page_icon', get_the_ID())); ?> space--half-right"></span>
      <p class="font--m"><?php echo e(get_the_title(get_the_ID())); ?></p>
    </div>
  <?php else: ?>
  <?php endif; ?>
  <h1 class="page-title font--primary--l">
    <?php if(get_field('display_title')): ?>
      <?php echo e(the_field('display_title')); ?>

    <?php else: ?>
      <?php echo App\title(); ?>

    <?php endif; ?>
  </h1>
  <?php if(get_field('intro')): ?>
    <div class="page-intro"><?php  echo wpautop(the_field('intro'));  ?></div>
  <?php endif; ?>
  <?php if(get_field('link_url')): ?>
    <a class="btn" href="<?php echo e(the_field('link_url')); ?>"><?php if(get_field('link_text')): ?><?php echo e(the_field('link_text')); ?><?php else: ?><?php echo e('Learn More'); ?><?php endif; ?></a>
  <?php endif; ?>
</div>
