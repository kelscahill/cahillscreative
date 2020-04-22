<div class="posts u-spacing--double">
  <?php if ($query->have_posts()): ?>
    <div class="grid grid--full">
      <?php while ($query->have_posts()): $query->the_post(); ?>
        <?php locate_template('views/block.php'); ?>
      <?php endwhile; $query->reset_postdata(); ?>
    </div>
  <?php else: ?>
    <div class='search-filter-results-list' data-search-filter-action='infinite-scroll-end'>
      <p>End of Results</p>
    </div>
  <?php endif; ?>
</div>