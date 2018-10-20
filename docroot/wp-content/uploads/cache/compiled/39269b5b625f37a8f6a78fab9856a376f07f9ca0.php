<form action="/" method="get" class="form form__search">
  <input placeholder="Search" type="text" name="s" id="search" value="<?php the_search_query(); ?>" />
  <button><span class="icon icon--s"><?php echo $__env->make('patterns.icon__search', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?></span></button>
</form>
