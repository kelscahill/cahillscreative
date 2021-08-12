<?php

namespace threewp_broadcast\premium_pack\ubermenu;

/**
	@brief			Adds support for the <a href="https://wpmegamenu.com/">UberMenu</a> menu plugin.
	@plugin_group	3rd party compatability
	@since			2018-07-13 22:08:47
**/
class UberMenu
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\sync_taxonomy_trait;

	public function _construct()
	{
		$this->add_action( 'broadcast_menus_copy_menu_item', 100 );
		$this->add_action( 'broadcast_menus_copy_menus', 50 );
	}

	/**
		@brief		broadcast_menus_copy_menu_item
		@since		2018-07-13 22:29:55
	**/
	public function broadcast_menus_copy_menu_item( $action )
	{
		// Is this menu item a UM segment?
		$old_menu_item_id = $action->menu_item->ID;
		$_ubermenu_custom_item_type = $action->copy_menu_action
			->menu
			->menu_item_post_meta
			->collection( $old_menu_item_id )
			->get( '_ubermenu_custom_item_type' );

		$ubermenu_settings = get_post_meta( $action->menu_item->new_item_id, '_ubermenu_settings');
		$ubermenu_settings = reset( $ubermenu_settings );
		$ubermenu_settings = maybe_unserialize( $ubermenu_settings );

		if ( ! is_array( $ubermenu_settings ) )
			return;

		$ubermenu = $action->copy_menu_action
			->copy_menus_action
			->ubermenu;
		$current_blog_id = get_current_blog_id();
		$parent_blog_id = $action->copy_menu_action->copy_menus_action->parent_blog_id;

		// Handle menu segments.
		$old_menu_id = $ubermenu_settings[ 'menu_segment' ];
		if ( $old_menu_id > 0 )
		{
			// Replace this with the correct menu ID, if possible.
			$menu_slug = $ubermenu
				->collection( 'menus' )
				->get( $old_menu_id )->slug;

			// Find the menu with the same slug.
			$new_menu_id = 0;
			foreach( wp_get_nav_menus() as $menu )
			{
				if ( $menu->slug == $menu_slug )
				{
					$new_menu_id = $menu->term_id;
				}
			}

			$ubermenu_settings[ 'menu_segment' ] = $new_menu_id;
			$this->debug( 'Replacing ubermenu menu segment %d with %d', $old_menu_id, $new_menu_id );
		}

		// Handle post related settings.
		foreach( [ 'dp_post_parent' ] as $key )
		{
			if ( $ubermenu_settings[ $key ] < 1 )
				continue;

			$old_value = $ubermenu_settings[ $key ];
			// Get the broadcast data of this post on the parent blog.
			$broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data(
				$parent_blog_id,
				$old_value
			);
			$new_value = $broadcast_data->get_linked_post_on_this_blog();
			$this->debug( 'Replacing ubermenu %s %s with %d', $key, $old_value, $new_value );
			$ubermenu_settings[ $key ] = $new_value;
		}

		// Handle taxonomies
		foreach( [
			'category' => 'dp_category',
			'post_tag' => 'dp_tag',
		] as $taxonomy => $key )
		{
			if ( $ubermenu_settings[ $key ] < 1 )
				continue;

			$old_value = $ubermenu_settings[ $key ];

			// Have we already synced this taxonomy?
			$bcd = $ubermenu->collection( 'synced_taxonomies' )->get( $taxonomy );
			if ( ! $bcd )
			{
				switch_to_blog( $parent_blog_id );
				$bcd = $this->sync_taxonomy_to_blogs( $taxonomy, [ $current_blog_id ] );
				restore_current_blog();
				$ubermenu->collection( 'synced_taxonomies' )->set( $taxonomy, $bcd );
			}
			// Get the equivalent ID on this blog.
			$new_value = $bcd->terms()->get( $old_value );
			$this->debug( 'Replacing ubermenu %s %s with %d', $key, $old_value, $new_value );
			$ubermenu_settings[ $key ] = $new_value;
		}

		update_post_meta( $action->menu_item->new_item_id, '_ubermenu_settings', $ubermenu_settings );
	}

	/**
		@brief		broadcast_menus_copy_menus
		@since		2018-07-18 16:55:02
	**/
	public function broadcast_menus_copy_menus( $action )
	{
		if ( ! isset( $action->ubermenu ) )
			$action->ubermenu = ThreeWP_Broadcast()->collection();

		// In case we need a menu lookup for later.
		$menus = wp_get_nav_menus();
		foreach( $menus as $menu )
			$action->ubermenu->collection( 'menus' )->set( $menu->term_id, $menu );
	}
}
