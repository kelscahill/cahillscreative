@php
  $args = array(
   'post_type' => 'affiliate',
   'posts_per_page' => -1,
   'post_status' => 'publish',
   'order' => 'DESC',
   'tax_query' => array(
     'relation' => 'AND',
     array(
       'taxonomy' => 'post_tag',
       'field' => 'slug',
       'terms' => 'favorite'
     ),
     array(
       'taxonomy' => 'category',
       'field' => 'slug',
       'terms' => get_the_category()[0]->slug
     )
   )
  );
  $posts = new WP_Query($args);
  $featured_post = get_field('featured_affiliates');
@endphp
@if ($posts->have_posts() || $featured_post)
  <section class="secton section__favorites background-color--white">
    <div class="layout-container section__favorites--inner text-align--center section--padding">
      <h3 class="font--primary--s">Shop My Favorites</h3>
      <hr class="divider" />
      <div class="slick-favorites">
        @if ($featured_post)
          @foreach( $featured_post as $post):
            @php(setup_postdata($post))
            @php
              $post_id = $post->ID;
              $thumb_id = get_post_thumbnail_id($post_id);
              $thumb_size = 'square';
              $link = get_field('affiliate_link', $post_id);
              $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
              $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
              $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
            @endphp
            <a href="{{ $link }}" class="block__favorite block__link spacing" target="_blank">
              @if (!empty($thumb_id))
                <picture class="block__thumb">
                  <source srcset="{{ $image_medium }}" media="(min-width:500px)">
                  <img src="{{ $image_small }}" alt="{{ $alt }}">
                </picture>
              @endif
            </a>
          @endforeach
          @php(wp_reset_postdata())
        @endif
        @while ($posts->have_posts()) @php($posts->the_post())
          @php
            $post_id = get_the_ID();
            $thumb_id = get_post_thumbnail_id($post_id);
            $thumb_size = 'square';
            $link = get_field('affiliate_link');
            $image_small = wp_get_attachment_image_src($thumb_id, $thumb_size . '--s')[0];
            $image_medium = wp_get_attachment_image_src($thumb_id, $thumb_size . '--m')[0];
            $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
          @endphp
          <a href="{{ $link }}" class="block__favorite block__link spacing" target="_blank">
            @if (!empty($thumb_id))
              <picture class="block__thumb">
                <source srcset="{{ $image_medium }}" media="(min-width:500px)">
                <img src="{{ $image_small }}" alt="{{ $alt }}">
              </picture>
            @endif
          </a>
        @endwhile
        @php(wp_reset_query())
      </div>
    </div>
  </section>
@endif
