<section class="section section__filter filter sticky-filter">
  <div class="filter-toggle js-toggle-parent">
    <span class="font--primary--xs color--gray filter-label"><span class="icon icon--s space--half-right path-fill--gray">@include('patterns.icon__filter')</span>Filter</span>
  </div>
  <form class="filter-wrap">
    @if (is_category('diy'))
      <div class="filter-item__container filter-item__container-projects">
        <div class="filter-item__toggle filter-item__toggle-projects font--primary--s js-toggle-parent">Project</div>
        @php
          $term_projects = get_terms( array(
            'taxonomy' => 'projects',
            'hide_empty' => false,
          ));
        @endphp
        @if ($term_projects)
          <div class="filter-items">
            @foreach ($term_projects as $term)
              <div class="filter-item">
                <input type="checkbox" value="{{ $term->name }}" id="{{ $term->name }}">
                <label for="{{ $term->name }}">{{ $term->name }}</label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="filter-item__container filter-item__container-room">
        <div class="filter-item__toggle filter-item__toggle-room font--primary--s js-toggle-parent">Room</div>
        @php
          $term_room = get_terms( array(
            'taxonomy' => 'room',
            'hide_empty' => false,
          ));
        @endphp
        @if ($term_room)
          <div class="filter-items">
            @foreach ($term_room as $term)
              <div class="filter-item">
                <input type="checkbox" value="{{ $term->name }}" id="{{ $term->name }}">
                <label for="{{ $term->name }}">{{ $term->name }}</label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="filter-item__container filter-item__container-cost">
        <div class="filter-item__toggle filter-item__toggle-cost font--primary--s js-toggle-parent">Cost</div>
        @php
          $term_cost = get_terms( array(
            'taxonomy' => 'cost',
            'hide_empty' => false,
          ));
        @endphp
        @if ($term_cost)
          <div class="filter-items" data-post-type="post" data-type="checkbox" data-parameter="category">
            @foreach ($term_cost as $term)
              <div class="filter-item" id="slider">
                <input type="checkbox" value="{{ $term->name }}" id="{{ $term->name }}">
                <label for="{{ $term->name }}">{{ $term->name }}</label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="filter-item__container filter-item__container-skill is-active">
        <div class="filter-item__toggle filter-item__toggle-skill font--primary--s">Skill Level</div>
        @php
          $term_skill = get_terms( array(
            'taxonomy' => 'skill_levels',
            'hide_empty' => false,
          ));
        @endphp
        @if ($term_skill)
          <div class="filter-items">
            @foreach ($term_skill as $term)
              <div class="filter-item filter-item__{{ $term->slug }} js-toggle">
                <p class="font--sans-serif--small">{{ $term->name }}</p>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="filter-footer">
        <button type="button" class="filter-apply">Apply Filters</button>
        <button type="button" class="filter-clear">Clear Filter</button>
      </div>
    @else
      <select class="filter-item">
        <option selected>Category</option>
        @php
          $term_projects = get_terms( array(
            'taxonomy' => 'category',
            'hide_empty' => true,
          ));
        @endphp
        @foreach ($term_projects as $term)
          <option>{{ $term->name }}</option>
        @endforeach
      </select>
    @endif
  </form>
</section>
