<div class="header__nav main-nav">
  <div class="nav__toggle toggle js-toggle-parent sticky">
    <span class="nav__toggle-span nav__toggle-span--1"></span>
    <span class="nav__toggle-span nav__toggle-span--2"></span>
    <span class="nav__toggle-span nav__toggle-span--3 font-weight--800"></span>
  </div>
  @if (has_nav_menu('primary_navigation'))
    <nav id="main-nav" class="nav__primary background-color--primary" role="navigation">
      <div class="nav__search hide-after--m">
        @include('patterns.form--search')
      </div>
      @php
        $menu_name = 'primary_navigation';
        $menu_locations = get_nav_menu_locations();
        $menu = wp_get_nav_menu_object( $menu_locations[ $menu_name ] );
        $primary_nav = wp_get_nav_menu_items( $menu->term_id);
        $count = 0;
        $submenu = false;
      @endphp
      <ul class="primary-nav__list">
        @php
          $primary_nav = json_decode(json_encode($primary_nav), true);
        @endphp

        @foreach ($primary_nav as $nav)
          @if (isset($primary_nav[$count + 1]))
            @php
              $parent = $primary_nav[$count + 1]['menu_item_parent'];
            @endphp
          @endif
          @if (!$nav['menu_item_parent'])
            @php($parent_id = $nav['ID'])
            <li class="primary-nav__list-item js-this">
              <div class="primary-nav__toggle">
                <a href="{{ $nav['url'] }}" title="{{ $nav['title'] }}" class="primary-nav__link font--s font-weight--800">{{ $nav['title'] }}</a>
                <span class="js-toggle" data-prefix="primary-nav__list-item" data-toggled="this"></span>
              </div>
          @endif
          @if ($parent_id == $nav['menu_item_parent'])
            @if (!$submenu)
              @php($submenu = true)
              <ul class="subnav__list">
            @endif
              <li class="subnav__list-item">
                <a href="{{ $nav['url'] }}" class="subnav__link">{{ $nav['title'] }}</a>
              </li>
              @if ($parent != $parent_id && $submenu)
                </ul>
                @php($submenu = false)
              @endif
          @endif
          @if ($parent != $parent_id)
            </li>
            @php($submenu = false)
          @endif
          @php($count++)
        @endforeach
        @php(wp_reset_postdata())
      </ul>
      <div class="nav__social hide-after--m">
        @include('patterns.social-links')
      </div>
    </nav>
  @endif
</div>
