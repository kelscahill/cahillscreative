<?php

namespace threewp_broadcast\premium_pack\elementor;

use Exception;

/**
	* @brief		Add a handler for [elementor-tag].
	* @since		2025-03-02 16:41:11
**/
class Elementor_Tag
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'elementor-tag';
	}

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		if ( ! isset( $item->product_id ) )
			return false;
		$new_product_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $item->product_id, get_current_blog_id() );
		$item->new_shortcode = str_replace( $item->product_id, $new_product_id, $item->shortcode );
	}

	/**
		@brief		Finalize the item before it is saved.
		@details	Currently just checks that the ID attribute exists.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		try
		{
			if ( ! isset( $item->attributes[ 'settings' ] ) )
				throw Exception( 'No settings.' );

			$settings = $item->attributes[ 'settings' ];
			$old_settings_decoded = urldecode( $settings );
			$old_settings_decoded = json_decode( $old_settings_decoded );

			if ( ! isset( $old_settings_decoded->product_id ) )
				throw new Exception( 'No product ID' );
			$item->product_id = $old_settings_decoded->product_id;
		}
		catch ( Exception $e )
		{
		}

		unset( $item->id );
	}
}
