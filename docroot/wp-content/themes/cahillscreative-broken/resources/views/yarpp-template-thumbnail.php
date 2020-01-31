<?php
/*
YARPP Template: Thumbnails
Description: Requires a theme which supports post thumbnails
Author: mitcho (Michael Yoshitaka Erlewine)
*/
?>
<?php if (have_posts()): ?>
  <?php while (have_posts()) : the_post(); ?>
  <?php
    // If the image has a native WordPress featured image, use that. Otherwise, use
    // one of the attached images.
    if (get_post_thumbnail_id()) {
      $thumb_id = get_post_thumbnail_id();
    }
    else {
      $thumb_id = get_fallback_attachment_image();
    }
    $image = wp_get_attachment_image_src($thumb_id, 'thumbnail')[0];
    $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
    $title = get_the_title();
    $link = get_the_permalink();
  ?>
    <div class="block block__latest">
      <a href="<?php echo $link; ?>" class="block__link">
        <?php if (!empty($thumb_id)): ?>
          <picture class="block__thumb space--right">
            <img src="<?php echo $image; ?>" alt="<?php echo $alt; ?>">
          </picture>
        <?php endif; ?>
        <div class="block__content">
          <div class="block__title font--primary--xs">
            <?php echo $title; ?>
          </div>
          <div class="block__meta color--gray">
            <time class="updated color--gray font--s" datetime="<?php echo get_post_time('c', true); ?>"><?php echo get_the_date(); ?></time>
          </div>
        </div>
      </a>
    </div>
  <?php endwhile; wp_reset_postdata(); ?>
<?php else: ?>
  <p>Sorry, no related posts.</p>
<?php endif; ?>
