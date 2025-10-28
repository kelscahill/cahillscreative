<?php

namespace threewp_broadcast\premium_pack\icegram_engage;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/icegram/">Icegram Engage</a> plugin.
	@plugin_group	3rd party compatability
	@since		2025-09-08 16:48:38
**/
class Icegram_Engage
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	/**
		@brief		Constructor.
	* @since		2025-09-08 16:48:38
	**/
	public function _construct()
	{
		$this->add_action( 'admin_menu', 1000 );		// 1000 so that the menu item gets added after the plugin.
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	/**
		@brief		Add ourselves to the Jetengine menu
		@since		2023-08-23 07:42:41
	**/
	public function admin_menu()
	{
		if ( ! is_super_admin() )
			return;

		add_submenu_page(
			'edit.php?post_type=ig_campaign',
			'Broadcast',
			'Broadcast',
			'manage_options',
			'bc_icegram_engage',
			[ $this, 'ui_tabs' ],
			1000,
		);
	}

	/**
		@brief		broadcast_campaigns
		@since		2025-09-19 13:07:11
	**/
	public function broadcast_campaigns()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'ig_campaign',
			'label_plural' => 'Campaigns',
			'label_singular' => 'Campaign',
		] );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
	* @since		2025-09-08 16:48:38
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$meta_key = 'icegram_campaign_target_rules';
		$meta_value = $bcd->custom_fields()->get_single( $meta_key );
		$meta_value = maybe_unserialize( $meta_value );
		if ( $meta_value )
		{
			$this->debug( 'Found %s', $meta_key );
			$new_page_ids = [];
			if ( isset( $meta_value[ 'page_id' ] ) )
			{
				foreach( $meta_value[ 'page_id' ] as $index => $old_page_id )
				{
					$new_page_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_page_id, get_current_blog_id() );
					if ( $new_page_id )
						$new_page_ids []= $new_page_id;
				}
				$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_page_ids );
			}
		}

		foreach( [
			'messages',
			'campaign_preview',
		] as $meta_key )
		{
			$meta_value = $bcd->custom_fields()->get_single( $meta_key );
			$meta_value = maybe_unserialize( $meta_value );
			if ( $meta_value )
			{
				$this->debug( 'Found %s', $meta_key );
				foreach( $meta_value as $index => $old_data )
				{
					$new_id = $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $old_data[ 'id' ], get_current_blog_id() );
					if ( $new_id )
						$meta_value[ $index ][ 'id' ] = $new_id;
				}
				$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $meta_value );
			}
		}

		$meta_key = 'icegram_campaign_target_pages';
		$meta_value = $bcd->custom_fields()->get_single( $meta_key );
		$meta_value = maybe_unserialize( $meta_value );
		if ( $meta_value )
		{
			$this->debug( 'Found %s', $meta_key );
			$new_page_ids = [];
			foreach( $meta_value as $old_page_id )
			{
				$new_page_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_page_id, get_current_blog_id() );
				if ( $new_page_id )
					$new_page_ids []= $new_page_id;
			}
			$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_page_ids );
		}
	}


	/**
		@brief		UI tabs
		@since		2025-09-08 20:05:16
	**/
	public function ui_tabs( $action )
	{
		$tabs = $this->tabs();

		$tabs->tab( 'broadcast_campaigns' )
			->callback_this( 'broadcast_campaigns' )
			->heading( 'Broadcast Campaigns' )
			->name( 'Campaigns' );

		echo $tabs->render();
	}
}
