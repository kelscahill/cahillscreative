<?php

namespace threewp_broadcast\premium_pack\wpforms;

/**
	@brief			Adds support for the <a href="https://wpforms.com/">WPForms</a> plugin.
	@plugin_group	3rd party compatability
	@since			2019-07-12 09:01:03
**/
class WPForms
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2017-04-27 22:46:42
	**/
	public function _construct()
	{
		new WPForms_Shortcode();
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
	}

	/**
		@brief		Modify the ID.
		@since		2019-07-12 09:05:05
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'wpforms' )
			return;

		$content = $action->broadcasting_data->modified_post->post_content;
		//$content = stripslashes( $content );
		$content = json_decode( $content );
		$new_id = $bcd->new_post( 'ID' );
		$this->debug( 'Setting new ID %s in %s', $new_id, $content );
		$content->id = $new_id;
		$content = json_encode( $content );

		$action->broadcasting_data->modified_post->post_content = $content;
	}

}
