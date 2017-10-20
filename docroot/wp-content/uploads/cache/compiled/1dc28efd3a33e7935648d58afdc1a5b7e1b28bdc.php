<div class="accordion spacing">
  <div class="accordion--inner">
    <?php ($accordion = get_field('accordion')); ?>
    <?php if($accordion): ?>
      <?php $__currentLoopData = $accordion; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="accordion-item is-active">
          <div class="accordion-item__title js-toggle-parent">
            <h4 class="font--primary--m"><?php echo e($item['accordion_title']); ?></h4>
            <span class="accordion-item__toggle spacing--zero"></span>
          </div>
          <div class="accordion-item__body article__body spacing padding--zero">
            <?php  echo wpautop($item['accordion_body']);  ?>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
    <?php ($instructions = get_field('instructions')); ?>
    <?php if($instructions): ?>
      <div class="accordion-item is-active">
        <div class="accordion-item__title js-toggle-parent">
          <h4 class="font--primary--m">Instructions</h4>
          <span class="accordion-item__toggle spacing--zero"></span>
        </div>
        <div class="accordion-item__body article__body spacing padding--zero step">
          <?php $__currentLoopData = $instructions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="step-item">
              <div class="step-item__number"><span class="font--primary--xs color--gray">Step</span></div>
              <div class="step-item__content spacing">
                <?php  echo wpautop($item['instructions_content']);  ?>
                <?php if(!empty($item['instructions_image'])): ?>
                  <picture class="block__thumb">
                    <source srcset="<?php echo e($item['instructions_image']['sizes']['horiz__4x3--l']); ?>" media="(min-width:800px)">
                    <source srcset="<?php echo e($item['instructions_image']['sizes']['horiz__4x3--l']); ?>" media="(min-width:500px)">
                    <img src="<?php echo e($item['instructions_image']['sizes']['horiz__4x3--l']); ?>" alt="<?php echo e($item['instructions_image']['alt']); ?>">
                  </picture>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="accordion-item is-active">
      <div class="accordion-item__title js-toggle-parent">
        <h4 class="font--primary--m">Comments</h4>
        <span class="accordion-item__toggle spacing--zero"></span>
      </div>
      <div class="accordion-item__body article__body spacing padding--zero">
        <?php echo $__env->make('partials.comments', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </div>
</div>
