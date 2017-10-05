@php
  $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
  $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
  $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
@endphp
<div class="block block__featured">
  <a href="{{ $link }}" class="block__media">
    <div class="block__overlay"></div>
    @if (!empty($thumb_id))
      <picture class="block__thumb">
        <source srcset="{{ $image_medium }}" media="(min-width:500px)">
        <img src="{{ $image_small }}" alt="{{ $alt }}">
      </picture>
    @endif
  </a>
  <a href="{{ $link }}" class="block__content block__hover padding spacing--half @if(!empty($excerpt)){{ 'hover' }}@endif">
    <div class="block__header spacing--half">
      @if (!empty($parent_id))
        <div class="block__kicker kicker">
          @if (get_field('page_icon', $parent_id))
            <span class="icon icon--m icon--{{ the_field('page_icon', $parent_id) }} space--half-right"></span>
          @endif
          <p class="font--m color--white">{{ get_the_title($parent_id) }}</p>
        </div>
      @endif
      <h3 class="link--cta link--cta--white font--primary--m"><div class="block__title">{{ $title }}</div><</h3>
    </div>
    @if(!empty($excerpt))
      <div class="block__excerpt">
        <p class="color--white">{{ $excerpt }}</p>
      </div>
    @endif
  </a>
</div>
