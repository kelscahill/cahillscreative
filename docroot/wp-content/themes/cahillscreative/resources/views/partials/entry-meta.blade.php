@if (is_singular('events'))
  <p class="color--gray font--s">
    @php
      $start_date = get_post_meta($post->ID, 'event_start_date_time', true);
      $start_date_string = date('F j, Y', strtotime($start_date));
      $start_time = date('g:i a', strtotime($start_date));

      $end_date = get_post_meta($post->ID, 'event_end_date_time', true);
      $end_time = date('g:i a', strtotime($end_date));

      $all_day = get_post_meta($post->ID, 'event_duration', true);
    @endphp
    {{ $start_date_string }}<br />
    @if ($all_day == true)
      All day
    @else
      {{ $start_time }}@if ($end_date) {{ ' &ndash; ' . $end_time }} @endif
    @endif
  </p>
@else
  <time class="updated color--gray font--s" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
@endif
