<!doctype html>
<html @php language_attributes() @endphp>
  @include('partials.head')
  <body id="top" @php body_class('body-party page-birthday-party__rsvp') @endphp>
    @php do_action('get_header') @endphp
    <main class="main" role="document">
      <div class="layout-container">
        @while(have_posts()) @php the_post() @endphp
          <article @php post_class('article spacing--double') @endphp>
            <div class="article__title spacing--half">
              @if (get_field('display_title'))
                <h2 class="page-title color--white">{{ get_field('display_title') }}</h2>
              @endif
              @if (get_field('intro'))
                {{ the_field('intro') }}
              @endif
            </div>
            <div class="article__body">
              @php the_content() @endphp
              <p class="space--half-top">Have a question? Email me at <a href="mailto:kelscahill@gmail.com">kelscahill@gmail.com</a></p>
            </div>
          </article>
        @endwhile
      </div>
    </main>
    @php do_action('get_footer') @endphp
    @php wp_footer() @endphp
  </body>
</html>
