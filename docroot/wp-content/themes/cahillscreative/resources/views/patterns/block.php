<div class="grid-item">
  <?php
    $post_id = get_the_ID();
    $title = get_the_title($post_id);
    $excerpt = get_the_excerpt($post_id);
    $thumb_id = get_post_thumbnail_id($post_id);
    $thumb_size = 'square';
    $link = get_permalink($post_id);
    $date = date('F j, Y', strtotime(get_the_date()));
    $post_type = get_post_type($post_id);
    if ($post_type == 'affiliate') {
      $kicker = 'Shop';
    } else {
      $kicker = get_the_category($post_id)[0]->name;
    }
    $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
    $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
    $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
  ?>
  <div class="block block__post background-color--white">
    <a href="<?php echo $link; ?>" class="block__link spacing">
      <?php if (!empty($thumb_id)): ?>
        <picture class="block__thumb">
          <source srcset="<?php echo $image_medium; ?>" media="(min-width:500px)">
          <img src="<?php echo $image_small; ?>" alt="<?php echo $alt; ?>">
        </picture>
      <?php endif; ?>
      <div class="block__content spacing--half">
        <?php if (!empty($kicker)): ?>
          <div class="block__kicker font--primary--xs color--gray">
            <?php echo $kicker; ?>
          </div>
        <?php endif; ?>
        <div class="block__title font--primary--m color--black">
          <?php echo $title ; ?>
        </div>
        <div class="block__meta color--gray">
          <?php if ($post_type == 'page'): ?>
            <?php the_excerpt(); ?>
          <?php else: ?>
            <time class="updated color--gray font--s" datetime="<?php echo get_post_time('c', true); ?>"><?php echo $date; ?></time>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php if ($post_type != 'page'): ?>
      <div class="block__toolbar">
        <div class="block__toolbar--left">
          <div class="block__toolbar-item block__toolbar-like space--right">
            <?php if(function_exists('wp_ulike')): ?>
              <?php wp_ulike('get'); ?>
            <?php endif; ?>
          </div>
          <?php if (comments_open()): ?>
            <a href="<?php echo $link; ?>#comments" class="block__toolbar-item block__toolbar-comment space--right">
              <span class="icon icon--s space--half-right"><?php include(locate_template('patterns/icon__comment')); ?></span>
              <span class="font--sans-serif font--sans-serif--small color--gray">
                <?php echo comments_number('0', '1', '%'); ?>
              </span>
            </a>
          <?php endif; ?>
        </div>
        <div class="block__toolbar--right tooltip">
          <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
            <span class="font--primary--xs color--gray">Share</span>
            <span class="icon icon--s space--half-left"><?php include(locate_template('patterns/icon__share.blade.php')); ?></span>
          </div>
          <div class="block__toolbar-share-tooltip tooltip-wrap">
            <div class="tooltip-item font--primary--xs text-align--center color--gray">Share Post</div>
            <div data-title="<?php echo $title; ?>" data-image="<?php echo $image_small; ?>" data-description="<?php echo $excerpt; ?>" data-url="<?php echo $link; ?>" data-network="facebook" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__facebook')); ?></span>Facebook</div>
            <div data-title="<?php echo $title; ?>" data-image="<?php echo $image_small; ?>" data-description="<?php echo $excerpt; ?>" data-url="<?php echo $link; ?>" data-network="twitter" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__twitter')); ?></span>Twitter</div>
            <div data-title="<?php echo $title; ?>" data-image="<?php echo $image_small; ?>" data-description="<?php echo $excerpt; ?>" data-url="<?php echo $link; ?>" data-network="pinterest" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__pinterest')); ?></span>Pinterest</div>
            <div data-title="<?php echo $title; ?>" data-image="<?php echo $image_small; ?>" data-description="<?php echo $excerpt; ?>" data-url="<?php echo $link; ?>" data-network="linkedin" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__linkedin')); ?></span>LinkedIn</div>
            <div data-title="<?php echo $title; ?>" data-image="<?php echo $image_small; ?>" data-description="<?php echo $excerpt; ?>" data-url="<?php echo $link; ?>" data-network="email" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__email')); ?></span>Email</div>
            <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
