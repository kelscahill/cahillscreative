@php($gallery = get_field('gallery'))
@if ($gallery)
  <div class="article__gallery gallery slick-gallery">
    @foreach ($gallery as $image)
      <div class="gallery__image">
        <picture class="gallery__picture">
          <source srcset="{{ $image['sizes']['flex-height--l'] }}" media="(min-width:650px)">
          <source srcset="{{ $image['sizes']['flex-height--m'] }}" media="(min-width:400px)">
          <img src="{{ $image['sizes']['flex-height--s'] }}" alt="{{ $image['alt'] }}">
        </picture>
        @if ($image['caption'])
          <div class="gallery__caption">
            {{ $image['caption'] }}
          </div>
        @endif
      </div>
    @endforeach
  </div>
@else
  <div class="article__image">
    @php
      $thumb_id = get_post_thumbnail_id();
      $image_small = wp_get_attachment_image_src($thumb_id, 'flex-height--s')[0];
      $image_medium = wp_get_attachment_image_src($thumb_id, 'flex-height--m')[0];
      $image_large = wp_get_attachment_image_src($thumb_id, 'flex-height--l')[0];
      $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
    @endphp
    <picture class="article__picture">
      <source srcset="{{ $image_large }}" media="(min-width:650px)">
      <source srcset="{{ $image_medium }}" media="(min-width:400px)">
      <img src="{{ $image_small }}" alt="{{ $image_alt }}">
    </picture>
  </div>
@endif
