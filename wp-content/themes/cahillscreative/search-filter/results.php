<?php
  global $search_filter_query;
  $searchandfilterId = $query->query['search_filter_query_id'];
  $sf_current_query = \Search_Filter\Queries\Query::find( array(
    'id' => $searchandfilterId
  ) );
  $pagination_type = $sf_current_query->get_attributes()['resultsPaginationType'];
?>
<div id="search-filter-<?php echo $searchandfilterId; ?>" class="c-search-filter u-spacing--double">
  <?php if ($query->have_posts()) : ?>
    <div class="c-posts" bp="grid 6@sm 4@lg 3@xl">
      <?php while ($query->have_posts()): $query->the_post(); ?>
        <?php include locate_template('resources/views/blocks/card.php'); ?>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  <?php endif; ?>
  <?php if ($pagination_type == 'default') : ?>
    <?php include locate_template('resources/views/blocks/pagination.php'); ?>
  <?php endif; ?>
</div>
