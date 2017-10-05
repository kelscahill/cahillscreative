<section class="section section__main">
  <div class="layout-container section__main--inner">
    <article @php(post_class('article narrow spacing--double'))>
      @include('partials.page-header')
      <div class="article__body spacing">
        @php(the_content())
      </div>
    </article>
    @include('partials.sidebar')
  </div>
</section>
