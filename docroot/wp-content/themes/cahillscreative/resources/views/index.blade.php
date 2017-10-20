@php
  // Display posts by category
  if (is_category('diy')) {
    $category = 'diy';
  } elseif (is_category('health')) {
    $category = 'health';
  } else {
    $category = '';
  }
  $posts = new WP_Query(array(
    'post_type' => 'post',
    'category_name' => $category,
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'order' => 'DESC',
  ));
@endphp
@extends('layouts.app')
@section('content')
  @include('patterns.section__hero')
  @include('patterns.section__filter')
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article @php(post_class('article spacing--double'))>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">Recent Posts</h2>
          <hr class="divider center-block" />
          @php(the_content())
          <div id="response" class="filter-response"></div>
          @if ($posts->have_posts())
            <div class="grid grid--full">
              @while ($posts->have_posts()) @php($posts->the_post())
                @php
                  $id = get_the_ID();
                  $title = get_the_title($id);
                  $excerpt = get_the_excerpt($id);
                  $thumb_id = get_post_thumbnail_id($id);
                  $thumb_size = 'square';
                  $kicker = get_the_category($id);
                  $link = get_permalink($id);
                  $date = date('F j, Y', strtotime(get_the_date()));
                @endphp
                <div class="grid-item">
                  @include('patterns.block')
                </div>
              @endwhile
              @php(wp_reset_query())
            </div>
            @php echo do_shortcode('[ajax_load_more container_type="div" css_classes="spacing--double" post_type="post" scroll="false" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]'); @endphp
          @else
            <p>{{ __('Sorry, no results were found.', 'sage') }}</p>
            {!! get_search_form(false) !!}
          @endif
        </div>
      </article>
    </div>
  </section>
@endsection
