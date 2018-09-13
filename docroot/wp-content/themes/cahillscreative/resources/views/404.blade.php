@extends('layouts.app')
@section('content')
  <section class="section section__main">
    <div class="layout-container section__main--inner">
      <article @php post_class('article narrow spacing--double') @endphp>
        <div class="page-header spacing text-align--center narrow narrow--m">
          <h2 class="page-kicker font--primary--s">404</h2>
          <hr class="divider">
          <h1 class="page-title">Page Not Found</h1>
          <div class="page-intro">
            <p>We couldn't find the page you were looking for. Please go back to <a href="{{ home_url() }}" class="text-link">home</a> or try the search below.</p>
          </div>
        </div>
        {!! get_search_form(false) !!}
      </article>
    </div>
  </section>
@endsection
