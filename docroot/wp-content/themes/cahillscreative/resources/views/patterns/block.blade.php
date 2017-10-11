@php
  $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
  $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
  $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
@endphp
<div class="block background-color--white">
  <a href="{{ $link }}" class="block__link">
    @if (!empty($thumb_id))
      <picture class="block__thumb">
        <source srcset="{{ $image_medium }}" media="(min-width:500px)">
        <img src="{{ $image_small }}" alt="{{ $alt }}">
      </picture>
    @endif
    <div class="block__content spacing--half">
      @if (!empty($kicker))
        <div class="block__kicker font--primary--xs">
          {{ $kicker[0]->name }}
        </div>
      @endif
      <div class="block__title font--primary--m">
        {{ $title }}
      </div>
      <div class="block__meta color--gray">
        @include('partials.entry-meta')
      </div>
    </div>
  </a>
  <div class="block__toolbar">
    <div class="block__toolbar--left">
      <div class="block__toolbar-like space--right">
        <span class="icon icon--s space--half-right">@include('patterns/icon__like')</span>
        <span class="font--primary--xs color--gray">
          @if(function_exists('wp_ulike'))
            @php wp_ulike('get'); @endphp
          @endif
        </span>
      </div>
      <div class="block__toolbar-comment space--right">
        <span class="icon icon--s space--half-right">@include('patterns/icon__comment')</span>
        <span class="font--primary--xs color--gray">
          @php
            comments_number('0', '1', '%');
          @endphp
        </span>
      </div>
    </div>
    <div class="block__toolbar--right">
      <div class="block__toolbar-share">
        <span class="font--primary--xs color--gray">Share</span>
        <span class="icon icon--s space--half-left">@include('patterns/icon__share')</span>
      </div>
    </div>
  </div>
</div>
