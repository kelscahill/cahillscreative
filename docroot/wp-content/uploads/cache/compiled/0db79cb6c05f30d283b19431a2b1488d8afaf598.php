<?php if(is_home() || in_category('4') || is_page('my-favorites')): ?>
  <?php echo $__env->make('patterns.popup', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
<?php endif; ?>
<header role="banner">
  <div class="header__utility background-color--black">
    <div class="header__utility--left">
      <a href="https://cahillscreative.us3.list-manage.com/subscribe/post?u=1bf312784f904cef8899dc68d&amp;id=864ef19e83" target="_blank" class="header__utility-mailing">
        <span class="icon icon--s space--half-right">
          <?php echo $__env->make('patterns.icon__email', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        </span>
        <span class="color--white font--primary--xs">Join our mailing list!</span>
      </a>
    </div>
    <div class="header__utility--right">
      <div class="header__utility-search">
        <?php echo $__env->make('patterns.form__search', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
      <div class="header__utility-social">
        <?php echo $__env->make('patterns.social-links', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </div>
  <?php echo $__env->make('partials.navigation', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
</header>
