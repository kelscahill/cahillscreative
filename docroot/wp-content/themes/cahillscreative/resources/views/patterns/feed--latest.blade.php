@php
  $posts = get_posts(array(
    'posts_per_page' => 2,
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby'	=> 'date',
    'order' => 'DESC',
  ));
@endphp
@if ($posts)
  @foreach ($posts as $post)
    @php
      setup_postdata($post);
      $id = $post->ID;
      $thumb_id = get_post_thumbnail_id($id);
      $image = wp_get_attachment_image_src($thumb_id, 'thumbnail')[0];
      $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
      $title = get_the_title($id);
      $link = get_the_permalink($id);
      $date = get_the_date('F j, Y', $post);
    @endphp
    @include('patterns.block--latest')
  @endforeach
  @php wp_reset_postdata() @endphp
@endif
