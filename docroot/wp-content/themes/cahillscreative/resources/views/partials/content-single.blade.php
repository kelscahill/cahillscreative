<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article spacing--double'))>
      <div class="article__header spacing text-align--center narrow">
        <h2 class="article__header-kicker font--primary--s">Blog</h2>
        <hr class="divider" />
        <h1 class="article__header-title font--secondary--l">{{ the_title() }}</h1>
      </div>
      @php
        $project = get_the_terms($post->ID, 'project');
        $room = get_the_terms($post->ID, 'room');
        $cost = get_the_terms($post->ID, 'cost');
        $skill = get_the_terms($post->ID, 'skill_level');
      @endphp
      @if ($project || $room || $cost || $skill)
        <div class="article__categories narrow">
          @php $project = get_the_terms($post->ID, 'project'); @endphp
          @if ($project)
            <div class="article__category">
              <span class="font--primary--xs">Project</span>
              @foreach ($project as $term)
                <a href="{{ home_url('/') }}{{ $term->taxonomy }}/{{ $term->slug }}">{{ $term->name }}</a>
              @endforeach
            </div>
          @endif
          @php $room = get_the_terms($post->ID, 'room'); @endphp
          @if ($room)
            <div class="article__category">
              <span class="font--primary--xs">Room</span>
              @foreach ($room as $term)
                <a href="{{ home_url('/') }}{{ $term->taxonomy }}/{{ $term->slug }}">{{ $term->name }}</a>
              @endforeach
            </div>
          @endif
          @php $cost = get_the_terms($post->ID, 'cost'); @endphp
          @if ($cost)
            <div class="article__category">
              <span class="font--primary--xs">Cost</span>
              @foreach ($cost as $term)
                <a href="{{ home_url('/') }}{{ $term->taxonomy }}/{{ $term->slug }}">{{ $term->name }}</a>
              @endforeach
            </div>
          @endif
          @php $skill = get_the_terms($post->ID, 'skill_level'); @endphp
          @if ($skill)
            <div class="article__category">
              <span class="font--primary--xs">Skill Level</span>
              @foreach ($skill as $term)
                <a href="{{ home_url('/') }}{{ $term->taxonomy }}/{{ $term->slug }}">{{ $term->name }}</a>
              @endforeach
            </div>
          @endif
        </div>
      @endif
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
                <a href="{{ the_field('etsy_link') }}" class="btn center-block" target="_blank">
                  @if(get_field('etsy_link_text'))
                    {{ the_field('etsy_link_text') }}
                  @else
                    Get DIY Plans
                  @endif
                </a>
                @if(get_field('etsy_link_description'))
                  <small class="space--half-top">{{ the_field('etsy_link_description') }}</small>
                @endif
              @endif
            </div>
            <div class="article__content--right spacing--double shift-right--small">
              @include('patterns.section--gallery')
              @php(the_content())
              @include('patterns.section--accordion')
              @include('patterns.section--pagination')
              <div class="section__favorites--mobile">
                @include('patterns.section--favorites')
              </div>
            </div>
          </div> <!-- ./article__content -->
          @include('partials.sidebar')
        </div> <!-- ./wrap--2-col -->
        <div class="aricle__mobile-footer">
          @if(get_field('etsy_link'))
            <a href="{{ the_field('etsy_link') }}" class="btn btn--outline btn--download hide-after--m" target="_blank">
              @if(get_field('etsy_link_text'))
                {{ the_field('etsy_link_text') }}
              @else
                Get DIY Plans
              @endif
            </a>
          @endif
          <div class="article__toolbar block__toolbar">
            <div class="block__toolbar--left">
              <div class="block__toolbar-item block__toolbar-like space--half-right">
                @if(function_exists('wp_ulike'))
                  @php wp_ulike('get'); @endphp
                @endif
              </div>
              <a href="{{ the_permalink() }}#comments" class="block__toolbar-item block__toolbar-comment space--half-right">
                <span class="icon icon--s space--half-right">@include('patterns/icon--comment')</span>
                <span class="font--sans-serif font--sans-serif--small color--gray">
                  @php
                    comments_number('0', '1', '%');
                  @endphp
                </span>
              </a>
              <div class="block__toolbar-item block__toolbar-share tooltip">
                <div class="block__toolbar-item block__toolbar-share tooltip-toggle js-toggle-parent">
                  <span class="icon icon--s space--half-left">@include('patterns/icon--share')</span>
                </div>
                @include('patterns/share-tooltip')
              </div>
            </div> <!-- ./block__toolbar--left -->
            <div class="block__toolbar--right">
              @php $next_post = get_next_post(true, '', 'category'); @endphp
              @if ( !empty($next_post) )
                @php $link = get_permalink($next_post->ID); @endphp
                <a href="{{ $link }}" class="font--primary--xs">Next Post<span class="icon icon--s">@include('patterns/arrow--carousel')</span></a>
              @endif
            </div> <!-- ./block__toolbar--right -->
          </div> <!-- ./block__toolbar -->
        </div> <!-- ./article__mobile-footer -->
      </div> <!-- ./article__body -->
    </article>
  </div>
</section>
<div class="section__favorites--desktop">
  @include('patterns.section--favorites')
</div>
