<header>
  <div class="header__utility background-color--black">
    <div class="header__utility--left">
      <a href="" class="header__utility-mailing">
        <span class="icon icon--s space--half-right">
          <?php echo $__env->make('patterns.icon__email', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        </span>
        <span class="color--white font--primary--xs">Join our mailing list!</span>
      </a>
    </div>
    <div class="header__utility--right">
      <div class="header__utility-search">
        <?php echo $__env->make('patterns.form--search', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
      <div class="header__utility-social">
        <?php echo $__env->make('patterns.social-links', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </div>
  <?php echo $__env->make('partials.navigation', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
</header>
