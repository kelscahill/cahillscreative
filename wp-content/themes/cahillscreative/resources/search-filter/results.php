<div class="c-posts u-spacing--double">
  <?php if ($query->have_posts()) : ?>
    <div class="c-posts__grid" bp="grid 6@xs 4@lg 3@xl">
      <?php while ($query->have_posts()): $query->the_post(); $post_id = get_the_ID(); ?>
        <?php include locate_template('views/blocks/card.php'); ?>
      <?php endwhile; ?>
    </div>
    <?php include locate_template('views/blocks/pagination.php'); ?>
  <?php else: ?>
    <p><?php echo "No Results Found"; ?></p>
  <?php endif; ?>
</div>