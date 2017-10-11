<?php if(is_singular('events')): ?>
  <p class="color--gray font--s">
    <?php 
      $start_date = get_post_meta($post->ID, 'event_start_date_time', true);
      $start_date_string = date('F j, Y', strtotime($start_date));
      $start_time = date('g:i a', strtotime($start_date));

      $end_date = get_post_meta($post->ID, 'event_end_date_time', true);
      $end_time = date('g:i a', strtotime($end_date));

      $all_day = get_post_meta($post->ID, 'event_duration', true);
     ?>
    <?php echo e($start_date_string); ?><br />
    <?php if($all_day == true): ?>
      All day
    <?php else: ?>
      <?php echo e($start_time); ?><?php if($end_date): ?> <?php echo e(' &ndash; ' . $end_time); ?> <?php endif; ?>
    <?php endif; ?>
  </p>
<?php else: ?>
  <time class="updated color--gray font--s" datetime="<?php echo e(get_post_time('c', true)); ?>"><?php echo e(get_the_date()); ?></time>
<?php endif; ?>
