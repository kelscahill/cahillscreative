<aside class="sidebar shift-right spacing--double">
  <div class="widget widget-search">
    @include('patterns/form__search')
  </div>
  <div class="widget widget-related">
    <h3 class="font--primary--xs">Related Posts</h3>
    <hr />
    @php related_posts() @endphp
  </div>
  <div class="widget widget-mailing">
    <h3 class="font--primary--xs">Join My Mailing List!</h3>
    @include('patterns/form__newsletter')
  </div>
  @php $tags = get_the_tags(); @endphp
  @if ($tags)
    <div class="widget widget-tags">
      <h3 class="font--primary--xs">Tags</h3>
      <hr />
      <div class="tags">
        @foreach ($tags as $tag)
          <div class="tag">
            <a href="{{ home_url('/') }}tag/{{ $tag->slug }}">{{ $tag->name }}</a>
          </div>
        @endforeach
      </div>
    </div>
  @endif
  <div class="widget widget-courtousy">
    <h3 class="font--primary--xs">Blog Courtousy</h3>
    <hr />
    <p>Hi! I'm all for sharing the love, but if you do share one of my photos or try out a DIY idea/design of mine, please make sure to link back to the original post and give proper credit. All photos, images, and content on this site, unless otherwise stated, are created by Cahill's Creative and should be credited as so.</p> 
  </div>
  <div class="desktop-only">
    <div class="widget sticky-ad">
      <h5 class="font--primary--xs color--black">Advertisements</h5>
      <hr />
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
</aside>
