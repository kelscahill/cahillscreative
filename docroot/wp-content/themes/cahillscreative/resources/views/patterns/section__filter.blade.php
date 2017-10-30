<section class="section section__filter filter sticky-filter">
  <div class="filter-toggle js-toggle-parent">
    <span class="font--primary--xs color--gray filter-label"><span class="icon icon--s space--half-right path-fill--gray">@include('patterns.icon__filter')</span>Filter</span>
  </div>
  <form class="filter-wrap"  method="POST" id="filter">
    @if (is_category('diy'))
      <div class="filter-item__container filter-item__container-projects">
        <div class="filter-item__toggle filter-item__toggle-projects font--primary--s js-toggle-parent">Project</div>
        @php
          $term_projects = get_terms( array(
            'taxonomy' => 'project',
            'hide_empty' => false,
          ));
        @endphp
        @if ($term_projects)
          <div class="filter-items">
            @foreach ($term_projects as $term)
              <div class="filter-item">
                <input type="checkbox" value="{{ $term->term_id }}, " id="{{ $term->slug }}" name="projects">
                <label for="{{ $term->slug }}">{{ $term->name }}</label>
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
                <input type="checkbox" value="{{ $term->term_id }}, " id="{{ $term->slug }}" name="room">
                <label for="{{ $term->slug }}">{{ $term->name }}</label>
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
          <div class="filter-items">
            @foreach ($term_cost as $term)
              <div class="filter-item" name="categoryfilter">
                <input type="checkbox" value="{{ $term->term_id }}, " id="{{ $term->slug }}" name="cost">
                <label for="{{ $term->slug }}">{{ $term->name }}</label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="filter-item__container filter-item__container-skill">
        <div class="filter-item__toggle filter-item__toggle-skill font--primary--s js-toggle-parent">Skill Level</div>
        <div class="filter-items" data-type="checkbox" data-parameter="category">
          <div class="filter-item">
            <input type="checkbox" value="beginner" id="beginner" name="skill_level">
            <label for="beginner">Beginner</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" value="intermediate" id="intermediate" name="skill_level">
            <label for="intermediate">Intermediate</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" value="advanced" id="advanced" name="skill_level">
            <label for="advanced">Advanced</label>
          </div>
        </div>
      </div>
      <div class="filter-footer">
        <button type="submit" class="filter-apply">Apply Filters</button>
        <button type="submit" class="filter-clear">Clear Filter</button>
        <input type="hidden" name="action" value="myfilter">
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
