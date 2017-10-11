@php
  // Display news post by date
  if (is_category('diy')) {
    $category = 'diy';
  } elseif (is_category('health')) {
    $category = 'health';
  } else {
    $category = 'uncategorized';
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
  <?php //@include('patterns.hero-image') ?>
  <section class="section section__main">
    <div class="layout-container">
      <div class="filter">
        @if (is_category('diy'))
          @php echo do_shortcode( '[searchandfilter fields="projects, rooms, skill, cost" types="select" hide_empty="1" submit_label="Filter"]' ); @endphp
          @php echo do_shortcode( '[searchandfilter fields="cost" types="select" hide_empty="1" submit_label="Filter"]' ); @endphp
        @else
          <?php echo do_shortcode( '[searchandfilter taxonomies="category"]' ); ?>
        @endif
      </div>
    </div>
      @if ($posts->have_posts())
        <div class="narrow--xl center-block spacing--double">
          @while ($posts->have_posts()) @php($posts->the_post())
            <div class="grid grid--3-col">
              @php
                $id = get_the_ID();
                $title = get_the_title($id);
                $excerpt = get_the_excerpt($id);
                $thumb_id = get_post_thumbnail_id($id);
                $thumb_size = 'horiz__4x3';
                $kicker = get_the_category($id);
                $link = get_permalink($id);
                $date = date('F j, Y', strtotime(get_the_date()));
              @endphp
              <div class="grid-item">
                @include('patterns.block')
              </div>
            @endwhile
          </div>
          @php(wp_reset_query())
          @php echo do_shortcode('[ajax_load_more container_type="div" css_classes="spacing--double" post_type="post" scroll="false" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]'); @endphp
        </div>
      @else
        <p>{{ __('Sorry, no results were found.', 'sage') }}</p>
        {!! get_search_form(false) !!}
      @endif
    </div>
  </section>
@endsection
