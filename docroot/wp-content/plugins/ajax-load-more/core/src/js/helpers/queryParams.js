/*
	almGetAjaxParams
	Build the data object to send with the Ajax request

   @param alm            object
   @param action         string
   @param queryType      string  
   
   @since 3.6
*/

let almGetAjaxParams = function(alm, action, queryType){

	// Defaults
	let data = {
      action               : action,
      nonce						: alm_localize.alm_nonce,
      query_type           : queryType,
      id							: alm.id,
      post_id					: alm.post_id,
      slug                 : alm.slug,
      canonical_url        : alm.canonical_url,
      posts_per_page       : alm.posts_per_page,
      page           		: alm.page,
      offset               : alm.offset,
      post_type				: alm.post_type,
      repeater					: alm.repeater,
      seo_start_page    	: alm.start_page
   }; 
   
   
   // Addons
   if(alm.theme_repeater){
      data.theme_repeater = alm.theme_repeater;
   }  
   if(alm.paging){
      data.paging = alm.paging;
   }  
   if(alm.preloaded){
      data.preloaded = alm.preloaded;
      data.preloaded_amount = alm.preloaded_amount;
   }
   if(alm.cache === 'true'){
      data.cache_id = alm.cache_id;
      data.cache_logged_in = alm.cache_logged_in;
   }	 
   if(alm.acf_array){
      data.acf = alm.acf_array;
   } 
   if(alm.cta_array){
      data.cta = alm.cta_array;
   } 
   if(alm.comments_array){
      data.comments = alm.comments_array;
   } 
   if(alm.nextpage_array){
      data.nextpage = alm.nextpage_array;
   } 
   if(alm.previous_post_array){
      data.previous_post = alm.previous_post_array;
   }
   if(alm.users_array){
      data.users = alm.users_array;
   }
   
   
   // Query data   
   if(alm.content.attr('data-lang')){
      data.lang = alm.content.attr('data-lang');
   }
   if(alm.content.attr('data-sticky-posts')){
      data.sticky_posts = alm.content.attr('data-sticky-posts');
   }
   if(alm.content.attr('data-post-format')){
      data.post_format = alm.content.attr('data-post-format');
   }
   if(alm.content.attr('data-category')){
      data.category = alm.content.attr('data-category');
   }
   if(alm.content.attr('data-category-not-in')){
      data.category__not_in = alm.content.attr('data-category-not-in');
   }
   if(alm.content.attr('data-tag')){
      data.tag = alm.content.attr('data-tag');
   }
   if(alm.content.attr('data-tag-not-in')){
      data.tag__not_in = alm.content.attr('data-tag-not-in');
   }
   if(alm.content.attr('data-taxonomy')){
      data.taxonomy = alm.content.attr('data-taxonomy');
   }
   if(alm.content.attr('data-taxonomy-terms')){
      data.taxonomy_terms = alm.content.attr('data-taxonomy-terms');
   }
   if(alm.content.attr('data-taxonomy-operator')){
      data.taxonomy_operator = alm.content.attr('data-taxonomy-operator');
   }
   if(alm.content.attr('data-taxonomy-relation')){
      data.taxonomy_relation = alm.content.attr('data-taxonomy-relation');
   }
   if(alm.content.attr('data-meta-key')){
      data.meta_key = alm.content.attr('data-meta-key');
   }
   if(alm.content.attr('data-meta-value')){
      data.meta_value = alm.content.attr('data-meta-value');
   }
   if(alm.content.attr('data-meta-compare')){
      data.meta_compare = alm.content.attr('data-meta-compare');
   }
   if(alm.content.attr('data-meta-relation')){
      data.meta_relation = alm.content.attr('data-meta-relation');
   }
   if(alm.content.attr('data-meta-type')){
      data.meta_type = alm.content.attr('data-meta-type');
   }
   if(alm.content.attr('data-author')){
      data.author = alm.content.attr('data-author');
   }
   if(alm.content.attr('data-year')){
      data.year = alm.content.attr('data-year');
   }
   if(alm.content.attr('data-month')){
      data.month = alm.content.attr('data-month');
   }
   if(alm.content.attr('data-day')){
      data.day = alm.content.attr('data-day');
   }
   if(alm.content.attr('data-order')){
      data.order = alm.content.attr('data-order');
   }
   if(alm.content.attr('data-orderby')){
      data.orderby = alm.content.attr('data-orderby');
   }
   if(alm.content.attr('data-post-status')){
      data.post_status = alm.content.attr('data-post-status');
   }
   if(alm.content.attr('data-post-in')){
      data.post__in = alm.content.attr('data-post-in');
   }
   if(alm.content.attr('data-post-not-in')){
      data.post__not_in = alm.content.attr('data-post-not-in');
   }
   if(alm.content.attr('data-exclude')){
      data.exclude = alm.content.attr('data-exclude');
   }
   if(alm.content.attr('data-search')){
      data.search = alm.content.attr('data-search');
   }
   if(alm.content.attr('data-s')){
      data.search = alm.content.attr('data-s');
   }
   if(alm.content.attr('data-custom-args')){
      data.custom_args = alm.content.attr('data-custom-args');
   }
   
   return data;
   
}



/*
	almGetRestParams
	Build the REST API data object to send with REST API request

   @param alm            object
   
   @since 3.6
*/
let almGetRestParams = function(alm){
	let data = {
		id						: alm.id,
	   post_id				: alm.post_id,
	   posts_per_page    : alm.posts_per_page,
	   page              : alm.page,
	   offset            : alm.offset,
	   slug              : alm.slug,
	   canonical_url     : alm.canonical_url,
	   post_type         : alm.post_type,
	   post_format       : alm.content.attr('data-post-format'),
	   category          : alm.content.attr('data-category'),
	   category__not_in  : alm.content.attr('data-category-not-in'),
	   tag               : alm.content.attr('data-tag'),
	   tag__not_in       : alm.content.attr('data-tag-not-in'),
	   taxonomy          : alm.content.attr('data-taxonomy'),
	   taxonomy_terms    : alm.content.attr('data-taxonomy-terms'),
	   taxonomy_operator : alm.content.attr('data-taxonomy-operator'),
	   taxonomy_relation : alm.content.attr('data-taxonomy-relation'),
	   meta_key          : alm.content.attr('data-meta-key'),
	   meta_value        : alm.content.attr('data-meta-value'),
	   meta_compare      : alm.content.attr('data-meta-compare'),
	   meta_relation     : alm.content.attr('data-meta-relation'),
	   meta_type         : alm.content.attr('data-meta-type'),
	   author            : alm.content.attr('data-author'),
	   year              : alm.content.attr('data-year'),
	   month             : alm.content.attr('data-month'),
	   day               : alm.content.attr('data-day'),
	   post_status       : alm.content.attr('data-post-status'),
	   order             : alm.content.attr('data-order'),
	   orderby           : alm.content.attr('data-orderby'),
	   post__in          : alm.content.attr('data-post-in'),
	   post__not_in      : alm.content.attr('data-post-not-in'),
	   search            : alm.content.attr('data-search'),
	   custom_args       : alm.content.attr('data-custom-args'),
	   lang              : alm.lang,
	   preloaded         : alm.preloaded,
	   preloaded_amount  : alm.preloaded_amount,
	   seo_start_page    : alm.start_page
   };
   return data;
}