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
            <div class="step-item sticky-parent">
              <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
              <div class="step-item__content spacing">
                @php echo wpautop($item['instructions_content']); @endphp
                @php $images = $item['instructions_image']; @endphp
                @if ($images)
                  @foreach ($images as $image)
                    @if ($image['caption'])
                      <div class="instructions__caption text-align--center space--double-top">
                        <h5 class="font--primary--xs">{{ $image['caption'] }}</h5>
                        <h6 class="font--s color--gray">{{ $image['description'] }}</h6>
                      </div>
                    @endif
                    <picture class="block__thumb">
                      <source srcset="{{ $image['sizes']['flex-height--m'] }}" media="(min-width:1300px)">
                      <source srcset="{{ $image['sizes']['flex-height--l'] }}" media="(min-width:900px)">
                      <source srcset="{{ $image['sizes']['flex-height--m'] }}" media="(min-width:400px)">
                      <img src="{{ $image['sizes']['flex-height--s'] }}" alt="{{ $image['alt'] }}">
                    </picture>
                  @endforeach
                @endif
              </div>
            </div>
          @endforeach
          @if(get_field('etsy_link'))
            <a href="{{ get_field('etsy_link') }}" class="btn center-block" target="_blank">Get DIY Plans</a>
            <small class="space--half-top text-align--center mobile-only">*The plans include a material cut list, a list of necessary tools &amp; hardware, assembly directions, and dimensions.</small>
          @endif
          <div class="mobile-only">
            <script type="text/javascript">
              amzn_assoc_placement = "adunit0";
              amzn_assoc_tracking_id = "cahillscreati-20";
              amzn_assoc_ad_mode = "search";
              amzn_assoc_ad_type = "smart";
              amzn_assoc_marketplace = "amazon";
              amzn_assoc_region = "US";
              amzn_assoc_title = "";
              amzn_assoc_default_search_phrase = "farmhouse";
              amzn_assoc_default_category = "All";
              amzn_assoc_linkid = "40f320132f46fee72104109bb69529c8";
              amzn_assoc_search_bar = "false";
            </script>
            <script src="//z-na.amazon-adsystem.com/widgets/onejs?MarketPlace=US"></script>
          </div>
        </div>
      </div>
    @endif
    <div class="accordion-item">
      <div class="accordion-item__title js-toggle-parent">
        <h4 class="font--primary--m">Comments</h4>
        <span class="accordion-item__toggle spacing--zero"></span>
      </div>
      <div class="accordion-item__body article__body spacing padding--zero">
        @php comments_template( '/partials/comments.blade.php', true ); @endphp
      </div>
    </div>
  </div>
</div>
