<div class="accordion spacing">
  <div class="accordion--inner">
    @php($accordion = get_field('accordion'))
    @if ($accordion)
      @foreach ($accordion as $item)
        <div class="accordion-item is-active">
          <div class="accordion-item__title js-toggle-parent">
            <h4 class="font--primary--m">{{ $item['accordion_title'] }}</h4>
            <span class="accordion-item__toggle spacing--zero"></span>
          </div>
          <div class="accordion-item__body article__body spacing padding--zero">
            @php echo wpautop($item['accordion_body']); @endphp
          </div>
        </div>
      @endforeach
    @endif
    @php($instructions = get_field('instructions'))
    @if ($instructions)
      <div class="accordion-item is-active">
        <div class="accordion-item__title js-toggle-parent">
          <h4 class="font--primary--m">Instructions</h4>
          <span class="accordion-item__toggle spacing--zero"></span>
        </div>
        <div class="accordion-item__body article__body spacing padding--zero step">
          @foreach ($instructions as $item)
            <div class="step-item">
              <div class="step-item__number"><span class="font--primary--xs color--gray">Step</span></div>
              <div class="step-item__content spacing">
                @php echo wpautop($item['instructions_content']); @endphp
                @if (!empty($item['instructions_image']))
                  <picture class="block__thumb">
                    <source srcset="{{ $item['instructions_image']['sizes']['horiz__4x3--l'] }}" media="(min-width:800px)">
                    <source srcset="{{ $item['instructions_image']['sizes']['horiz__4x3--l'] }}" media="(min-width:500px)">
                    <img src="{{ $item['instructions_image']['sizes']['horiz__4x3--l'] }}" alt="{{ $item['instructions_image']['alt'] }}">
                  </picture>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
    <div class="accordion-item is-active">
      <div class="accordion-item__title js-toggle-parent">
        <h4 class="font--primary--m">Comments</h4>
        <span class="accordion-item__toggle spacing--zero"></span>
      </div>
      <div class="accordion-item__body article__body spacing padding--zero">
        @include('partials.comments')
      </div>
    </div>
  </div>
</div>
