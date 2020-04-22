<div class="posts spacing--double">
  <?php if ($query->have_posts()): ?>
    <h4 class="text-align--center">Found <?php echo $query->found_posts; ?> Results</h4>
    <div class="grid grid--full">
      <?php while ($query->have_posts()): $query->the_post(); ?>
        <?php include(locate_template('views/block.php')); ?>
      <?php endwhile; $query->wp_reset_query(); ?>
    </div>
  <?php else: ?>
    <p class="text-align--center"><em>No Results Found</em></p>
  <?php endif; ?>
</div>