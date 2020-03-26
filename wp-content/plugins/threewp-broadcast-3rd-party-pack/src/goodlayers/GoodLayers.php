<?php

namespace threewp_broadcast\premium_pack\goodlayers;

use \Exception;

/**
	@brief			Adds support for the <a href="https://goodlayers.com/">GoodLayers family of themes</a>.
	@plugin_group	3rd party compatability
	@since			2017-11-30 18:57:40
**/
class GoodLayers
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Meta key where the page builder data is kept.
		@since		2017-11-30 19:20:27
	**/
	public static $page_builder_meta_key = 'gdlr-core-page-builder';

	/**
		@brief		Meta key where the core option data is kept.
		@since		2017-11-30 19:20:27
	**/
	public static $page_option_meta_key = 'gdlr-core-page-option';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-07-19 19:27:21
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$bcd->goodlayers = ThreeWP_Broadcast()->collection();

		foreach( [
			static::$page_builder_meta_key,
			static::$page_option_meta_key,
		] as $key )
		{
			$data = $bcd->custom_fields()->get_single( $key );
			if ( ! $data )
				continue;
			$data = maybe_unserialize( $data );

			if ( ! is_array( $data ) )
				continue;

			$this->debug( '%s data is %s', $key, $data );

			if ( is_array( reset( $data ) ) )
			{
				foreach( $data as $index => $item )
					$data[ $index ]  = $this->preparse_data( $bcd, $item );
			}
			else
				$data = $this->preparse_data( $bcd, $data );
		}
	}

	/**
		@brief		Put in the new attachment IDs.
		@since		2014-04-06 15:54:36
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->goodlayers ) )
			return;

		if ( count( $bcd->goodlayers ) < 1 )
			return;

		foreach( [
			static::$page_builder_meta_key,
			static::$page_option_meta_key,
		] as $key )
		{
			$data = $bcd->custom_fields()->get_single( $key );
			$data = maybe_unserialize( $data );

			if ( ! is_array( $data ) )
				continue;

			$this->debug( 'Parsing %s', $key );

			if ( is_array( reset( $data ) ) )
			{
				foreach( $data as $index => $item )
					$data[ $index ]  = $this->parse_data( $bcd, $item );
			}
			else
				$data = $this->parse_data( $bcd, $data );

			$this->debug( 'Assigning new data: %s', $data );

			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $data );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Return the equivalent slider ID on this blog, if any.
		@since		2017-12-07 22:15:01
	**/
	public function get_equivalent_slider_revolution_slider_id( $bcd, $id )
	{
		global $wpdb;

		$c = $bcd->goodlayers->collection( 'slider_revolution' )
			->collection( 'sliders' );
		if ( ! $c->has( $id ) )
			return false;
		$alias = $c->get( $id )->alias;
		$table = sprintf( '%srevslider_sliders', $wpdb->prefix );
		// Get the slider with this alias.
		$query = sprintf( "SELECT * FROM `%s` WHERE `alias` = '%s'", $table, $alias );
		$result = $wpdb->get_row( $query );
		if ( ! $result )
			return $this->debug( 'No slider found with the alias %s.', $alias );
		return $result->id;
	}

	/**
		@brief		Preparse the data.
		@since		2017-11-30 19:07:07
	**/
	public function preparse_data( $bcd, $item )
	{

		// Generic image handler.
		if ( isset( $item[ 'image' ] ) )
		{
			$image_id = $item[ 'image' ];
			if ( $bcd->try_add_attachment( $image_id ) )
				$this->debug( 'Adding attachment %s found in image.', $image_id );
		}

		// From the page options.
		if ( isset( $item[ 'revolution-slider-id' ] ) )
		{
			$id = $item[ 'revolution-slider-id' ];
			$this->save_slider_revolution_data( $bcd, $id );
		}

        if ( isset( $item[ 'value' ] ) )
		{

			// Generic background handler.
			if ( isset( $item[ 'value' ][ 'background-image' ] ) )
			{
				$image_id = $item[ 'value' ][ 'background-image' ];
				if ( $bcd->try_add_attachment( $image_id ) )
					$this->debug( 'Adding background image %d', $image_id );
			}

			// Generic image handler.
			if ( isset( $item[ 'value' ][ 'image' ] ) )
			{
				$image_id = $item[ 'value' ][ 'image' ];
				$this->debug( 'Adding attachment %s found in image.', $image_id );
				try
				{
					$bcd->try_add_attachment( $image_id );
				}
				catch ( Exception $e )
				{
					$this->debug( 'Warning! Unable to add image %d.', $image_id );
				}
			}

			if ( isset( $item[ 'value' ][ 'tabs' ] ) )
				foreach( $item[ 'value' ][ 'tabs' ] as $index => $tab )
				{
					$this->debug( 'Preparsing tab %d', $index );
					$item[ 'value' ][ 'tabs' ][ $index ] = $this->preparse_data( $bcd, $tab );
				}
		}

		// Title background from page options.
		if ( isset( $item[ 'title-background' ] ) )
		{
			$image_id = $item[ 'title-background' ];
			$this->debug( 'Adding page title background image %d', $image_id );
			try
			{
				$bcd->try_add_attachment( $image_id );
			}
			catch ( Exception $e )
			{
				$this->debug( 'Warning! Unable to add background image %d.', $image_id );
			}
		}

		if ( isset( $item[ 'type' ] ) )
			switch( $item[ 'type' ] )
			{
				case 'gallery':
					foreach( $item[ 'value' ][ 'gallery' ] as $gallery_item )
					{
						$image_id = $gallery_item[ 'id' ];
						$this->debug( 'Adding attachment %s found in image.', $image_id );
						try
						{
							$bcd->try_add_attachment( $image_id );
						}
						catch ( Exception $e )
						{
							$this->debug( 'Warning! Unable to add gallery image %d.', $image_id );
						}
					}
				break;
				case 'revolution-slider':
					$id = $item[ 'value' ][ 'revolution-slider-id' ];
					$this->save_slider_revolution_data( $bcd, $id );
				break;
			}

		if ( isset( $item[ 'items' ] ) )
			foreach( $item[ 'items' ] as $index => $subitem )
				$item[ 'items' ][ $index ] = $this->preparse_data( $bcd, $subitem );

		return $item;
	}

	/**
		@brief		Parse (modify) the data.
		@since		2017-11-30 19:07:07
	**/
	public function parse_data( $bcd, $item )
	{
		// Generic image handler.
		if ( isset( $item[ 'image' ] ) )
		{
			$old_image_id = $item[ 'image' ];
			$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
			$this->debug( 'Replacing image %d with %d.', $old_image_id, $new_image_id );
			$item[ 'image' ] = $new_image_id;
		}

		// From the page options.
		if ( isset( $item[ 'revolution-slider-id' ] ) )
		{
			$id = $item[ 'revolution-slider-id' ];
			$new_id = $this->get_equivalent_slider_revolution_slider_id( $bcd, $id );
			$this->debug( 'The new ID for slider %d in the page options is %d.', $id, $new_id );
			$item[ 'revolution-slider-id' ] = $new_id;
		}

		if ( isset( $item[ 'value' ] ) )
		{
			// Generic background handler.
			if ( isset( $item[ 'value' ][ 'background-image' ] ) )
			{
				$old_image_id = $item[ 'value' ][ 'background-image' ];
				$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
				$this->debug( 'Replacing background image %d with %d.', $old_image_id, $new_image_id );
				$item[ 'value' ][ 'background-image' ] = $new_image_id;
			}

			// Generic image handler.
			if ( isset( $item[ 'value' ][ 'image' ] ) )
			{
				$old_image_id = $item[ 'value' ][ 'image' ];
				$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
				$this->debug( 'Replacing image %d with %d.', $old_image_id, $new_image_id );
				$item[ 'value' ][ 'image' ] = $new_image_id;
			}

			if ( isset( $item[ 'value' ][ 'tabs' ] ) )
				foreach( $item[ 'value' ][ 'tabs' ] as $index => $tab )
					$item[ 'value' ][ 'tabs' ][ $index ] = $this->parse_data( $bcd, $tab );
		}

		// Title background from page options.
		if ( isset( $item[ 'title-background' ] ) )
		{
			$old_image_id = $item[ 'title-background' ];
			$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
			$this->debug( 'Replacing title background image %d with %d.', $old_image_id, $new_image_id );
			$item[ 'title-background' ] = $new_image_id;
		}

		if ( isset( $item[ 'type' ] ) )
			switch( $item[ 'type' ] )
			{
				case 'contact-form-7':
					$contact_form_id = $item[ 'value' ][ 'cf7-id' ];
					// Broadcast this contact form here.
					$new_contact_form_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $contact_form_id, get_current_blog_id() );
					$this->debug( 'Replacing contact form %d with %d.', $contact_form_id, $new_contact_form_id );
					$item[ 'value' ][ 'cf7-id' ] = $new_contact_form_id;
				break;
				case 'gallery':
					foreach( $item[ 'value' ][ 'gallery' ] as $gallery_index => $gallery_item )
					{
						$old_image_id = $gallery_item[ 'id' ];
						$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
						$item[ 'value' ][ 'gallery' ][ $gallery_index ][ 'id' ] = $new_image_id;
						$item[ 'value' ][ 'gallery' ][ $gallery_index ][ 'thumbnail' ] = wp_get_attachment_thumb_url( $new_image_id );;
					}
					break;
				case 'revolution-slider':
					$id = $item[ 'value' ][ 'revolution-slider-id' ];
					$new_id = $this->get_equivalent_slider_revolution_slider_id( $bcd, $id );
					$this->debug( 'The new ID for slider %d in as an element is %d.', $id, $new_id );
					$item[ 'value' ][ 'revolution-slider-id' ] = $new_id;
				break;
			}

		$function = __FUNCTION__;

		if ( isset( $item[ 'items' ] ) )
			foreach( $item[ 'items' ] as $index => $subitem )
				$item[ 'items' ][ $index ] = $this->$function( $bcd, $subitem );

		return $item;
	}

	/**
		@brief		Save the revslider data for this blog.
		@since		2017-12-07 22:13:24
	**/
	public function save_slider_revolution_data( $bcd, $id )
	{
		global $wpdb;

		$table = sprintf( '%srevslider_sliders', $wpdb->prefix );
		// Get the slider with this ID.
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $id );
		$result = $wpdb->get_row( $query );
		if ( ! $result )
		{
			$this->debug( 'No slider found with the ID %d.', $id );
			return;
		}
		$this->debug( 'The alias for slider %s is %s.', $id, $result->alias );
		$bcd->goodlayers->collection( 'slider_revolution' )
			->collection( 'sliders' )
			->set( $id, $result );
	}
}
