@php
  $thumb_id = get_post_thumbnail_id();
  $body = get_the_content();
  $category = strip_tags(get_the_tag_list('',', ',''));
  $title = get_the_title();
  $link = get_permalink();
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
    <div class="page-header spacing text-align--center narrow narrow--m">
      @if (!empty($category))
        <h2 class="page-kicker font--primary--s color--white">{{ $category }}</h2>
        <hr class="divider background-color--white">
      @endif
      <h1 class="page-title color--white">{{ $title }}</h1>
    </div>
  </div>
</section>

<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article spacing--double'))>
      <div class="article__body narrow spacing--double">
        <div class="narrow narrow--s spacing text-align--center ">
          {{ $body }}
          <a href="{{ $link }}" class="btn btn--outline center-block">View Website</a>
        </div>
        <div class="work">
          <div class="work-item">
            <div class="work-item__title">
            </div>
            @foreach ($work as $post)
              <div class="work-item__image">
              </div>
            @endforeach
          </div>
        </div>
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
