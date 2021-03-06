<div class="article__share share-tools spacing--half">
  <?php 
    $link = get_the_permalink();
    $title = get_the_title();
    if (get_the_excerpt() != '') {
      $excerpt = wp_trim_excerpt(get_the_excerpt('',FALSE,''));
    } else {
      $excerpt = wp_trim_words(get_the_content('',FALSE,''), 100, '...');
    }
    $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'horiz__4x3--m')[0];
   ?>
  <div class="article__share-title font--primary--xs color--gray">Share</div>
  <a href="https://facebook.com/sharer/sharer.php?u=<?php echo e($link); ?>" class="article__share-link icon icon--m space--half-right" aria-label="Share on Facebook" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Facebook</title><path d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0Zm2.86,6.33h-1c-.81,0-1,.39-1,1V8.53h1.94l-.25,2H10.85v5h-2v-5H7.14v-2H8.83V7.08A2.36,2.36,0,0,1,11.35,4.5a13.86,13.86,0,0,1,1.51.08Z" fill="#393939"/></svg>
  </a>
  <a href="https://twitter.com/intent/tweet/?text=<?php echo e($title . ': ' . $excerpt . ' ' . $link); ?>&amp;url=<?php echo e($link); ?>" class="article__share-link icon icon--m space--half-right" aria-label="Share on Twitter" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Twitter</title><path d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0Zm3.82,8q0,.13,0,.26A5.61,5.61,0,0,1,5.19,13l.47,0a4,4,0,0,0,2.45-.84,2,2,0,0,1-1.84-1.37,2,2,0,0,0,.89,0A2,2,0,0,1,5.58,8.87v0a2,2,0,0,0,.89.25,2,2,0,0,1-.61-2.63A5.6,5.6,0,0,0,9.93,8.52a2,2,0,0,1,3.36-1.8,4,4,0,0,0,1.25-.48,2,2,0,0,1-.87,1.09A3.94,3.94,0,0,0,14.81,7,4,4,0,0,1,13.82,8Z" fill="#393939"/></svg>
  </a>
  <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo e($link); ?>&amp;title=<?php echo e($title); ?>&amp;summary=<?php echo e($excerpt . ' ' . $link); ?>&amp;source=<?php echo e($link); ?>" aria-label="Share on LinkedIn" class="article__share-link icon icon--m space--half-right" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>LinkedIn</title><path d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0ZM8,13.88H6V7.82H8ZM7,7.15a1,1,0,1,1,1-1A1,1,0,0,1,7,7.15Zm8.41,6.73H13.48V11c0-.69,0-1.57-1-1.57s-1.17.75-1.17,1.52v2.92H9.36V7.82h1.86v1h0a2.07,2.07,0,0,1,1.84-1c2,0,2.33,1.22,2.33,2.82Z" fill="#393939"/></svg>
  </a>
  <a href="https://pinterest.com/pin/create/button/?url=<?php echo e($link); ?>&amp;media=<?php echo e($image); ?>&amp;description=<?php echo e($title); ?><?php echo e(': ' . $excerpt . ' ' . $link); ?>" aria-label="Share on Pinterest" class="article__share-link icon icon--m space--half-right" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Pinterest</title><path d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0Zm.73,12.88a1.72,1.72,0,0,1-1.47-.74l-.4,1.53a7.18,7.18,0,0,1-.8,1.68l-.44-.15a6.17,6.17,0,0,1,0-1.84l.75-3.19a2.32,2.32,0,0,1-.19-1c0-.89.52-1.56,1.16-1.56a.81.81,0,0,1,.81.9,12.93,12.93,0,0,1-.53,2.14.93.93,0,0,0,1,1.16c1.14,0,2-1.2,2-2.94A2.53,2.53,0,0,0,9.93,6.3,2.78,2.78,0,0,0,7,9.09a2.5,2.5,0,0,0,.48,1.47.19.19,0,0,1,0,.18l-.18.73c0,.12-.09.14-.21.09a2.88,2.88,0,0,1-1.3-2.49A3.89,3.89,0,0,1,10.1,5.18a3.77,3.77,0,0,1,4,3.71C14.06,11.1,12.66,12.88,10.73,12.88Z" fill="#393939"/></svg>
  </a>
  <a href="mailto:?subject=<?php echo e($title); ?>&amp;body=<?php echo e($excerpt . ' ' . $link); ?>" target="_self" aria-label="Share by E-Mail" class="article__share-link icon icon--m space--half-right" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Email</title><path d="M15.5,13.12H4.5v-6l5.56,3,5.44-3ZM20,10A10,10,0,1,1,10,0,10,10,0,0,1,20,10ZM16.25,6.13H3.75v7.74h12.5Zm-1.83.75H5.6L10.05,9.3Z" fill="#393939"/></svg>
  </a>
</div>
