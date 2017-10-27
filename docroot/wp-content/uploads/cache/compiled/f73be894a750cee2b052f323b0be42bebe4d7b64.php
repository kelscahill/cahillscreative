<?php $__env->startSection('content'); ?>
  <?php while(have_posts()): ?> <?php (the_post()); ?>
    <?php echo $__env->make('partials.content-page', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php ($process = get_field('process_steps')); ?>
    <?php if($process): ?>
      <section class="section section__process padding--double-bottom">
        <div class="section--inner layout-container narrow narrow--m spacing--double">
          <div class="step">
            <?php $__currentLoopData = $process; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="step-item sticky-parent">
                <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
                <div class="step-item__content spacing">
                  <h2 class="font--primary--s"><?php echo e($item['process_title']); ?></h2>
                  <?php  echo wpautop($item['process_body']);  ?>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if(get_field('etsy_link')): ?>
              <a href="<?php echo e(get_field('etsy_link')); ?>" class="btn center-block" target="_blank">Download PDF Plans</a>
            <?php endif; ?>
          </div>
          <p class="text-align--center">This is how I do what I do. Now, take a look at some of the end results.</p>
          <a href="/work" class="btn btn--center">See Work</a>
        </div>
      </section>
    <?php endif; ?>
    <section class="section section__faqs padding--double-top padding--double-bottom background-color--white">
      <div class="section--inner layout-container narrow narrow--l spacing--double">
        <div class="section__header text-align--center">
          <h3 class="font--primary--s">FAQ's</h3>
          <hr class="divider" />
          <h2 class="font--primary--xl">You have questions. I have answers.</h2>
        </div>
        <?php ($accordion = get_field('accordion')); ?>
        <?php if($accordion): ?>
          <div class="accordion spacing">
            <div class="accordion--inner space--half-bottom">
              <?php $__currentLoopData = $accordion; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="accordion-item">
                  <div class="accordion-item__title js-toggle-parent">
                    <h4 class="font--primary--m"><?php echo e($item['accordion_title']); ?></h4>
                    <span class="accordion-item__toggle spacing--zero"></span>
                  </div>
                  <div class="accordion-item__body article__body spacing padding--zero">
                    <?php  echo wpautop($item['accordion_body']);  ?>
                  </div>
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
        <?php endif; ?>
        <p class="text-align--center">Don't hesitate to reach out with any other questions or just to say hi.</p>
        <a href="/contact" class="btn btn--center">Contact</a>
      </div>
    </section>
  <?php endwhile; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>