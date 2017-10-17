@extends('layouts.app')
@section('content')
  <section class="section section__main">
    <div class="layout-container">
      <article @php(post_class('article narrow--xl center-block spacing--double'))>
      @include('partials.page-header')
        @if (have_posts())
          @while (have_posts()) @php(the_post())
            @php
              $id = get_the_ID();
              $title = get_the_title($id);
              $excerpt = get_the_excerpt($id);
              $thumb_id = get_post_thumbnail_id($id);
              $link = get_permalink($id);
              $date = date('F j, Y', strtotime(get_the_date()));
            @endphp
            @include('patterns.block')
          @endwhile
            @php echo do_shortcode('[ajax_load_more container_type="div" css_classes="spacing--double" post_type="post, page" scroll="false" transition_container="false" button_label="Load More" posts_per_page="5" offset="5"]'); @endphp
        @else
          <p>{{ __('Sorry, no results were found.', 'sage') }}</p>
          {!! get_search_form(false) !!}
        @endif
      </article>
    </div>
  </section>
@endsection
