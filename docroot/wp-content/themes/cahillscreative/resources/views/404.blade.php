@extends('layouts.app')
@section('content')
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article @php(post_class('article narrow spacing'))>
        @include('partials.page-header')
        <p>{{ __('Sorry, no results were found.', 'sage') }}</p>
        {!! get_search_form(false) !!}
      </article>
    </div>
  </section>
@endsection
