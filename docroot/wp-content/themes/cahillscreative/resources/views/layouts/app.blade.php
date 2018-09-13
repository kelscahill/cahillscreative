<!doctype html>
<html @php language_attributes() @endphp>
  @include('partials.head')
  <div class="overlay"></div>
  <body id="top" @php body_class() @endphp>
    @php do_action('get_header') @endphp
    @include('partials.header')
    <main class="main" role="document">
      @yield('content')
    </main>
    @php do_action('get_footer') @endphp
    @include('partials.footer')
    @php wp_footer() @endphp
  </body>
</html>
