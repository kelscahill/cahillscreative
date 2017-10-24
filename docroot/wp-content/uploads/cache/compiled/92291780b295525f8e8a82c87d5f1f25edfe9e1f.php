<?php 
if (post_password_required()) {
  return;
}
 ?>
<section id="comments" class="comments">
  <?php if(have_comments()): ?>
    <h2 class="font--primary--s">
      <?php echo sprintf(_nx('One response to &ldquo;%2$s&rdquo;', '%1$s responses to &ldquo;%2$s&rdquo;', get_comments_number(), 'comments title', 'sage'), number_format_i18n(get_comments_number()), '<span>' . get_the_title() . '</span>'); ?>

    </h2>

    <ol class="comment-list">
      <?php echo wp_list_comments(['style' => 'ol', 'short_ping' => true, 'avatar_size' => 80]); ?>

    </ol>

    <?php if(get_comment_pages_count() > 1 && get_option('page_comments')): ?>
      <nav>
        <ul class="pager">
          <?php if(get_previous_comments_link()): ?>
            <li class="previous"><?php (previous_comments_link(__('&larr; Older comments', 'sage'))); ?></li>
          <?php endif; ?>
          <?php if(get_next_comments_link()): ?>
            <li class="next"><?php (next_comments_link(__('Newer comments &rarr;', 'sage'))); ?></li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <?php if(!comments_open() && get_comments_number() != '0' && post_type_supports(get_post_type(), 'comments')): ?>
    <p><?php echo e(__('Sorry, no results were found.', 'sage')); ?></p>
  <?php endif; ?>

  <?php (comment_form()); ?>
</section>
