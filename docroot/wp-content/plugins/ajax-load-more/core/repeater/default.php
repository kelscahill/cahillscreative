<?php
  $id = get_the_ID();
  $title = get_the_title($id);
  $excerpt = get_the_excerpt($id);
  $thumb_id = get_post_thumbnail_id($id);
  $link = get_permalink($id);
  $date = date('F j, Y', strtotime(get_the_date()));
?>
<div class="block block__news background-color--white border">
  <div class="grid <?php if($thumb_id): echo 'grid--50-50'; else: echo ' space--zero'; endif; ?>">
    <?php if(!empty($thumb_id)): ?>
      <a href="<?php echo $link; ?>" class="grid-item block__media background-image--<?php echo $thumb_id; ?> background--cover">
        <style>
          .background-image--<?php echo $thumb_id; ?> {
            background-image: url(<?php echo wp_get_attachment_image_src($thumb_id, "horiz__16x9--s")[0]; ?>);
          }
          @media (min-width: 500px) {
            .background-image--<?php echo $thumb_id; ?> {
              background-image: url(<?php echo wp_get_attachment_image_src($thumb_id, "horiz__16x9--m")[0]; ?>);
            }
          }
        </style>
      </a>
    <?php endif; ?>
    <a href="<?php echo $link; ?>" class="grid-item block__content spacing">
      <div class="block__header">
        <h3 class="block__title"><?php echo $title; ?></h3>
        <p class="block__meta font--s color--gray"><?php echo $date; ?></p>
      </div>
      <?php if(!empty($excerpt)): ?>
        <p class="block__excerpt color--black"><?php echo wp_trim_words($excerpt, 50, '&hellip; <span class="color--secondary">Read more</span>'); ?></p>
      <?php endif; ?>
    </a>
  </div>
</div>