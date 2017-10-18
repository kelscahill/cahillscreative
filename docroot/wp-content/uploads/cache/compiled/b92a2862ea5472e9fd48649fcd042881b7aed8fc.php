<section class="section section__filter filter sticky-filter">
  <div class="filter-toggle js-toggle-parent">
    <span class="font--primary--xs color--gray filter-label"><span class="icon icon--s space--half-right path-fill--gray"><?php echo $__env->make('patterns.icon__filter', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>Filter</span>
  </div>
  <form class="filter-wrap">
    <?php if(is_category('diy')): ?>
      <div class="filter-item__container filter-item__container-projects">
        <div class="filter-item__toggle filter-item__toggle-projects font--primary--s js-toggle-parent">Project</div>
        <?php 
          $term_projects = get_terms( array(
            'taxonomy' => 'projects',
            'hide_empty' => false,
          ));
         ?>
        <?php if($term_projects): ?>
          <div class="filter-items">
            <?php $__currentLoopData = $term_projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="filter-item">
                <input type="checkbox" value="<?php echo e($term->name); ?>" id="<?php echo e($term->name); ?>">
                <label for="<?php echo e($term->name); ?>"><?php echo e($term->name); ?></label>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="filter-item__container filter-item__container-room">
        <div class="filter-item__toggle filter-item__toggle-room font--primary--s js-toggle-parent">Room</div>
        <?php 
          $term_room = get_terms( array(
            'taxonomy' => 'room',
            'hide_empty' => false,
          ));
         ?>
        <?php if($term_room): ?>
          <div class="filter-items">
            <?php $__currentLoopData = $term_room; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="filter-item">
                <input type="checkbox" value="<?php echo e($term->name); ?>" id="<?php echo e($term->name); ?>">
                <label for="<?php echo e($term->name); ?>"><?php echo e($term->name); ?></label>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="filter-item__container filter-item__container-cost">
        <div class="filter-item__toggle filter-item__toggle-cost font--primary--s js-toggle-parent">Cost</div>
        <?php 
          $term_cost = get_terms( array(
            'taxonomy' => 'cost',
            'hide_empty' => false,
          ));
         ?>
        <?php if($term_cost): ?>
          <div class="filter-items" data-post-type="post" data-type="checkbox" data-parameter="category">
            <?php $__currentLoopData = $term_cost; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="filter-item" id="slider">
                <input type="checkbox" value="<?php echo e($term->name); ?>" id="<?php echo e($term->name); ?>">
                <label for="<?php echo e($term->name); ?>"><?php echo e($term->name); ?></label>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="filter-item__container filter-item__container-skill is-active">
        <div class="filter-item__toggle filter-item__toggle-skill font--primary--s">Skill Level</div>
        <?php 
          $term_skill = get_terms( array(
            'taxonomy' => 'skill_levels',
            'hide_empty' => false,
          ));
         ?>
        <?php if($term_skill): ?>
          <div class="filter-items">
            <?php $__currentLoopData = $term_skill; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="filter-item filter-item__<?php echo e($term->slug); ?> js-toggle">
                <p class="font--sans-serif--small"><?php echo e($term->name); ?></p>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="filter-footer">
        <button type="button" class="filter-apply">Apply Filters</button>
        <button type="button" class="filter-clear">Clear Filter</button>
      </div>
    <?php else: ?>
      <select class="filter-item">
        <option selected>Category</option>
        <?php 
          $term_projects = get_terms( array(
            'taxonomy' => 'category',
            'hide_empty' => true,
          ));
         ?>
        <?php $__currentLoopData = $term_projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option><?php echo e($term->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    <?php endif; ?>
  </form>
</section>
