<div class="page-header spacing--double">
  @if (is_singular('events'))
    <div class="kicker color--gray">
      <span class="icon icon--m icon--events space--half-right"></span>
      <p class="font--m">Event</p>
    </div>
  @elseif (is_single())
    <div class="kicker color--gray">
      <span class="icon icon--m icon--news space--half-right"></span>
      <p class="font--m">News</p>
    </div>
  @elseif (get_queried_object() && get_queried_object()->post_parent != 0)
    @php
      $id = get_queried_object();
      $page_parent = $id->post_parent;
    @endphp
    <div class="kicker color--gray">
      @if (get_field('page_icon', $page_parent))
        <span class="icon icon--m icon--{{ the_field('page_icon', $page_parent) }} space--half-right"></span>
      @endif
      <p class="font--m">{{ get_the_title($page_parent) }}</p>
    </div>
  @elseif (get_field('page_icon', get_the_ID()))
    <div class="kicker color--gray">
      <span class="icon icon--m icon--{{ the_field('page_icon', get_the_ID()) }} space--half-right"></span>
      <p class="font--m">{{ get_the_title(get_the_ID()) }}</p>
    </div>
  @else
  @endif
  <h1 class="page-title font--primary--l">
    @if (get_field('display_title'))
      {{ the_field('display_title') }}
    @else
      {!! App\title() !!}
    @endif
  </h1>
  @if (get_field('intro'))
    <div class="page-intro">@php echo wpautop(the_field('intro')); @endphp</div>
  @endif
  @if (get_field('link_url'))
    <a class="btn" href="{{ the_field('link_url') }}">@if (get_field('link_text')){{ the_field('link_text') }}@else{{ 'Learn More' }}@endif</a>
  @endif
</div>
