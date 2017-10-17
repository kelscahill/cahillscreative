@extends('layouts.app')
@section('content')
  @include('patterns.section__hero')
  @while(have_posts()) @php(the_post())
    <section class="section section__main">
      <div class="layout-container section__main--inner">
        <article @php(post_class('article'))>
          <div class="article__header text-align--center">
            <h1 class="font--primary--xl">Welcome to Cahill's Creative</h1>
            <hr class="divider" />
          </div>
          <div class="article__body narrow narrow--m spacing">
            @php(the_content())
          </div>
        </article>
      </div>
    </section>
  @endwhile
@endsection
