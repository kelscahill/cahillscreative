<?php 
  $posts = get_posts(array(
    'posts_per_page' => 2,
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby'	=> 'date',
    'order' => 'DESC',
  ));
 ?>
<?php if($posts): ?>
  <?php $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php 
      setup_postdata($post);
      $id = $post->ID;
      $thumb_id = get_post_thumbnail_id($id);
      $image = wp_get_attachment_image_src($thumb_id, 'thumbnail')[0];
      $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
      $title = get_the_title($id);
      $link = get_the_permalink($id);
      $date = get_the_date('F j, Y', $post);
     ?>
    <?php echo $__env->make('patterns.block--latest', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  <?php (wp_reset_postdata()); ?>
<?php endif; ?>
