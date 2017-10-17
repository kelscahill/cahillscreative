<section class="section section__filter">
  <span class="font--primary--xs color--gray">Filter</span>
  <?php if(is_category('diy')): ?>
    <select>
      <option selected>Projects</option>
      <?php 
        $term_projects = get_terms( array(
          'taxonomy' => 'projects',
          'hide_empty' => true,
        ));
       ?>
      <?php $__currentLoopData = $term_projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option><?php echo e($term->name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  <?php else: ?>

  <?php endif; ?>
</section>
