<?php

namespace threewp_broadcast\premium_pack\slider_revolution;

/**
	@brief			Adds support for the <a href="https://revolution.themepunch.com/">Slider Revolution</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-01-11 22:51:31
**/
class Slider_Revolution
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Some page builders like to store the slider in a custom field.
		@since		2017-06-14 10:27:32
	**/
	public static $builder_custom_fields = [
		'env_rev_slider',	// Envision
		'pyre_revslider',	// Avada / pyre
	];

	public function _construct()
	{
		parent::_construct();

		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-05-08 14:39:19
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		// Allow developers to add more custom fields through which to search for revolution sliders.
		$custom_fields = apply_filters( 'broadcast_slider_revolution_builder_custom_fields', static::$builder_custom_fields );

		foreach( $custom_fields as $builder_custom_field )
		{
			$key = sprintf( 'slider_revolution_%s', $builder_custom_field );
			if ( ! isset( $bcd->$key ) )
				continue;

			foreach( $bcd->$key as $slider_alias => $item )
			{
				$new_slider_id = $this->copy_item( $bcd, $item );
				$this->debug( 'Revolution slider on this blog has the ID %s.', $new_slider_id );
			}
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@details	If revslider is used in a theme, the shortcode won't be detected, so we have to check for sliders separately.
		@since		2017-05-08 14:26:29
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		foreach( static::$builder_custom_fields as $builder_custom_field )
		{
			$slider_alias = $bcd->custom_fields()->get_single( $builder_custom_field );
			if ( $slider_alias != false )
			{
				$item = (object) [];
				$item->attributes = [];
				$this->debug( 'Found slider %s in %s.', $slider_alias, $builder_custom_field );
				$item->attributes[ 'alias' ] = $slider_alias;
				$this->finalize_item( $item );
				$this->remember_item( $bcd, $item );

				$key = sprintf( 'slider_revolution_%s', $builder_custom_field );

				$bcd->$key = ThreeWP_Broadcast()->collection();
				$bcd->$key->set( $slider_alias, $item );
			}
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		$table = $this->get_table( 'revslider_sliders' );
		$this->database_table_must_exist( $table );

		// Does this slider exist on this child?
		$query = sprintf( "SELECT * FROM `%s` WHERE `alias` = '%s'", $table, $item->attributes[ 'alias' ] );
		$this->debug( $query );
		$result = $wpdb->get_row( $query );

		if ( $result === null )
		{
			$columns = '`title`, `alias`, `params`, `settings`, `type`';
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` = '%s' )",
				$this->get_table( 'revslider_sliders' ),
				$columns,
				$columns,
				$this->get_table( 'revslider_sliders', $bcd->parent_blog_id ),
				$item->id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_item_id = $wpdb->insert_id;
		}
		else
			$new_item_id = $result->id;

		// Delete all old slides.
		$query = sprintf( "DELETE FROM `%s` WHERE `slider_id` = '%s'",
			$this->get_table( 'revslider_slides' ),
			$new_item_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Re-add the slides.
		$columns = '`slide_order`, `params`, `layers`, `settings`';
		$query = sprintf( "INSERT INTO `%s` ( `slider_id`, %s ) ( SELECT %s, %s FROM `%s` WHERE `slider_id` ='%s' )",
			$this->get_table( 'revslider_slides' ),
			$columns,
			$new_item_id,
			$columns,
			$this->get_table( 'revslider_slides', $bcd->parent_blog_id ),
			$item->id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		// And fix the image IDs.
		$query = sprintf( "SELECT * FROM `%s` WHERE `slider_id` = '%s'",
			$this->get_table( 'revslider_slides' ),
			$new_item_id
		);
		$new_slides = $wpdb->get_results( $query );
		foreach( $new_slides as $slide )
		{
			$modified = false;

			$params = $slide->params;
			$params = json_decode( $params );

			if ( isset( $params->image_id ) )
				if ( $params->image_id > 0 )
				{
					$params->image_id = $bcd->copied_attachments()->get( $params->image_id );
					$modified = true;
				}

			if ( $modified )
			{
				$new_params = json_encode( $params );
				$this->debug( 'Saving new params for slide %s in %s: %s', $slide->id, $slider_id, $new_params );
				$wpdb->update( $this->get_table( 'revslider_slides' ), [ 'params' => $new_params ], [ 'id' => $slide->id ] );
			}
		}

		// Delete all old static slides.
		$query = sprintf( "DELETE FROM `%s` WHERE `slider_id` = '%s'",
			$this->get_table( 'revslider_static_slides' ),
			$new_item_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Re-add the static slides.
		$columns = '`params`, `layers`, `settings`';
		$query = sprintf( "INSERT INTO `%s` ( `slider_id`, %s ) ( SELECT %s, %s FROM `%s` WHERE `slider_id` ='%s' )",
			$this->get_table( 'revslider_static_slides' ),
			$columns,
			$new_item_id,
			$columns,
			$this->get_table( 'revslider_static_slides', $bcd->parent_blog_id ),
			$item->id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		return $new_item_id;
	}

	/**
		@brief		Check for an ID or a title attribute.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		// Did we find either the id or title? Convenience variable.
		$found = false;

		if ( ! isset( $item->attributes[ 'alias' ] ) )
			return $this->debug( 'Warning: shortcode has no alias attribute.' );
		$alias = $item->attributes[ 'alias' ];

		global $wpdb;

		$table = $this->get_table( 'revslider_sliders' );
		$this->database_table_must_exist( $table );

		// The the table with this alias.
		$query = sprintf( "SELECT * FROM `%s` WHERE `alias` = '%s'", $table, $alias );
		$result = $wpdb->get_row( $query );

		if ( ! $result )
			return $this->debug( 'No slider found with this alias.' );

		$item->id = $result->id;

		// Find all of the slides.
		$table = $this->get_table( 'revslider_slides' );
		$this->database_table_must_exist( $table );
		$query = sprintf( "SELECT * FROM `%s` WHERE `slider_id` = '%s'", $table, $item->id );
		$item->slides = $wpdb->get_results( $query );

		// And the static slides.
		$table = $this->get_table( 'revslider_static_slides' );
		$this->database_table_must_exist( $table );
		$query = sprintf( "SELECT * FROM `%s` WHERE `slider_id` = '%s'", $table, $item->id );
		$item->static_slides = $wpdb->get_results( $query );
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'rev_slider';
	}

	/**
		@brief		Return the name of the table on this blog.
		@since		2017-05-05 17:16:45
	**/
	public function get_table( $name, $blog_id = 0 )
	{
		if ( $blog_id > 0 )
			switch_to_blog( $blog_id );

		global $wpdb;
		$r = sprintf( '%s%s', $wpdb->prefix, $name );

		if ( $blog_id > 0 )
			restore_current_blog();

		return $r;
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		// Get the image ID for each slide.
		foreach( $item->slides as $index => $slide )
		{
			$params = $slide->params;
			$params = json_decode( $params );
			if ( isset( $params->image_id ) )
				if ( $params->image_id > 0 )
				{
					if ( $bcd->try_add_attachment( $params->image_id ) )
						$this->debug( 'Found image %s in slide %s', $params->image_id, $index );
				}
		}
	}
}
