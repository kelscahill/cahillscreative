@php($gallery = get_field('gallery'))
@if ($gallery)
  <div class="gallery spacing">
    @if (get_field('gallery_title'))
      <h3 class="gallery__title">{{ the_field('gallery_title') }}</h3>
    @endif
    <div class="slick slick-gallery">
      @foreach ($gallery as $image)
        <div class="gallery__image">
          <picture class="block__thumb">
            <source srcset="{{ $image['sizes']['horiz__16x9--m'] }}" media="(min-width:500px)">
            <img src="{{ $image['sizes']['horiz__4x3--s'] }}" alt="{{ $image['alt'] }}">
          </picture>
          @if ($image['caption'])
            <div class="gallery__caption">
              {{ $image['caption'] }}
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
@endif
