<!doctype html>
<html @php language_attributes() @endphp>
  @include('partials.head')
  <body id="top" @php body_class('body-party page-birthday-party__decline') @endphp>
    @php do_action('get_header') @endphp
    <main class="main" role="document">
      <div class="layout-container">
        @while(have_posts()) @php the_post() @endphp
          <article @php post_class('article spacing--double') @endphp>
            <div class="article__title">
              @if (get_field('display_title'))
                <h2 class="page-title color--white">{{ get_field('display_title') }}</h2>
              @endif
            </div>
            <div class="article__dot">
              <h3>You</h3>
              <span class="dot"></span>
            </div>
            <div class="article__body">
              @if (get_field('intro'))
                {{ the_field('intro') }}
              @endif
              @php the_content() @endphp
            </div>
          </article>
        @endwhile
      </div>
    </main>
    @php do_action('get_footer') @endphp
    @php wp_footer() @endphp
  </body>
</html>
