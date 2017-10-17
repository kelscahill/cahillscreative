@php
  $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
  $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
  $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
@endphp
<div class="block block__post background-color--white">
  <a href="{{ $link }}" class="block__link spacing">
    @if (!empty($thumb_id))
      <picture class="block__thumb">
        <source srcset="{{ $image_medium }}" media="(min-width:500px)">
        <img src="{{ $image_small }}" alt="{{ $alt }}">
      </picture>
    @endif
    <div class="block__content spacing--half">
      @if (!empty($kicker))
        <div class="block__kicker font--primary--xs color--gray">
          {{ $kicker[0]->name }}
        </div>
      @endif
      <div class="block__title font--primary--m color--black">
        {{ $title }}
      </div>
      <div class="block__meta color--gray">
        @include('partials.entry-meta')
      </div>
    </div>
  </a>
  <div class="block__toolbar">
    <div class="block__toolbar--left">
      <div class="block__toolbar-item block__toolbar-like space--right">
        @if(function_exists('wp_ulike'))
          @php wp_ulike('get'); @endphp
        @endif
      </div>
      <a href="{{ $link }}#comments" class="block__toolbar-item block__toolbar-comment space--right">
        <span class="icon icon--m space--half-right">@include('patterns/icon__comment')</span>
        <span class="font--sans-serif font--sans-serif--small color--gray">
          @php
            comments_number('0', '1', '%');
          @endphp
        </span>
      </a>
    </div>
    <div class="block__toolbar--right tooltip">
      <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
        <span class="font--primary--xs color--gray">Share</span>
        <span class="icon icon--m space--half-left">@include('patterns/icon__share')</span>
      </div>
      <div class="block__toolbar-share-tooltip tooltip-wrap">
        <div class="tooltip-item font--primary--xs text-align--center color--gray">Share Post</div>
        <div data-title="{{ $title }}" data-image="{{ $image_small }}" data-description="{{ $excerpt }}" data-url="{{ $link }}" data-network="facebook" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__facebook')</span>Facebook</div>
        <div data-title="{{ $title }}" data-image="{{ $image_small }}" data-description="{{ $excerpt }}" data-url="{{ $link }}" data-network="twitter" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__twitter')</span>Twitter</div>
        <div data-title="{{ $title }}" data-image="{{ $image_small }}" data-description="{{ $excerpt }}" data-url="{{ $link }}" data-network="pinterest" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__pinterest')</span>Pinterest</div>
        <div data-title="{{ $title }}" data-image="{{ $image_small }}" data-description="{{ $excerpt }}" data-url="{{ $link }}" data-network="linkedin" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__linkedin')</span>LinkedIn</div>
        <div data-title="{{ $title }}" data-image="{{ $image_small }}" data-description="{{ $excerpt }}" data-url="{{ $link }}" data-network="email" class="st-custom-button tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__email')</span>Email</div>
        <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
      </div>
    </div>
  </div>
</div>
