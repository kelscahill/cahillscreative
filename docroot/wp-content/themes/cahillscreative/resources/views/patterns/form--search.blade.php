<form action="/" method="get" class="form form__search">
  <input class="js-hover-parent" placeholder="Search" type="text" name="s" id="search" value="<?php the_search_query(); ?>" />
  <button class="js-hover-parent"><span class="icon icon--m">@include('patterns.icon__search')</span></button>
</form>
