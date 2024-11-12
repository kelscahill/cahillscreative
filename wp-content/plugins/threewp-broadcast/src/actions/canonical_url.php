<?php

namespace threewp_broadcast\actions;

/**
	@brief		Return the canonical URL for this child.
	@since		2021-11-15 19:06:33
**/
class canonical_url
	extends action
{
	/**
		@brief		Disable the Wordpress internal rel_canonical action.
		@since		2021-11-15 19:08:33
	**/
	public $disable_rel_canonical = true;

	/**
		@brief		[OUT] The HTML tag, ready to have the canonical URL sprintf'd into it.
		@details	Set to false to not output html tag at all, but continue processing the canonical.
		@since		2021-11-15 19:10:06
	**/
	public $html_tag = '<link rel="canonical" href="%s" />' . "\n";

	/**
		@brief		IN: The linked parent.
		@since		2021-11-15 19:07:31
	**/
	public $linked_parent;

	/**
		@brief		IN: The child post object.
		@since		2021-11-15 19:06:45
	**/
	public $post;

	/**
		@brief		IN/OUT: The canonical URL for this child.
		@details	Set to false to not process the canonical anymore.
		@since		2021-11-15 19:07:03
	**/
	public $url;
}
