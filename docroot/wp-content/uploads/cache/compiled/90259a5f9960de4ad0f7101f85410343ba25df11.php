<?php 
  $id = get_queried_object_id();
  if (is_page_template("views/template-events.blade.php")) {
    $filter = 'Calendar';
  } else {
    $filter = 'News';
  }
 ?>
<section class="nav-bar nav-bar background-color--quaternary color--white">
  <div class="nav-bar--inner flex-justify--space-between layout-container js-this">
    <div class="nav-bar--left">
      <div class="nav-bar__label">
        <?php if(get_field('page_icon', $id)): ?>
          <span class="icon icon--m icon--<?php echo e(the_field('page_icon', $id)); ?> space--half-right"></span>
        <?php endif; ?>
        <p class="font--m">Refine <?php echo e($filter); ?></p>
      </div>
      <div class="nav-bar__dropdown">
        <div class="nav-bar__toggle js-toggle" data-prefix="nav-bar--inner" data-toggled="this">
          <p class="filter-label"><?php echo e($filter_label); ?></p>
          <span class="icon icon--xs icon--arrow path-fill--white space--half-left">
            <?php echo $__env->make('patterns.arrow--small', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
          </span>
        </div>
        <ul class="nav-bar__list secondary-nav">
          <?php if(is_page_template("views/template-events.blade.php")): ?>
            <?php $__currentLoopData = $output; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php 
                $link = get_permalink();
                $link_url = strtolower(date('F-Y', strtotime($key)));
                $link_text = date('F Y', strtotime($key));
               ?>
              <li class="nav-bar__list-item secondary-nav__item">
                <a class="nav-bar__list-link" href="<?php echo e($link); ?>?filter=<?php echo e($link_url); ?>"><?php echo e($link_text); ?></a>
              </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php else: ?>
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="<?php echo e($link); ?>?orderby=title&order=ASC" class="filter-link secondary-nav__link">By Title</a>
            </li>
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="<?php echo e($link); ?>?orderby=date&order=DESC" class="filter-link secondary-nav__link">By Newer Posts</a>
            </li>
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="<?php echo e($link); ?>?orderby=date&order=ASC" class="filter-link secondary-nav__link">By Older Posts</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="nav-bar--right hide-until--m">
      <?php if(is_page_template("views/template-events.blade.php")): ?>
        <a href="https://calendar.google.com/calendar/embed?src=wilkesschool.org_3ik6a6kkni39p84u8bgkid05tc%40group.calendar.google.com&ctz=America/New_York" class="nav-bar__subscribe link--cta link--cta--white font--m color--white" target="_blank">Subscribe to Calendar<span class="icon icon--arrow icon--s space--half-left"><?php echo $__env->make('patterns.arrow--small', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span></a>
      <?php else: ?>
        <a href="http://wilkesschool.us16.list-manage.com/subscribe/post?u=44b1b0d9f882aa37c64c004cf&id=7857dacd0c" class="nav-bar__subscribe link--cta link--cta--white font--m color--white" target="_blank">Sign Up for Updates<span class="icon icon--arrow icon--s space--half-left"><?php echo $__env->make('patterns.arrow--small', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span></a>
      <?php endif; ?>
    </div>
  </div>
</section>
