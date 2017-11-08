<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article spacing--double'))>
      <div class="article__header spacing text-align--center narrow">
        <h2 class="article__header-kicker font--primary--s">Shop</h2>
        <hr class="divider" />
        <h1 class="article__header-title font--secondary--l">{{ the_title() }}</h1>
      </div>
      <div class="article__body narrow narrow--xl">
        <div class="wrap--2-col sticky-parent">
          <div class="article__content shift-left wrap--2-col--small">
            <div class="article__content--left spacing--double sticky shift-left--small">
              @include('patterns.share-tools')
              @if(get_field('affiliate_link'))
                <a href="{{ get_field('affiliate_link') }}" class="btn btn--outline" target="_blank">Buy now</a>
              @endif
            </div>
            <div class="article__content--right spacing--double shift-right--small">
              <div class="article__image">
                @php
                  $thumb_id = get_post_thumbnail_id();
                  $image_small = wp_get_attachment_image_src($thumb_id, 'flex-height--s')[0];
                  $image_medium = wp_get_attachment_image_src($thumb_id, 'flex-height--m')[0];
                  $image_large = wp_get_attachment_image_src($thumb_id, 'flex-height--l')[0];
                  $image_xlarge = wp_get_attachment_image_src($thumb_id, 'flex-height--xl')[0];
                  $image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
                @endphp
                <picture class="article__picture">
                  <source srcset="{{ $image_xlarge }}" media="(min-width:1100px)">
                  <source srcset="{{ $image_large }}" media="(min-width:800px)">
                  <source srcset="{{ $image_medium }}" media="(min-width:500px)">
                  <img src="{{ $image_small }}" alt="{{ $image_alt }}">
                </picture>
              </div>
              @php(the_content())
              @include('patterns.section__pagination')
            </div> <!-- ./article__content--right -->
          </div> <!-- ./article__content -->
          @include('partials.sidebar')
        </div> <!-- ./wrap--2-col -->
        <div class="aricle__mobile-footer">
          @if(get_field('affiliate_link'))
            <a href="{{ get_field('affiliate_link') }}" class="btn btn--outline btn--download hide-after--m" target="_blank"><span class="font--primary--xs">Buy now</a>
          @endif
          <div class="article__toolbar block__toolbar">
            <div class="block__toolbar--left">
              <div class="block__toolbar-item block__toolbar-like space--half-right">
                @if(function_exists('wp_ulike'))
                  @php wp_ulike('get'); @endphp
                @endif
              </div>
              <a href="{{ $link }}#comments" class="block__toolbar-item block__toolbar-comment space--half-right">
                <span class="icon icon--s space--half-right">@include('patterns/icon__comment')</span>
                <span class="font--sans-serif font--sans-serif--small color--gray">
                  @php
                    comments_number('0', '1', '%');
                  @endphp
                </span>
              </a>
              <div class="block__toolbar-item block__toolbar-share tooltip">
                <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
                  <span class="icon icon--s space--half-left">@include('patterns/icon__share')</span>
                </div>
                @include('patterns/share-tooltip')
              </div>
            </div> <!-- ./block__toolbar--left -->
            <div class="block__toolbar--right">
              @php $next_post = get_next_post(true, '', 'category'); @endphp
              @if ( !empty($next_post) )
                @php $link = get_permalink($next_post->ID); @endphp
                <a href="{{ $link }}" class="font--primary--xs">Next Item<span class="icon icon--s path-fill--black">@include('patterns/arrow__carousel')</span></a>
              @endif
            </div> <!-- ./block__toolbar--right -->
          </div> <!-- ./block__toolbar -->
        </div> <!-- ./article__mobile-footer -->
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
@include('patterns.section__favorites')
