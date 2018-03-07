<div class="grid-item">
  @php
    $post_id = get_the_ID();
    $title = get_the_title($post_id);
    $thumb_id = get_post_thumbnail_id($post_id);
    $thumb_size = 'square';
    $link = get_permalink($post_id);
    $post_type = get_post_type($post_id);
    if (get_the_excerpt() != '') {
      $excerpt = get_the_excerpt('',FALSE,'');
    } else {
      $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
    }
    if ($post_type == 'affiliate') {
      if (get_the_terms($post_id, 'store')) {
        $tags = get_the_terms($post_id, 'store');
        $kicker = $tags[0]->name;
      } else {
        $kicker = NULL;
      }
      $date = NULL;
      $tags = NULL;
    } else if ($post_type == 'work') {
      $kicker = NULL;
      $date = NULL;
      $tags = strip_tags(get_the_tag_list('',', ',''));
    } else {
      $kicker = get_the_category($post_id)[0]->name;
      $date = date('F j, Y', strtotime(get_the_date()));
    }
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
            {{ $kicker}}
          </div>
        @endif
        <div class="block__title font--primary--m color--black">
          {{ $title }}
        </div>
        @if (!empty($tags))
          <div class="block__meta color--gray">
            <span class="color--gray font--s">{{ $tags }}</span>
          </div>
        @endif
        @if ($date)
          <div class="block__meta color--gray">
            <time class="updated color--gray font--s" datetime="{{ get_post_time('c', true) }}">{{ $date }}</time>
          </div>
        @endif
      </div>
    </a>
    <div class="block__toolbar">
      <div class="block__toolbar--left">
        <div class="block__toolbar-item block__toolbar-like space--half-right">
          @if(function_exists('wp_ulike'))
            @php wp_ulike('get'); @endphp
          @endif
        </div>
        @if (comments_open())
          <a href="{{ $link }}#comments" class="block__toolbar-item block__toolbar-comment space--half-right">
            <span class="icon icon--s space--half-right">@include('patterns/icon--comment')</span>
            <span class="font--sans-serif font--sans-serif--small color--gray">
              @php
                comments_number('0', '1', '%');
              @endphp
            </span>
          </a>
        @endif
      </div>
      <div class="block__toolbar--right tooltip">
        <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
          <span class="font--primary--xs color--gray">Share</span>
          <span class="icon icon--s space--half-left">@include('patterns/icon--share')</span>
        </div>
        @include('patterns/share-tooltip')
      </div>
    </div>
  </div>
</div>
