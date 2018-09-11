<!doctype html>
<html @php(language_attributes())>
  @include('partials.head')
  <body id="top" @php(body_class('page-birthday-party'))>
    @php(do_action('get_header'))
    <main class="main" role="document">
      <div class="layout-container">
        <article @php(post_class('article spacing--double'))>
          @if (get_field('display_title'))
            <h1 class="page-title color--white">{{ get_field('display_title') }}</h1>
          @endif
          <a href="/birthday-party/invite" class="btn btn--red">Of Course!</a>
          <a href="/birthday-party/decline" class="btn btn--outline">Nah, secrets don't make friends</a>
        </article>
      </div>
    </main>
    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
