@php
  $args = array(
   'post_type' => array(
     'affiliate',
   ),
   'posts_per_page' => 12,
   'post_status' => 'publish',
   'order' => 'DESC',
   'tax_query' => array(
     array(
       'taxonomy' => 'post_tag',
       'field' => 'slug',
       'terms' => 'home-decor'
     )
   )
 );
 $posts = new WP_Query($args);

@endphp
@extends('layouts.app')
@section('content')
  @include('patterns.section--hero')
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article @php post_class('article spacing--double') @endphp>
        <div class="article__body spacing text-align--center">
          <h2 class="font--primary--s">Recent Items</h2>
          <hr class="divider center-block" />
          @if ($posts->have_posts())
            <div class="grid grid--full">
              @while ($posts->have_posts()) @php $posts->the_post() @endphp
                @include('patterns.block')
              @endwhile
              @php wp_reset_query() @endphp
            </div>
            @php echo do_shortcode('[ajax_load_more container_type="div" tag="home-decor" post_type="affiliate" scroll="true" transition_container="false" button_label="Load More Items" posts_per_page="12" offset="12"]'); @endphp
          @else
            <p>{{ __('Sorry, no posts were found.', 'sage') }}</p>
            {!! get_search_form(false) !!}
          @endif
        </div>
      </article>
    </div>
  </section>
@endsection
