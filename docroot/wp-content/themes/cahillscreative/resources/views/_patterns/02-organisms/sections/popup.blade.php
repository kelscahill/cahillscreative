@php
  $title = get_field('modal_title', 'option');
  $kicker = get_field('modal_kicker', 'option');
  $description = get_field('modal_description', 'option');
@endphp
@if (get_field('modal_embed_code', 'option'))
  <div id="popup-container" class="popup--overlay popup--hide">
     <div id="popup-window" class="popup">
        <div class="modal-content">
           <div class="popup--inner spacing">
             @if ($kicker)
               <h4 class="popup__kicker font--primary--xs">{{ $kicker }}</h4>
               <hr class="divider background-color--white" />
             @endif
             @if ($title)
               <h3 class="popup__title font--primary--xl">{{ $title }}</h3>
             @endif
             @if ($description)
               <div class="popup__body color--gray">{!! $description !!}</div>
             @endif
             <div class="popup__form">@php the_field('modal_embed_code', 'option'); @endphp</div>
             <a href="" class="popup__close" data-dismiss="modal" aria-label="Close"><em>No Thanks</em></a>
           </div>
        </div>
     </div>
  </div>
@endif
