@php
  $link = get_the_permalink();
  $title = get_the_title();
  if (get_the_excerpt() != '') {
    $excerpt = get_the_excerpt('',FALSE,'');
  } else {
    $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
  }
  $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'horiz__4x3--m')[0];
@endphp
<div class="block__toolbar-share-tooltip tooltip-wrap">
  <div class="tooltip-item font--primary--xs text-align--center color--gray">Share Post</div>
  <a aria-label="Share on Facebook" href="https://facebook.com/sharer/sharer.php?u={{ $link }}" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__facebook')</span>Facebook</a>
  <a aria-label="Share on Twitter" href="https://twitter.com/intent/tweet/?text={{ $title }}{{ ': ' . $excerpt }}&amp;url={{ $link }}" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__twitter')</span>Twitter</a>
  <a aria-label="Share on Pinterest" href="https://pinterest.com/pin/create/button/?url={{ $link }}&amp;media={{ $image_medium }}&amp;description={{ $title }}{{ ': ' . $excerpt }}" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__pinterest')</span>Pinterest</a>
  <a aria-label="Share on LinkedIn" href="https://www.linkedin.com/shareArticle?mini=true&amp;url={{ $link }}&amp;title={{ $title }}&amp;summary={{ ': ' . $excerpt }}&amp;source={{ $link }}" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__linkedin')</span>LinkedIn</a>
  <a aria-label="Share by E-Mail" href="mailto:?subject={{ $title }}&amp;body={{ $excerpt }}" target="_self" class="tooltip-item"><span class="icon icon--xs space--half-right path-fill--black">@include('patterns.icon__email')</span>Email</a>
  <div class="tooltip-item tooltip-close font--primary--xs text-align--center background-color--black color--white">Close Share</div>
</div>
