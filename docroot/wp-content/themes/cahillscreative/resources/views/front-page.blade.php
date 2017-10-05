@extends('layouts.app')
@section('content')
  @while (have_posts()) @php(the_post())
  @endwhile
@endsection
