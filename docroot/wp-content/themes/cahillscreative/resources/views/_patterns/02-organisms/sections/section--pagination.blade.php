@php
  $prev_post = get_previous_post(true, '', 'category');
  $next_post = get_next_post(true, '', 'category');
@endphp
<div class="article__nav">
  <div class="article__nav--inner">
    @if (!empty($prev_post))
      @php
        $prev_link = $prev_post->guid;
        $prev_title = $prev_post->post_title;
      @endphp
      <a href="{{ $prev_link }}" class="article__nav-item previous">
        <div class="article__nav-item-label font--primary--xs">
          <span class="icon icon--xs">@include('patterns/arrow--previous')</span><font>Previous</font>
        </div>
        <div class="font--primary--s">{{ $prev_title }}</div>
      </a>
    @endif
  </div>
  <div class="article__nav--inner">
    @if (!empty($next_post))
      @php
        $next_link = $next_post->guid;
        $next_title = $next_post->post_title;
      @endphp
      <a href="{{ $next_link }}" class="article__nav-item next">
        <div class="article__nav-item-label font--primary--xs">
          <font>Next</font><span class="icon icon--xs">@include('patterns/arrow--next')</span>
        </div>
        <div class="font--primary--s">{{ $next_title }}</div>
      </a>
    @endif
  </div>
</div>
