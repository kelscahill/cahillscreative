<?php

namespace threewp_broadcast\premium_pack\ultimate_member;

use \Exception;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/ultimate-member/">Ultimate Member</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-05-23 09:50:47
**/
class Ultimate_Member
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Constructor.
		@since		2017-08-18
	**/
	public function _construct()
	{
		parent::_construct();
		
		// UM is naughty in that it overwrites the columns.
		$bc = ThreeWP_Broadcast();
		add_filter( 'manage_edit-um_directory_columns', [ $bc, 'manage_posts_columns' ], 20 );
		add_filter( 'manage_edit-um_form_columns', [ $bc, 'manage_posts_columns' ], 20 );
		add_filter( 'manage_edit-um_role_columns', [ $bc, 'manage_posts_columns' ], 20 );
		
		// Add the other custom post types.
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}
	
	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'form_id';
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'ultimatemember';
	}
	
	/**
		@brief		Return the post types we handle
		@since		2017-08-19
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'um_directory' );
		$action->add_type( 'um_form' );
		$action->add_type( 'um_role' );
	}
}
