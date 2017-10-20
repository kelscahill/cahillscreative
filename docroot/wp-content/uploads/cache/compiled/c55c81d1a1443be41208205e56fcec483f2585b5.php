<footer class="footer background-color--black color--white">
  <div class="footer--inner layout-container spacing--double">
    <div class="footer__row footer__row--top grid grid--50-50">
      <div class="grid-item footer--left spacing--double">
        <div class="footer__nav">
          <div class="footer__nav-col">
            <a href="/work" class="footer__nav-link">Work</a>
            <a href="/about" class="footer__nav-link">About</a>
            <a href="/services" class="footer__nav-link">Services</a>
            <a href="/contact" class="footer__nav-link">Contact</a>
          </div>
          <div class="footer__nav-col">
            <a href="/blog" class=" font--primary--xs"><span class="color--gray">Blog</span></a>
            <a href="/blog/diy" class="footer__nav-link">DIY</a>
            <a href="/blog/health" class="footer__nav-link">Health</a>
          </div>
          <div class="footer__nav-col">
            <span class="color--gray font--primary--xs">Shop</span>
            <a href="https://www.etsy.com/shop/CahillsCreative" target="_blank" class="footer__nav-link">Esty</a>
            <a href="/my-favorites" class="footer__nav-link">My Favorites</a>
          </div>
        </div>
        <div class="footer__mailing">
          <span class="color--gray font--primary--xs">Join the mailing list</span>
          <form action="https://cahillscreative.us3.list-manage.com/subscribe/post?u=1bf312784f904cef8899dc68d&amp;id=864ef19e83" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="form--inline space--half-top validate" target="_blank" novalidate>
            <input id="mce-EMAIL" name="EMAIL" type="email" placeholder="Email address" name="email"/>
            <button class="btn">Sign Up</button>
          </form>
        </div>
      </div>
      <div class="grid-item footer--right">
        <div class="footer__posts spacing">
          <span class="color--gray font--primary--xs">Latest Posts</span>
          <?php echo $__env->make('patterns.feed__latest', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        </div>
        <div class="footer__ads">
          <span class="color--gray font--primary--xs space--half-bottom">Advertisements</span>
          <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
          <!-- Footer Ad (Rectangle) -->
          <ins class="adsbygoogle"
               style="display:inline-block;width:336px;height:280px"
               data-ad-client="ca-pub-3133257559155527"
               data-ad-slot="9671017956"></ins>
          <script>
          (adsbygoogle = window.adsbygoogle || []).push({});
          </script>
        </div>
      </div>
    </div>
    <div class="footer__row footer__row--bottom">
      <div class="footer__copyright">
        <a class="font--primary--xs space--half-right" href="/privacy-policy">Privacy Policy</a>
        <span class="font--primary--xs font-weight--400">© 2018 Cahill’s Creative. All Rights Reserved.</span>
      </div>
      <div class="footer__social">
        <span class="color--gray font--primary--xs space--half-right">Follow Me</span>
        <?php echo $__env->make('patterns.social-links', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
      </div>
    </div>
  </div>
  <a href="#top" class="footer__top font--primary--xs">
    To Top <span class="icon icon--l space--half-left path-fill--white"><?php echo $__env->make('patterns.arrow__cta--long', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span>
  </a>
</footer>
