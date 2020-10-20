<?php

namespace threewp_broadcast\premium_pack\soliloquy;

/**
	@brief			Detect the soliloquy shortcode.
	@since			2020-07-30 09:57:42
**/
class Soliloquy_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	public function copy_item( $bcd, $item )
	{
		if ( isset( $item->attributes[ 'slug' ] ) )
			$item->new_shortcode = $item->shortcode;
		return parent::copy_item( $bcd, $item );
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'soliloquy';
	}

	/**
		@brief		Finalize the item before it is saved.
		@details	Currently just checks that the ID attribute exists.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		if ( ! isset( $item->attributes[ 'id' ] ) )
		{
			if ( isset( $item->attributes[ 'slug' ] ) )
			{
				// Try and find the slider.
				$posts = get_posts( [
					'post_name' => $item->attributes[ 'slug' ],
					'post_type' => 'soliloquy',
				] );
				if ( count ( $posts ) == 1 )
				{
					$post = reset( $posts );
					$item->attributes[ 'id' ] = $post->ID;
				}
			}
		}
		return parent::finalize_item( $item );
	}
}
