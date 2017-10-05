{{--
  Template Name: Default Template with Blocks
--}}

@extends('layouts.app')
@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.content-page')
  @endwhile
@endsection
