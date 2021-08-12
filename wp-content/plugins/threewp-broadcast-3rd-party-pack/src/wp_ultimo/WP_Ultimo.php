<?php

namespace threewp_broadcast\premium_pack\wp_ultimo;

/**
	@brief			Adds support for the <a href="https://wpultimo.com/">WP Ultimo</a> plugin.
	@plugin_group	3rd party compatability
	@since			2019-07-25 10:31:24
**/
class WP_Ultimo
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\find_unlinked_children_on_blog;

	/**
		@brief		Constructor.
		@since		2019-07-25 10:31:19
	**/
	public function _construct()
	{
		$this->add_action( 'mucd_after_copy_data', 10, 2 );
	}

	/**
		@brief		After copying the files, link the posts.
		@since		2019-07-25 10:31:10
	**/
	public function mucd_after_copy_data( $source_id, $target_id )
	{
		$this->find_unlinked_children_on_blog( [
			'parent_blog_id' => $source_id,
			'child_blog_id' => $target_id,
		] );
	}
}
