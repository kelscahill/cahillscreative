<div class="block block__latest">
  <a href="{{ $link }}" class="block__link">
    @if (!empty($thumb_id))
      <picture class="block__thumb round space--right">
        <img src="{{ $image }}" alt="{{ $alt }}">
      </picture>
    @endif
    <div class="block__content">
      <div class="block__title font--primary--xs">
        {{ $title }}
      </div>
      <div class="block__meta color--gray">
        @include('partials.entry-meta')
      </div>
    </div>
  </a>
</div>
