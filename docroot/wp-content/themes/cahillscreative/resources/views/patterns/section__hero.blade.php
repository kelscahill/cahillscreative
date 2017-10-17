@php
  $id = get_queried_object_id();
  if (is_category()) {
    $thumb_id = 'default';
    $title = 'Blog';
    $excerpt = 'Find free plans and DIY instructions for furniture, home decor, and furniture makeovers.';
    $category = 'Category';

  } else if (is_front_page()) {
    $thumb_id = get_post_thumbnail_id();
    $title = 'We Create';
    $excerpt = get_the_excerpt();
    $category = '';
  } else if (get_post_thumbnail_id()) {
    $thumb_id = get_post_thumbnail_id();
    $title = get_field('display_title');
    $excerpt = get_the_excerpt();
    $category = 'Post Parent';
  }
@endphp
<section class="section section__hero background--cover background-image--{{ $thumb_id }}">
  @if (!empty($thumb_id) || $thumb_id != 'default')
    <style>
      .background-image--{{ $thumb_id }} {
        background-image: url({{ wp_get_attachment_image_src($thumb_id, "featured__hero--s")[0] }});
      }
      @media (min-width: 800px) {
        .background-image--{{ $thumb_id }} {
          background-image: url({{ wp_get_attachment_image_src($thumb_id, "featured__hero--m")[0] }});
        }
      }
      @media (min-width: 1100px) {
        .background-image--{{ $thumb_id }} {
          background-image: url({{ wp_get_attachment_image_src($thumb_id, "featured__hero--l")[0] }});
        }
      }
      @media (min-width: 1600px) {
        .background-image--{{ $thumb_id }} {
          background-image: url({{ wp_get_attachment_image_src($thumb_id, "featured__hero--xl")[0] }});
        }
      }
    </style>
  @endif
  <div class="section__hero--inner spacing">
    @if (!empty($category))
      <div class="kicker font--primary--s color--white">{{ $category }}</div>
    @endif
    <h1 class="section__hero-title font--secondary--xl color--white">{{ $title }}</h1>
    <h2 class="section__hero-subtitle font--primary--m color--white">Websites that kick ass.</h2>
    @if (!empty($excerpt))
      <hr class="divider background-color--white" />
      <p class="section__hero-excerpt color--white">{{ $excerpt }}</p>
    @endif
  </div>
</section>
