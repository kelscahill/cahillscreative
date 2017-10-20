<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article spacing--double'))>
      <div class="article__header spacing text-align--center narrow">
        <h2 class="article__header-kicker font--primary--s">Blog</h2>
        <hr class="divider" />
        <h1 class="article__header-title font--secondary--l">{{ the_title() }}</h1>
      </div>
      <div class="article__categories narrow">
        @php $project = get_terms('project'); @endphp
        @if ($project)
          <div class="article__category">
            <span class="font--primary--xs">Project</span>
            @foreach ($project as $term)
              <p>{{ $term->name }}</p>
            @endforeach
          </div>
        @endif
        @php $room = get_terms('room'); @endphp
        @if ($room)
          <div class="article__category">
            <span class="font--primary--xs">Room</span>
            @foreach ($room as $term)
              <p>{{ $term->name }}</p>
            @endforeach
          </div>
        @endif
        @php $cost = get_terms('cost'); @endphp
        @if ($cost)
          <div class="article__category">
            <span class="font--primary--xs">Cost</span>
            @foreach ($cost as $term)
              <p>{{ $term->name }}</p>
            @endforeach
          </div>
        @endif
        @php $skill = get_terms('skill_level'); @endphp
        @if ($skill)
          <div class="article__category">
            <span class="font--primary--xs">Skill Level</span>
            @foreach ($skill as $term)
              <p>{{ $term->name }}</p>
            @endforeach
          </div>
        @endif
      </div>
      <div class="article__gallery narrow narrow--xl">
        @include('patterns.section__gallery')
      </div>
      <div class="article__body narrow narrow--xl">
        <div class="wrap--2-col sticky-parent">
          <div class="article__content shift-left wrap--2-col--small">
            <div class="article__content--left spacing--double sticky shift-left--small">
              <div class="author-meta spacing--half">
                <div class="author-meta__image round center-block">
                  @php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); @endphp
                </div>
                <div class="author-meta__name font--primary--xs">
                  {{ get_the_author_meta('first_name') }} {{ get_the_author_meta('last_name') }}
                </div>
                <hr class="divider" />
                @include('partials/entry-meta')
              </div>
              @include('patterns.share-tools')
              @if(get_field('etsy_link'))
                <a href="{{ get_field('etsy_link') }}" class="btn btn--outline" target="_blank"><span class="font--primary--xs">Download</span><font>PDF Plans</font></a>
              @endif
            </div>
            <div class="article__content--right spacing--double shift-right--small">
              @php(the_content())
              @include('patterns.accordion')
            </div>
          </div> <!-- ./article__content -->
          @include('partials.sidebar')
        </div> <!-- ./wrap--2-col -->
        <div class="aricle__mobile-footer">
          @if(get_field('etsy_link'))
            <a href="{{ get_field('etsy_link') }}" class="btn btn--outline btn--download hide-after--m" target="_blank"><span class="font--primary--xs">Download</span><font>PDF Plans</font></a>
          @endif
          <div class="article__toolbar block__toolbar">
            <div class="block__toolbar--left">
              <div class="block__toolbar-item block__toolbar-like space--right">
                @if(function_exists('wp_ulike'))
                  @php wp_ulike('get'); @endphp
                @endif
              </div>
              <a href="{{ $link }}#comments" class="block__toolbar-item block__toolbar-comment space--right">
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
            </div> <!-- ./block__toolbar--left -->
            <div class="block__toolbar--right">
              @php $next_post = get_next_post(); @endphp
              @if ( !empty($next_post) )
                @php $link = get_permalink($next_post->ID); @endphp
                <a href="{{ $link }}" class="font--primary--xs">Next Post<span class="icon icon--xs">@include('patterns/arrow__carousel')</span></a>
              @endif
            </div> <!-- ./block__toolbar--right -->
          </div> <!-- ./block__toolbar -->
        </div> <!-- ./article__mobile-footer -->
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
