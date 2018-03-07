@if (is_home() || in_category('4') || is_page('my-favorites'))
  @include('patterns.popup')
@endif
<header role="banner">
  <div class="header__utility background-color--black">
    <div class="header__utility--left">
      <a href="https://cahillscreative.us3.list-manage.com/subscribe/post?u=1bf312784f904cef8899dc68d&amp;id=864ef19e83" target="_blank" class="header__utility-mailing">
        <span class="icon icon--s space--half-right">
          @include('patterns.icon--email')
        </span>
        <span class="color--white font--primary--xs">Join our mailing list!</span>
      </a>
    </div>
    <div class="header__utility--right">
      <div class="header__utility-search">
        @include('patterns.form--search')
      </div>
      <div class="header__utility-social">
        @include('patterns.social-links')
      </div>
    </div>
  </div>
  @include('partials.navigation')
</header>
