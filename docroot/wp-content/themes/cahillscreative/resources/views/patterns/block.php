<div class="grid-item">
  <?php
    $post_id = get_the_ID();
    $title = get_the_title($post_id);
    $thumb_id = get_post_thumbnail_id($post_id);
    $thumb_size = 'square';
    $link = get_permalink($post_id);
    $post_type = get_post_type($post_id);
    if (get_the_excerpt() != '') {
      $excerpt = get_the_excerpt('',FALSE,'');
    } else {
      $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
    }
    if ($post_type == 'affiliate') {
      $kicker = 'Shop';
      $date = NULL;
    } else if ($post_type == 'work') {
      $kicker = NULL;
      $date = NULL;
      $tags = strip_tags(get_the_tag_list('',', ',''));
    } else {
      $kicker = get_the_category($post_id)[0]->name;
      $date = date('F j, Y', strtotime(get_the_date()));
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
        <?php if (!empty($tags)): ?>
          <div class="block__meta color--gray">
            <span class="color--gray font--s"><?php echo $tags; ?></span>
          </div>
        <?php endif; ?>
        <?php if($date): ?>
          <div class="block__meta color--gray">
            <time class="updated color--gray font--s" datetime="<?php echo get_post_time('c', true); ?>"><?php echo $date; ?></time>
          </div>
        <?php endif; ?>
      </div>
    </a>
    <div class="block__toolbar">
      <div class="block__toolbar--left">
        <div class="block__toolbar-item block__toolbar-like space--right">
          <?php if(function_exists('wp_ulike')): ?>
            <?php wp_ulike('get'); ?>
          <?php endif; ?>
        </div>
        <?php if (comments_open()): ?>
          <a href="<?php echo $link; ?>#comments" class="block__toolbar-item block__toolbar-comment space--right">
            <span class="icon icon--s space--half-right"><?php include(locate_template('patterns/icon__comment.blade.php')); ?></span>
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
          <a aria-label="Share on Facebook" href="https://facebook.com/sharer/sharer.php?u=<?php echo $link; ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__facebook.blade.php')); ?></span>Facebook</a>
          <a aria-label="Share on Twitter" href="https://twitter.com/intent/tweet/?text=<?php echo $title; ?><?php echo ': ' . $excerpt; ?>&amp;url=<?php echo $link; ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__twitter.blade.php')); ?></span>Twitter</a>
          <a aria-label="Share on Pinterest" href="https://pinterest.com/pin/create/button/?url=<?php echo $link; ?>&amp;media=<?php echo $image_medium; ?>&amp;description=<?php echo $title; ?><?php echo ': ' . $excerpt; ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__pinterest.blade.php')); ?></span>Pinterest</a>
          <a aria-label="Share on LinkedIn" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo $link; ?>&amp;title=<?php echo $title; ?>&amp;summary=<?php echo ': ' . $excerpt; ?>&amp;source=<?php echo $link; ?>" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__linkedin.blade.php')); ?></span>LinkedIn</a>
          <a aria-label="Share by E-Mail" href="mailto:?subject=<?php echo $title; ?>&amp;body=<?php echo $excerpt; ?>" target="_self" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black"><?php include(locate_template('patterns/icon__email.blade.php')); ?></span>Email</a>
          <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
        </div>
      </div>
    </div>
  </div>
</div>
