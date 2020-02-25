<?php 
  $title = get_field('modal_title', 'option');
  $kicker = get_field('modal_kicker', 'option');
  $description = get_field('modal_description', 'option');
 ?>
<?php if(get_field('modal_embed_code', 'option')): ?>
  <div id="popup-container" class="popup--overlay popup--hide">
     <div id="popup-window" class="popup">
        <div class="modal-content">
           <div class="popup--inner spacing">
             <?php if($kicker): ?>
               <h4 class="popup__kicker font--primary--xs"><?php echo e($kicker); ?></h4>
               <hr class="divider background-color--white" />
             <?php endif; ?>
             <?php if($title): ?>
               <h3 class="popup__title font--primary--xl"><?php echo e($title); ?></h3>
             <?php endif; ?>
             <?php if($description): ?>
               <div class="popup__body color--gray"><?php echo e($description); ?></div>
             <?php endif; ?>
             <div class="popup__form"><?php  the_field('modal_embed_code', 'option');  ?></div>
             <a href="" class="popup__close" data-dismiss="modal" aria-label="Close"><em>No Thanks</em></a>
           </div>
        </div>
     </div>
  </div>
<?php endif; ?>
