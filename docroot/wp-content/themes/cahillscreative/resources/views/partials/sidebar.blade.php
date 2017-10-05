<aside class="sidebar">
  @if (get_field('sidebar_title') || get_field('sidebar_body'))
    <div class="block block__freeform">
      <div class="block__content spacing">
        @if (get_field('sidebar_title'))
          <h3>{{ the_field('sidebar_title')}}</h3>
          <hr />
        @endif
        <p>{{ the_field('sidebar_body') }}</p>
        @if (get_field('sidebar_link_url'))
          <a class="btn" href="{{ the_field('sidebar_link_url') }}">@if (get_field('sidebar_link_text')){{ the_field('sidebar_link_text') }}@else{{ 'Learn More' }}@endif</a>
        @endif
      </div>
    </div>
  @elseif (is_singular('events'))
    @php
      $today = date('Y-m-d H:m:s');
      $posts = get_posts(array(
        'posts_per_page' => 3,
        'post_type' => 'events',
        'post_status' => 'publish',
        'meta_key' => 'event_start_date_time',
        'orderby'	=> 'meta_value',
	      'order' => 'ASC',
        'meta_query' => array(
          array(
            'key'  => 'event_start_date_time',
            'compare'   => '>=',
            'value'     => $today,
          ),
        ),
      ));
    @endphp
    <div class="block block__news-feed spacing--half">
      <h3>Upcoming Events</h3>
      @if ($posts)
        @foreach ($posts as $post)
          @php
            $date = get_post_meta($post->ID, 'event_start_date_time', true);
            $date = date('F j, Y', strtotime($date));
          @endphp
          <div class="block__row">
            <p class="block__row-title"><a href="{{ $post->guid }}" class="block__link">{{ $post->post_title }}</a></p>
            <span class="block__row-meta font--s color--gray">{{ $date }}</span>
          </div>
        @endforeach
        @php(wp_reset_postdata())
      @else
        <div class="block__row">
          <span class="block__meta font--s color--gray">No events at this time.</span>
        </div>
      @endif
      <div class="block__row">
        <a href="/life-at-wilkes/calendar" class="btn space--quarter-top">View More</a>
      </div>
    </div>
  @else
    @php
      $posts = get_posts(array(
        'posts_per_page' => 3,
        'post_type' => 'post',
        'post_status' => 'publish',
        'category_name' => 'news',
      ));
    @endphp
    @if ($posts)
      <div class="block block__news-feed spacing--half">
        <h3>The Latest</h3>
        @foreach ($posts as $post)
          @php
            $date = $post->post_date;
            $date = date('F j, Y', strtotime($date));
          @endphp
          <div class="block__row">
            <p class="block__row-title"><a href="{{ $post->guid }}" class="block__row-link">{{ $post->post_title }}</a></p>
            <span class="block__row-meta font--s color--gray">{{ $date }}</span>
          </div>
        @endforeach
        @php(wp_reset_postdata())
        <div class="block__row">
          <a href="/news" class="btn space--quarter-top">View More</a>
        </div>
      </div>
    @endif
  @endif
</aside>
