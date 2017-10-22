<?php 
  $id = get_queried_object_id();
  if (is_category() || is_home() || is_tag()) {
    $thumb_id = 'default';
    $title = get_the_title(6);
    $excerpt = get_the_excerpt(6);
    $category = get_cat_name($id);
  } else if (is_front_page()) {
    $thumb_id = get_post_thumbnail_id();
    $title = 'We Create';
    $excerpt = get_the_excerpt();
  } else if (get_post_thumbnail_id()) {
    $thumb_id = get_post_thumbnail_id();
    $excerpt = get_the_excerpt();
    if (get_field('display_title')) {
      $title = get_field('display_title');
    } else {
      $title = get_the_title();
    }
    if (0 == $post->post_parent) {
      $category = get_the_title();
    } else {
      $category = get_the_title($post->post_parent);
    }
  }
 ?>
<section class="section section__hero background--cover background-image--<?php echo e($thumb_id); ?>">
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
  <div class="section__hero--inner spacing">
    <?php if(!empty($category)): ?>
      <div class="kicker font--primary--s color--white"><?php echo e($category); ?></div>
      <hr class="divider background-color--white" />
    <?php endif; ?>
    <h1 class="section__hero-title font--secondary--xl color--white"><?php echo e($title); ?></h1>
    <?php if(is_front_page()): ?>
      <h2 class="section__hero-subtitle font--primary--m color--white">Websites that kick ass.</h2>
    <?php endif; ?>
    <?php if(!empty($excerpt)): ?>
      <p class="section__hero-excerpt color--white"><?php echo e($excerpt); ?></p>
    <?php endif; ?>
  </div>
</section>
