@php
  $id = get_queried_object_id();
  if (is_tax()) {
    $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
    $args = array(
      'post_type' => 'post',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'order' => 'DESC',
      'tax_query' => array(
        array(
          'taxonomy' => $term->taxonomy,
          'field' => 'slug',
          'terms' => $term->slug
        )
      )
    );
  } elseif (is_tag()) {
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
  } elseif (is_archive('work')) {
    $args = array(
      'post_type' => 'work',
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
          <h2 class="font--primary--s">
            @if (is_archive('work'))
              Recent Work
            @else
              Recent Posts
            @endif
          </h2>
          <hr class="divider center-block" />
          @if (get_field('intro'))
            <p class="page-intro">{{ get_field('intro') }}</p>
          @endif
          <?php /* <div id="response" class="filter-response"></div> */ ?>
          @if ($posts->have_posts())
            <div class="grid grid--full">
              @while ($posts->have_posts()) @php($posts->the_post())
                @include('patterns.block')
              @endwhile
              @php(wp_reset_query())
            </div>
            @php
              if (is_tag()) {
                echo do_shortcode('[ajax_load_more tag="' .get_the_category()[0]->slug .'" container_type="div" post_type="post, affiliate" scroll="false" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } elseif (is_category()) {
                echo do_shortcode('[ajax_load_more category="' . get_the_category()[0]->slug .'" container_type="div" post_type="post" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } elseif (is_archive('work')) {
                echo do_shortcode('[ajax_load_more container_type="div" post_type="work" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              } else {
                echo do_shortcode('[ajax_load_more container_type="div" post_type="post" scroll="true" transition_container="false" button_label="Load More" posts_per_page="12" offset="12"]');
              }
            @endphp
          @else
            <p>{{ __('Sorry, no posts were found.', 'sage') }}</p>
            {!! get_search_form(false) !!}
          @endif
        </div>
      </article>
    </div>
  </section>
@endsection
