<?php
namespace threewp_broadcast\premium_pack\seopress;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/wp-seopress/">SEOPress</a> plugin.
	@plugin_group	3rd party compatability
	@since			2021-11-30 21:30:07
**/
class SEOPress
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_canonical_url' );
	}

	/**
		@brief		threewp_broadcast_canonical_url
		@since		2021-11-30 21:33:50
	**/
	public function threewp_broadcast_canonical_url( $action )
	{
		$seopress_canonical_url = get_post_meta( $action->post->ID, '_seopress_robots_canonical', true );
		// No custom SEOpress canonical? Allow Broadcast to handle the canonical.
		if ( ! $seopress_canonical_url )
			return add_filter( 'seopress_titles_canonical', '__return_false' );

		// Tell Broadcast to not output its own canonical.
		$action->html_tag = false;

		// Alternatively, we can tell Broadcast to output this URL instead.
		$action->url = $seopress_canonical_url;
	}
}
