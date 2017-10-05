@php
  $classes = '';
  if (is_front_page() || is_page_template("views/template-news.blade.php")  || is_page_template("views/template-landing.blade.php") || is_page_template("views/template-news.blade.php") || is_page_template("views/template-events.blade.php")) {
    $classes = ' header--transparent position--absolute shadow';
  }
@endphp
<div class="body-overlay"></div>
<header class="{{ 'header'.$classes }}">

  <div class="header--inner">
    <div class="header--left">
      <a class="header__logo" href="{{ home_url('/') }}">@include('patterns.logo')</a>
    </div>
    <div class="header--right">
      <div class="header__search space--half-right hide-until--m">
        @include('patterns.form--search')
      </div>
      <div class="header__social hide-until--m">
        @include('patterns.social-links')
      </div>
      @include('partials.navigation')
    </div>
  </div>
</header>
