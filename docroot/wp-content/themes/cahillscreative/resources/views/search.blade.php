@php
  global $wp_query;
  $total_results = $wp_query->found_posts;
@endphp
@extends('layouts.app')
@section('content')
  <section class="section section__main">
    <div class="layout-container">
      <article @php(post_class('article narrow--xl center-block spacing--double'))>
        <div class="page-header spacing text-align--center narrow narrow--m">
          <h2 class="page-kicker font--primary--s">Search Results for</h2>
          <hr class="divider">
          <h1 class="page-title">{{ get_search_query() }}</h1>
          <div class="page-intro">
            <p>{{ $total_results }} total results found.</p>
          </div>
        </div>
        @if (have_posts())
          @php echo do_shortcode('[ajax_load_more css_classes="spacing" post_type="post, affiliate" search="'. get_search_query() .'" orderby="relevance" posts_per_page="12" scroll="true" button_label="Show More Results" transition_container="false"]'); @endphp
        @else
          <p class="text-align--center space--zero">{{ __('Sorry, no results were found.', 'sage') }}</p>
          {!! get_search_form(false) !!}
        @endif
      </article>
    </div>
  </section>
@endsection
