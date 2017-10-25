<?php 
  global $wp_query;
  $total_results = $wp_query->found_posts;
 ?>

<?php $__env->startSection('content'); ?>
  <section class="section section__main">
    <div class="layout-container">
      <article <?php (post_class('article narrow--xl center-block spacing--double')); ?>>
        <div class="page-header spacing text-align--center narrow narrow--m">
          <h2 class="page-kicker font--primary--s">Search Results for</h2>
          <hr class="divider">
          <h1 class="page-title"><?php echo e(get_search_query()); ?></h1>
          <div class="page-intro">
            <p><?php echo e($total_results); ?> total results found.</p>
          </div>
        </div>
        <?php if(have_posts()): ?>
          <?php  echo do_shortcode('[ajax_load_more post_type="post, affiliate, work" search="'. get_search_query() .'" orderby="relevance" posts_per_page="12" scroll="true" button_label="Show More Results" transition_container="false"]');  ?>
        <?php else: ?>
          <p class="text-align--center space--zero"><?php echo e(__('Sorry, no results were found.', 'sage')); ?></p>
          <?php echo get_search_form(false); ?>

        <?php endif; ?>
      </article>
    </div>
  </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>