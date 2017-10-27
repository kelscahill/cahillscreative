@extends('layouts.app')
@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.content-page')
    <section class="section section__services padding--double-bottom narrow narrow--l">
      <div class="grid grid--3-col">
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__web')</span>
            </div>
            <h2 class="font--primary--s">Web &amp; Interactive</h2>
            <hr class="divider" />
            <ul>
              <li>Website Design &amp; Development</li>
              <li>Responsive Web Design</li>
              <li>Content Management Systems (CMS)</li>
              <li>Search Engine Optimization (SEO)</li>
              <li>Social Media &amp; Blogs</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__web')</span>
            </div>
            <h2 class="font--primary--s">Web &amp; Interactive</h2>
            <hr class="divider" />
            <ul>
              <li>Logo Design</li>
              <li>Tagline &amp; Positioning</li>
              <li>Typography &amp; Color Palette</li>
              <li>Branding Guidelines</li>
              <li>Stationery</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__branding')</span>
            </div>
            <h2 class="font--primary--s">Logo &amp; Branding</h2>
            <hr class="divider" />
            <ul>
              <li>Campaign Strategy</li>
              <li>Design</li>
              <li>Copywriting</li>
              <li>Demographic Targeting</li>
              <li>Timeline Scheduling</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__marketing')</span>
            </div>
            <h2 class="font--primary--s">Email Marketing</h2>
            <hr class="divider" />
            <ul>
              <li>Copywriting</li>
              <li>Design</li>
              <li>Mailing Lists</li>
              <li>Print Coordination</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__direct-mail')</span>
            </div>
            <h2 class="font--primary--s">Direct Mail</h2>
            <hr class="divider" />
            <ul>
              <li>Brochures</li>
              <li>Advertising</li>
              <li>Newsletters</li>
              <li>Signage &amp; Environmental</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
        <div class="grid-item">
          <a href="/contact" class="block block__service spacing">
            <div class="round">
              <span class="icon icon--m">@include('patterns/icon__print')</span>
            </div>
            <h2 class="font--primary--s">Print</h2>
            <hr class="divider" />
            <ul>
              <li>Website Design &amp; Development</li>
              <li>Responsive Web Design</li>
              <li>Content Management Systems (CMS)</li>
              <li>Search Engine Optimization (SEO)</li>
              <li>Social Media &amp; Blogs</li>
            </ul>
            <hr class="divider" />
            <div class="btn btn--outline">Start A Project</div>
          </a>
        </div>
      </div>
    </section>
  @endwhile
@endsection
