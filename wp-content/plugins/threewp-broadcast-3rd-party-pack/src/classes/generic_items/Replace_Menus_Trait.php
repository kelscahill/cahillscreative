<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		Replace menu IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Menus_Trait
{
	/**
		@brief		Add the menu(s).
		@since		2016-07-14 13:41:10
	**/
	public function parse_find( $bcd, $find )
	{
		foreach( $find->value as $attribute => $id )
			if ( intval( $id ) > 0 )
				$find->collection( 'menu_slugs' )->set( $id, $this->find_menu_slug( $id ) );

		foreach( $find->values as $attribute => $array )
			foreach( $array[ 'ids' ] as $id )
				if ( intval( $id ) > 0 )
					$find->collection( 'menu_slugs' )->set( $id, $this->find_menu_slug( $id ) );

		$this->debug( 'Find data: %s', $find );
	}

	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		// Convert the ID to a slug.
		$menu_slug = $find->collection( 'menu_slugs' )->get( $old_id, false );
		if ( ! $menu_slug )
		{
			$this->debug( 'Menu slug %s not found.', $menu_slug );
			return false;
		}
		$menus = $this->menus();
		if ( ! isset( $menus[ $menu_slug ] ) )
		{
			$this->debug( 'No menu on this blog with slug %s', $menu_slug );
			return false;
		}

		$new_id = $menus[ $menu_slug ]->term_id;
		$this->debug( 'Replacing menu %s with %s', $old_id, $new_id );
		return $new_id;
	}
}
