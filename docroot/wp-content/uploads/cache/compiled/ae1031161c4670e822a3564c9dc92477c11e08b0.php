<div class="grid-item">
  <?php 
    $post_id = get_the_ID();
    $title = get_the_title($post_id);
    $thumb_id = get_post_thumbnail_id($post_id);
    $link = get_permalink($post_id);
    $kicker = 'Featured Work';
    $tags = strip_tags(get_the_tag_list('',', ',''));
   ?>
  <div class="block block__featured">
    <a href="<?php echo e($link); ?>" class="block__link spacing background--cover background-image--<?php echo e($thumb_id); ?> image-overlay">
      <?php if(!empty($thumb_id)): ?>
        <style>
          .background-image--<?php echo e($thumb_id); ?> {
            background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "square--s")[0]); ?>);
          }
          @media (min-width: 800px) {
            .background-image--<?php echo e($thumb_id); ?> {
              background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "square--m")[0]); ?>);
            }
          }
          @media (min-width: 1100px) {
            .background-image--<?php echo e($thumb_id); ?> {
              background-image: url(<?php echo e(wp_get_attachment_image_src($thumb_id, "square--l")[0]); ?>);
            }
          }
        </style>
      <?php endif; ?>
      <div class="block__content">
        <div class="spacing--half">
          <?php if(!empty($kicker)): ?>
            <div class="block__kicker font--primary--xs color--gray">
              <?php echo e($kicker); ?>

            </div>
          <?php endif; ?>
          <div class="block__title font--primary--m color--black">
            <?php echo e($title); ?>

          </div>
          <?php if(!empty($tags)): ?>
            <div class="block__meta color--gray">
              <span class="color--gray font--s"><?php echo e($tags); ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="block__button">
          View Project
        </div>
      </div>
    </a>
  </div>
</div>
