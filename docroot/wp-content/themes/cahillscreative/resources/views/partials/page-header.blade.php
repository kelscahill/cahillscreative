<div class="page-header spacing text-align--center narrow narrow--m">
  @if (get_field('display_title'))
    <h2 class="page-kicker font--primary--s">{{ the_title() }}</h2>
    <hr class="divider" />
    <h1 class="page-title">{{ the_field('display_title') }}</h1>
  @else
    <h1 class="page-title">{{ the_title() }}</h1>
    <hr class="divider" />
  @endif
  @if (get_field('intro'))
    <div class="page-intro">
      {{ the_field('intro') }}
    </div>
  @endif
</div>
