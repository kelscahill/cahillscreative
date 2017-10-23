<?php 
  $args = array(
   'post_type' => 'affiliate',
   'posts_per_page' => -1,
   'post_status' => 'publish',
   'order' => 'DESC',
   'tax_query' => array(
     array(
       'taxonomy' => 'post_tag',
       'field' => 'slug',
       'terms' => 'favorite'
     )
   )
  );
  $posts = new WP_Query($args);
 ?>
<?php if($posts->have_posts()): ?>
<section class="secton section__favorites background-color--white">
    <div class="layout-container section__favorites--inner text-align--center section--padding">
      <h3 class="font--primary--s">Shop My Favorites</h3>
      <hr class="divider" />
      <div class="slick-favorites">
        <?php while($posts->have_posts()): ?> <?php ($posts->the_post()); ?>
          <?php 
            $post_id = get_the_ID();
            $thumb_id = get_post_thumbnail_id($post_id);
            $thumb_size = 'square';
            $link = get_permalink($post_id);
            $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
            $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
            $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
           ?>
          <a href="<?php echo e($link); ?>" class="block__favorite block__link spacing">
            <?php if(!empty($thumb_id)): ?>
              <picture class="block__thumb">
                <source srcset="<?php echo e($image_medium); ?>" media="(min-width:500px)">
                <img src="<?php echo e($image_small); ?>" alt="<?php echo e($alt); ?>">
              </picture>
            <?php endif; ?>
          </a>
        <?php endwhile; ?>
        <?php (wp_reset_query()); ?>
      </div>
    </div>
  </section>
<?php endif; ?>
