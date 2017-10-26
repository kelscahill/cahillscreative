<div class="grid-item">
  @php
    $post_id = get_the_ID();
    $title = get_the_title($post_id);
    $thumb_id = get_post_thumbnail_id($post_id);
    $link = get_permalink($post_id);
    $kicker = 'Featured Work';
    $tags = strip_tags(get_the_tag_list('',', ',''));
  @endphp
  <div class="block block__featured">
    <a href="{{ $link }}" class="block__link spacing background--cover background-image--{{ $thumb_id }} image-overlay">
      @if (!empty($thumb_id))
        <style>
          .background-image--{{ $thumb_id }} {
            background-image: url({{ wp_get_attachment_image_src($thumb_id, "square--s")[0] }});
          }
          @media (min-width: 800px) {
            .background-image--{{ $thumb_id }} {
              background-image: url({{ wp_get_attachment_image_src($thumb_id, "square--m")[0] }});
            }
          }
          @media (min-width: 1100px) {
            .background-image--{{ $thumb_id }} {
              background-image: url({{ wp_get_attachment_image_src($thumb_id, "square--l")[0] }});
            }
          }
        </style>
      @endif
      <div class="block__content">
        <div class="spacing--half">
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
        </div>
        <div class="block__button">
          View Project
        </div>
      </div>
    </a>
  </div>
</div>
