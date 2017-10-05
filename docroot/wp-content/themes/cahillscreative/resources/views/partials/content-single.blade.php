@include('patterns.breadcrumbs')
<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article narrow spacing'))>
      @include('partials.page-header')
      <div class="article__body spacing">
        @php
          $thumb_id = get_post_thumbnail_id();
          $image_small = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--s')[0];
          $image_medium = wp_get_attachment_image_src($thumb_id, 'horiz__16x9--m')[0];
          $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
        @endphp
        <picture class="block__thumb">
          <source srcset="{{ $image_medium }}" media="(min-width:500px)">
          <img src="{{ $image_small }}" alt="{{ $image_alt }}">
        </picture>
        <div class="article__content space--double-top">
          <div class="article__content--left">
            @include('partials/entry-meta')
          </div>
          <div class="article__content--right spacing--double">
            @php(the_content())
            @include('patterns.share-tools')
          </div>
        </div>
      </div>
    </article>
    @include('partials.sidebar')
  </div>
</section>
