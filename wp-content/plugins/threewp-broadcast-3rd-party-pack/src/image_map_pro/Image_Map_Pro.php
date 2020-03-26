<?php

namespace threewp_broadcast\premium_pack\image_map_pro;

/**
	@brief			Adds support for the <a href="https://codecanyon.net/item/image-map-pro-for-wordpress-interactive-image-map-builder/2826664">Image Map Pro</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-10-29 16:00:09
**/
class Image_Map_Pro
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The option key used by IMP to save its settings.
		@since		2017-10-29 16:01:44
	**/
	public static $option_key = 'image-map-pro-wordpress-admin-options';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_setup' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-10-29 16:22:56
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->image_map_pro ) )
			return;

		$used = $bcd->image_map_pro->collection( 'used_shortcodes' );
		if ( count( $used ) < 1 )
			return;

		$options = get_option( static::$option_key, true );
		if ( ! is_array( $options ) )
			$options = [];
		if ( ! isset( $options[ 'saves' ] ) )
			$options[ 'saves' ] = [];

		foreach( $bcd->image_map_pro->collection( 'used_shortcodes' ) as $shortcode => $data )
		{
			$found = false;
			$json = json_decode( stripslashes( $data[ 'json' ] ) );

			foreach( $options[ 'saves' ] as $save_id => $save_data )
			{
				if ( $data[ 'meta' ][ 'shortcode' ] == $save_data[ 'meta' ][ 'shortcode' ] )
				{
					$found = true;
					// Update the meta.
					$json->id = $save_id;
					$options[ 'saves' ][ $save_id ][ 'json' ] = json_encode( $json );
					$options[ 'saves' ][ $save_id ][ 'meta' ] = $data[ 'meta' ];
				}
			}

			if ( ! $found )
			{
				// Add this data.
				do
				{
					$new_id = rand( 1, 9999 );
				}
				while( isset( $options[ 'saves' ][ $new_id ] ) );

				$json->id = $new_id;
				$options[ 'saves' ][ $new_id ] = [
					'json' => wp_slash( json_encode( $json ) ),
					'meta' => $data[ 'meta' ],
				];

			}
		}

		$this->debug( 'Updating %s with %s', static::$option_key, $options );
		update_option( static::$option_key, $options );
	}

	/**
		@brief		threewp_broadcast_broadcasting_setup
		@since		2017-10-29 16:01:14
	**/
	public function threewp_broadcast_broadcasting_setup( $action )
	{
		$bcd = $action->broadcasting_data;

		// Find out if there are any active shortcodes on this blog and activate a shortcode parser for each one.
		$options = get_option( static::$option_key, true );
		if ( ! is_array( $options ) )
			return;
		if ( ! isset( $options[ 'saves' ] ) )
			return;

		$bcd->image_map_pro = ThreeWP_Broadcast()->collection();

		foreach( $options[ 'saves' ] as $save_id => $data )
		{
			$shortcode = $data[ 'meta' ][ 'shortcode' ];
			$bcd->image_map_pro->collection( 'shortcodes' )
				->set( $shortcode, $data );
			$sc = new Shortcode();
			$sc->shortcode = $shortcode;
		}
	}
}
