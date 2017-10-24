<aside class="sidebar shift-right spacing--double">
  <div class="widget widget-search">
    <?php echo $__env->make('patterns/form__search', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  </div>
  <?php  $tags = get_the_tags();  ?>
  <?php if($tags): ?>
    <div class="widget widget-tags">
      <h3 class="font--primary--xs">Tags</h3>
      <hr />
      <div class="tags">
        <?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="tag">
            <a href="<?php echo e(home_url('/')); ?>tag/<?php echo e($tag->slug); ?>"><?php echo e($tag->name); ?></a>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  <?php endif; ?>
  <div class="widget widget-related">
    <h3 class="font--primary--xs">Related Posts</h3>
    <hr />
    <?php  related_posts()  ?>
  </div>
  <div class="widget widget-mailing">
    <h3 class="font--primary--xs">Join My Mailing Lists!</h3>
    <?php echo $__env->make('patterns/form__newsletter', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  </div>
  <div class="widget widget-courtousy">
    <h3 class="font--primary--xs">Blog Courtousy</h3>
    <hr />
    <p>Hi! I'm all for sharing the love, but if you do share one of my photos or try out a DIY idea/design of mine, please make sure to link back to the original post and give proper credit. All photos, images, and content on this site, unless otherwise stated, are created by Cahill's Creative and should be credited as so.</p> 
  </div>
  <div class="widget sticky-ad">
    <h5 class="font--primary--xs color--black">Advertisements</h5>
    <hr />
    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <!-- Sidebar Ad -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:300px;height:600px"
         data-ad-client="ca-pub-3133257559155527"
         data-ad-slot="5922592630"></ins>
    <script>
    (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
  </div>
</aside>
