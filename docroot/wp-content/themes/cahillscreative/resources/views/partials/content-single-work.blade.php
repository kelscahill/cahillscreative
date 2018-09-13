@php
  $thumb_id = get_field('featured_banner_image')['ID'];
  $body = get_the_content();
  $category = strip_tags(get_the_tag_list('',', ',''));
  $title = get_the_title();
  $link = get_field('website_url');
  $featured_thumb_id = get_field('featured_work_image')['ID'];
@endphp
<section class="section section__hero background--cover background-image--{{ $thumb_id }} image-overlay">
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
</section>

<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php post_class('article spacing--double') @endphp>
      <div class="article__header spacing text-align--center narrow narrow--m">
        @if (!empty($category))
          <h2 class="page-kicker font--primary--s color--white">{{ $category }}</h2>
          <hr class="divider background-color--white">
        @endif
        <h1 class="page-title color--white">{{ $title }}</h1>
        <?php if (!empty($featured_thumb_id)): ?>
          <picture class="block__thumb">
            <source srcset="{{ wp_get_attachment_image_src($featured_thumb_id, 'flex-height--l')[0] }}" media="(min-width:900px)">
            <source srcset="{{ wp_get_attachment_image_src($featured_thumb_id, 'flex-height--m')[0] }}" media="(min-width:650px)">
            <img src="{{ wp_get_attachment_image_src($featured_thumb_id, 'flex-height--s')[0] }}" alt="{{ get_post_meta($featured_thumb_id, '_wp_attachment_image_alt', true) }}">
          </picture>
        <?php endif; ?>
      </div>
      <div class="article__body narrow spacing--double">
        <div class="narrow narrow--s spacing text-align--center">
          <p>{{ $body }}</p>
          @if ($link)
            <a href="{{ $link }}" class="btn btn--outline center space--top" target="_blank">View Website</a>
          @endif
        </div>
        @if(have_rows('work'))
          @while (have_rows('work'))
            @php
              the_row();
              $work_section_title = get_sub_field('work_section_title');
              $work_section_images = get_sub_field('work_section_images');
            @endphp
            <div class="work">
              <div class="work-item spacing--double">
                <div class="work-item__title">
                  <span class="font--primary--s">{{ $work_section_title }}</span>
                </div>
                @foreach ($work_section_images as $image)
                  <div class="work-item__image">
                    <picture class="work__image">
                      <source srcset="{{ wp_get_attachment_image_src($image['ID'], 'flex-height--l')[0] }}" media="(min-width:900px)">
                      <source srcset="{{ wp_get_attachment_image_src($image['ID'], 'flex-height--m')[0] }}" media="(min-width:650px)">
                      <img src="{{ wp_get_attachment_image_src($image['ID'], 'flex-height--s')[0] }}" alt="{{ get_post_meta($image['ID'], '_wp_attachment_image_alt', true) }}">
                    </picture>
                  </div>
                @endforeach
              </div>
            </div>
          @endwhile
        @endif
        <div class="article__share-work">
          @include('patterns/share-tools')
        </div>
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
@php
  $prev_post = get_previous_post(true, '', 'category');
  $next_post = get_next_post(true, '', 'category');
@endphp
@if ($prev_post || $next_post)
  <section class="section section__pagination background-color--off-white">
  	<div class="narrow narrow--xl">
      <div class="pagination">
        <div class="pagination-item">
          @if (!empty($prev_post))
            <a href="{{ $prev_post->guid }}" class="prev pagination-link">
        			<span class="icon icon--l">@include('patterns/arrow--carousel')</span>
              <p class="font--primary--xs">Previous Project</p>
        		</a>
          @endif
        </div>
        <div class="pagination-item">
      		<a href="/work" class="all pagination-link">
            <span class="icon icon--l">@include('patterns/icon--close--large')</span>
            <p class="font--primary--xs">All Work</p>
          </a>
        </div>
        <div class="pagination-item">
          @if (!empty($next_post))
        		<a href="{{ $next_post->guid }}" class="next pagination-link">
        			<span class="icon icon--l">@include('patterns/arrow--carousel')</span>
              <p class="font--primary--xs">Next Project</p>
        		</a>
          @endif
        </div>
      </div>
  	</div>
  </section>
@endif
