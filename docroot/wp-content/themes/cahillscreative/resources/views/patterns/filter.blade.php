@php
  $id = get_queried_object_id();
  if (is_page_template("views/template-events.blade.php")) {
    $filter = 'Calendar';
  } else {
    $filter = 'News';
  }
@endphp
<section class="nav-bar nav-bar background-color--quaternary color--white">
  <div class="nav-bar--inner flex-justify--space-between layout-container js-this">
    <div class="nav-bar--left">
      <div class="nav-bar__label">
        @if (get_field('page_icon', $id))
          <span class="icon icon--m icon--{{ the_field('page_icon', $id) }} space--half-right"></span>
        @endif
        <p class="font--m">Refine {{ $filter }}</p>
      </div>
      <div class="nav-bar__dropdown">
        <div class="nav-bar__toggle js-toggle" data-prefix="nav-bar--inner" data-toggled="this">
          <p class="filter-label">{{ $filter_label }}</p>
          <span class="icon icon--xs icon--arrow path-fill--white space--half-left">
            @include('patterns.arrow--small')
          </span>
        </div>
        <ul class="nav-bar__list secondary-nav">
          @if (is_page_template("views/template-events.blade.php"))
            @foreach ($output as $key)
              @php
                $link = get_permalink();
                $link_url = strtolower(date('F-Y', strtotime($key)));
                $link_text = date('F Y', strtotime($key));
              @endphp
              <li class="nav-bar__list-item secondary-nav__item">
                <a class="nav-bar__list-link" href="{{ $link }}?filter={{ $link_url }}">{{ $link_text }}</a>
              </li>
            @endforeach
          @else
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="{{ $link }}?orderby=title&order=ASC" class="filter-link secondary-nav__link">By Title</a>
            </li>
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="{{ $link }}?orderby=date&order=DESC" class="filter-link secondary-nav__link">By Newer Posts</a>
            </li>
            <li class="secondary-nav__list-item nav-bar__list-item">
              <a href="{{ $link }}?orderby=date&order=ASC" class="filter-link secondary-nav__link">By Older Posts</a>
            </li>
          @endif
        </ul>
      </div>
    </div>
    <div class="nav-bar--right hide-until--m">
      @if (is_page_template("views/template-events.blade.php"))
        <a href="https://calendar.google.com/calendar/embed?src=wilkesschool.org_3ik6a6kkni39p84u8bgkid05tc%40group.calendar.google.com&ctz=America/New_York" class="nav-bar__subscribe link--cta link--cta--white font--m color--white" target="_blank">Subscribe to Calendar<span class="icon icon--arrow icon--s space--half-left">@include('patterns.arrow--small')</span></a>
      @else
        <a href="http://wilkesschool.us16.list-manage.com/subscribe/post?u=44b1b0d9f882aa37c64c004cf&id=7857dacd0c" class="nav-bar__subscribe link--cta link--cta--white font--m color--white" target="_blank">Sign Up for Updates<span class="icon icon--arrow icon--s space--half-left">@include('patterns.arrow--small')</span></a>
      @endif
    </div>
  </div>
</section>
