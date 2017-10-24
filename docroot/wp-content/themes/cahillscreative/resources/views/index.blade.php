@php
  $id = get_queried_object_id();
  if (is_tag()) {
    $args = array(
     'post_type' => array(
       'post',
       'affiliate',
     ),
     'posts_per_page' => 12,
     'post_status' => 'publish',
     'order' => 'DESC',
     'tax_query' => array(
       array(
         'taxonomy' => 'post_tag',
         'field' => 'slug',
         'terms' => get_cat_name($id)
       )
     )
   );
  } elseif (is_category()) {
     $args = array(
      'post_type' => 'post',
      'category_name' => get_cat_name($id),
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
    );
  } else {
    $args = array(
      'post_type' => 'post',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
    );
  }
  $posts = new WP_Query($args);
@endphp
@extends('layouts.app')
@section('content')
  @include('patterns.section__hero')
  <?php /* @include('patterns.section__filter') */ ?>
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article @php(post_class('article spacing--double'))>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">Recent Posts</h2>
          <hr class="divider center-block" />
          @if (get_field('intro'))
            <p class="page-intro">{{ get_field('intro') }}</p>
          @endif
          <?php /* <div id="response" class="filter-response"></div> */ ?>
          @if ($posts->have_posts())
            <div class="grid grid--full">
              @while ($posts->have_posts()) @php($posts->the_post())
                @php
                  $post_id = get_the_ID();
                  $title = get_the_title($post_id);
                  $thumb_id = get_post_thumbnail_id($post_id);
                  $thumb_size = 'square';
                  $link = get_permalink($post_id);
                  $date = date('F j, Y', strtotime(get_the_date($post_id)));
                  $post_type = get_post_type($post_id);
                  if (get_the_excerpt() != '') {
                    $excerpt = get_the_excerpt('',FALSE,'');
                  } else {
                    $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
                  }
                  if ($post_type == 'affiliate') {
                    $kicker = 'Shop';
                  } else {
                    $kicker = get_the_category($post_id)[0]->name;
                  }
                @endphp
                <div class="grid-item">
                  @include('patterns.block')
                </div>
              @endwhile
              @php(wp_reset_query())
            </div>
            @if (is_tag())
              @php echo do_shortcode('[ajax_load_more css_classes="spacing" container_type="div" post_type="post, affiliate" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]'); @endphp
            @else
              @php echo do_shortcode('[ajax_load_more css_classes="spacing" container_type="div" post_type="post" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]'); @endphp
            @endif
          @else
            <p>{{ __('Sorry, no results were found.', 'sage') }}</p>
            {!! get_search_form(false) !!}
          @endif
        </div>
      </article>
    </div>
  </section>
@endsection
