@extends('layouts.app')
@section('content')
  @include('patterns.section--hero')
  @while(have_posts()) @php the_post() @endphp
    <section class="section section__main">
      <div class="layout-container section__main--inner">
        <article @php post_class('article') @endphp>
          <div class="article__header text-align--center">
            <h1 class="font--primary--xl space--top">Welcome to Cahill&rsquo;s Creative</h1>
            <hr class="divider" />
          </div>
          <div class="article__body text-align--center">
            <div class="narrow narrow--m spacing text-align--center">
              @php the_content() @endphp
            </div>
            <div class="narrow narrow--l">
              <div class="grid grid--3-col">
                <div class="grid-item">
                  <a href="/services" class="block block__service spacing">
                    <div class="round">
                      <span class="icon icon--m">@include('patterns/icon--web')</span>
                    </div>
                    <h2 class="font--primary--s">Web</h2>
                    <hr class="divider" />
                    <p>More than just websites, going digital means email, social media &amp; beyond.</p>
                    <div class="btn btn--outline">Learn More</div>
                  </a>
                </div>
                <div class="grid-item">
                  <a href="/services" class="block block__service spacing">
                    <div class="round">
                      <span class="icon icon--m">@include('patterns/icon--print')</span>
                    </div>
                    <h2 class="font--primary--s">Print</h2>
                    <hr class="divider" />
                    <p>Despite what they want you to believe, print is most definitely alive and kicking.</p>
                    <div class="btn btn--outline">Learn More</div>
                  </a>
                </div>
                <div class="grid-item">
                  <a href="/blog" class="block block__service spacing">
                    <div class="round">
                      <span class="icon icon--m">@include('patterns/icon--blog')</span>
                    </div>
                    <h2 class="font--primary--s">Blog</h2>
                    <hr class="divider" />
                    <p>Find it all from DIY projects, meal plans, or just interesting information.</p>
                    <div class="btn btn--outline">Learn More</div>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>
    <section class="section section__featured-about background-color--white padding--double-top padding--double-bottom">
      <div class="section--inner layout-container">
        <div class="grid grid--50-50">
          <div class="grid-item spacing">
            <div class="spacing--half">
              <h3 class="font--primary--xs color--gray">About</h3>
              <h2 class="font--primary--m">Let’s Get aquinted</h2>
            </div>
            <hr class="divider space--left-zero" />
            <p>I currently reside in Wayne County, PA (Beach Lake, to be exact), but offer my graphic design, branding, and website services to many other areas, including Scranton, Milford, Pike County and the Poconos.</p>
            <p>I specialize in all aspects of branding and website design, and use my experience in graphic design to help clients bring their brand and ideas to life. I enjoy working with small businesses and any individual that’s as passionate about what they do as I am.</p>
            <p>When I’m not working with clients, I enjoy spending time on my own side projects. This helps me stay creatively energized while giving me new challenges to overcome. If you’d like to see some of my recent side work, check out my <a href="/blog" class="text-decoration--underline">blog</a>!</p>
            <a href="/about" class="btn">Learn More</a>
          </div>
          <div class="grid-item">
            <picture class="round">
              <img src="{{ get_bloginfo('template_url') }}/assets/images/headshot.jpg" alt="Kelsey Cahill" />
            </picture>
          </div>
        </div>
      </div>
    </section>
    @php
      $args = array(
        'post_type' => 'post',
        'posts_per_page' => 4,
        'post_status' => 'publish',
        'order' => 'DESC',
        'tax_query' => array(
          array(
            'taxonomy' => 'category',
            'field' => 'slug',
            'terms' => 'health',
            'operator' => 'NOT IN'
          )
        )
      );
      $featured_posts = new WP_Query($args);
    @endphp
    @if ($featured_posts)
      <section class="section section__featured-posts layout-container padding--double-top padding--double-bottom">
        <div class="grid grid--4-col">
          @while ($featured_posts->have_posts()) @php $featured_posts->the_post() @endphp
            @include('patterns.block')
          @endwhile
          @php wp_reset_query() @endphp
        </div>
      </section>
    @endif
    @php
      $args = array(
        'post_type' => 'work',
        'posts_per_page' => 2,
        'post_status' => 'publish',
        'order' => 'DESC',
        'tax_query' => array(
          array(
            'taxonomy' => 'post_tag',
            'field' => 'slug',
            'terms' => 'featured'
          )
        )
      );
      $featured_work = new WP_Query($args);
    @endphp
    @if ($featured_work)
      <section class="section section__featured-work">
        @while ($featured_work->have_posts()) @php $featured_work->the_post() @endphp
          @include('patterns.block--featured')
        @endwhile
        @php wp_reset_query() @endphp
      </section>
    @endif
  @endwhile
@endsection
