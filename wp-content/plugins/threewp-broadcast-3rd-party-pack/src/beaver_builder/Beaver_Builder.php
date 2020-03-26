<?php

namespace threewp_broadcast\premium_pack\beaver_builder;

/**
	@brief			Adds support for the <a href="https://www.wpbeaverbuilder.com/">Beaver Builder page builder plugin</a>.
	@plugin_group	3rd party compatability
	@since			2016-10-25 18:57:26
**/
class Beaver_Builder
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\copy_options_trait;
	use \threewp_broadcast\premium_pack\classes\parse_and_preparse_content_trait;
	use \threewp_broadcast\premium_pack\classes\sync_taxonomy_trait;

	/**
		@brief		Which arrays BB stores its data in.
		@since		2018-07-11 10:28:53
	**/
	public static $data_arrays = [
		'_fl_builder_data',
		'_fl_builder_draft',
	];

	/**
		@brief		Themer layout data.
		@since		2019-03-29 20:45:42
	**/
	public static $layout_fields = [
		'_fl_builder_draft_settings',
		'_fl_builder_data_settings',
	];

	public function _construct()
	{
		$this->add_action( 'fl_builder_after_save_layout' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		Update the children after saving the layout.
		@since		2016-10-25 18:58:28
	**/
	public function fl_builder_after_save_layout( $post_id )
	{
		if ( defined( 'BROADCAST_BEAVER_BUILDER_NO_UPDATE_ON_SAVE' ) )
			return;
		ThreeWP_Broadcast()->api()->update_children( $post_id, [] );
	}

	/**
		@brief		Return an array of the options to copy.
		@since		2017-05-01 22:48:56
	**/
	public function get_options_to_copy()
	{
		return [
			'_fl_builder_*',
		];
	}

	/**
		@brief		Parse the data and replace things.
		@since		2017-10-16 21:31:42
	**/
	public function parse_data( $bcd, $data )
	{
		if ( is_object( $data ) )
		{
			if ( isset( $data->photo ) )
			{
				$new_id = $bcd->copied_attachments()->get( $data->photo );
				$this->debug( 'New photo is: %s', $new_id );
				$data->photo = $new_id;
			}

			if ( isset( $data->photos ) )
			{
				$new_photos = [];
				foreach( $data->photos as $media_id )
				{
					$new_photos []= $bcd->copied_attachments()->get( $media_id );
				}
				$this->debug( 'New photos are: %s', $new_photos );
				$data->photos = $new_photos;
			}

			if ( isset( $data->tax_post_category ) )
			{
				$new_terms = $this->sync_taxonomies( [
					'bcd' => $bcd,
					'post_type' => 'post',
					'taxonomy' => 'category',
					'value' => $data->tax_post_category,
				] );
				$this->debug( 'Replacing tax_post_category %s with %s', $data->tax_post_category, $new_terms );
				$data->tax_post_category = $new_terms;
			}

			if ( isset( $data->tax_post_post_tag ) )
			{
				$new_terms = $this->sync_taxonomies( [
					'bcd' => $bcd,
					'post_type' => 'post',
					'taxonomy' => 'post_tag',
					'value' => $data->tax_post_post_tag,
				] );
				$this->debug( 'Replacing tax_post_post_tag %s with %s', $data->tax_post_post_tag, $new_terms );
				$data->tax_post_post_tag = $new_terms;
			}

			foreach( (array) $data as $key => $value )
				$data->$key = $this->parse_data( $bcd, $value );
		}
		return $data;
	}

	/**
		@brief		Recurse through the array / object.
		@since		2017-10-16 21:22:41
	**/
	public function preparse_data( $bcd, $data )
	{
		if ( is_object( $data ) )
		{
			if ( isset( $data->photo ) )
				if ( $bcd->try_add_attachment( $data->photo ) )
					$this->debug( 'Found photo %s', $data->photo );

			if ( isset( $data->photos ) )
			{
				$this->debug( 'Found photos %s', $data->photos );
				foreach( $data->photos as $media_id )
					$bcd->try_add_attachment( $media_id );
			}

			// Does this object contain a tax_post_category field?
			if ( isset( $data->tax_post_category ) )
			{
				$bcd->taxonomies()->also_sync( 'post', 'category' );
			}

			foreach( (array) $data as $key => $value )
				$data->$key = $this->preparse_data( $bcd, $value );
		}
		return $data;
	}

	/**
		@brief		show_copy_options
		@since		2017-05-01 22:47:16
	**/
	public function show_copy_settings()
	{
		echo $this->generic_copy_options_page( [
			'plugin_name' => 'Beaver Builder',
		] );
	}

	/**
		@brief		Sync some taxonomies if necessary.
		@since		2018-07-11 19:55:02
	**/
	public function sync_taxonomies( $options )
	{
		$options = ( object ) $options;
		$bb = $options->bcd->beaver_builder;
		// Do we need to sync the taxonomies?
		$synced_taxonomies = $bb->collection( 'synced_taxonomies' )->collection( $options->post_type )->get( $options->taxonomy );
		if ( ! $synced_taxonomies )
		{
			switch_to_blog( $options->bcd->parent_blog_id );
			$synced_taxonomies = $this->sync_taxonomy_to_blogs( $options->taxonomy, [ $options->bcd->current_child_blog_id ] );
			restore_current_blog();
			$bb->collection( 'synced_taxonomies' )->collection( $options->post_type )->set( $options->taxonomy, $synced_taxonomies );
		}

		// Convert the terms to the equivalents.
		$terms = explode( ',', $options->value );
		$new_terms = [];
		foreach( $terms as $term_id )
		{
			$new_term_id = $synced_taxonomies->terms()->get( $term_id );
			if ( $new_term_id < 1 )
				continue;
			$new_terms []= $new_term_id;
		}
		$new_terms = implode( ',', $new_terms );
		return $new_terms;
	}

	/**
		@brief		Parse the builder blocks.
		@since		2017-06-30 00:19:34
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->beaver_builder ) )
			return;

		$bb = $bcd->beaver_builder;

		foreach( static::$data_arrays as $key )
		{
			$data = $bcd->custom_fields()->get_single( $key );
			$data = maybe_unserialize( $data );

			if ( ! is_array( $data ) )
				continue;

			foreach( $data as $data_key => $data_value )
				$data[ $data_key ] = $this->parse_data( $bcd, $data_value );

			$this->debug( 'Parsing Beaver Builder %s', $key );
			foreach( $data as $block_id => $section )
			{
				$data[ $block_id ] = $this->parse_content( [
					'broadcasting_data' => $bcd,
					'content' => $section,
					'id' => $key . $block_id,
				] );
				if ( isset( $section->settings ) )
				{
					if ( isset( $section->settings->text ) )
					{
						$section->settings->text = $this->parse_content( [
							'broadcasting_data' => $bcd,
							'content' => $section->settings->text,
							'id' => $key . $block_id . 'text',
						] );
					}

					if ( isset( $section->settings->type ) )
						switch( $section->settings->type )
						{
							case 'post-carousel':
								$post_type = $section->settings->post_type;
								$carousel_key = 'posts_' . $post_type;
								// Load the bcd for each posts_page
								$post_ids = explode( ',', $section->settings->$carousel_key );
								$new_post_ids = [];
								$bb_pp = $bb->collection( 'post-carousel' );
								foreach( $post_ids as $post_id )
								{
									$post_bcd = $bb_pp->get( $post_id );
									$new_post_id = $post_bcd->get_linked_post_on_this_blog();
									if ( ! $new_post_id )
										continue;
									$new_post_ids []= $new_post_id;
								}
								$new_post_ids = implode( ',', $new_post_ids );
								$this->debug( 'Setting new %s post carousel: %s', $carousel_key, $new_post_ids );
								$section->settings->$carousel_key = $new_post_ids;
							break;
						}
				}
			}

			// Done modifying. Save it.
			$this->debug( 'Saving %s: %s', $key, $data );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $data );
		}

		$meta_key = '_fl_theme_builder_locations';
		$locations = $bcd->custom_fields()->get_single( $meta_key );
		$locations = maybe_unserialize( $locations );
		if ( is_array( $locations ) )
		{
			$new_locations = [];
			foreach( $locations as $location )
			{
				// Split this location into pieces.
				$pieces = explode( ':', $location );
				switch( $pieces[ 0 ] )
				{
					case 'post':
						$post_type = $pieces[ 1 ];

						// Try handling a normal post ID.
						$post_id = intval( $pieces[ 2 ] );
						if ( $post_id > 0 )
						{
							$pieces[ 2 ] = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $post_id, $bcd->current_child_blog_id );
						}
						// This is a taxonomy.
						if ( $pieces[ 2 ] == 'taxonomy' )
						{
							switch_to_blog( $bcd->parent_blog_id );

							$taxonomy = $pieces[ 3 ];
							$term_id = intval( $pieces[ 4 ] );
							$synced_bcd = $this->sync_taxonomy_to_blogs( $taxonomy, [ $bcd->current_child_blog_id ] );

							restore_current_blog();

							$pieces[ 4 ] = $synced_bcd->terms()->get( $pieces[ 4 ] );
						}
						break;
				}
				$new_location = implode( ':', $pieces );
				$new_locations []= $new_location;
			}
			$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_locations );
		}

		foreach( $bb->collection( 'layout_fields' ) as $key => $value )
		{
			$this->debug( 'Updating layout_field %s: %s', $key, $value );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $value );
		}
	}


	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-06-30 00:09:50
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		// Does this page have beaver info?
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->beaver_builder ) )
			$bcd->beaver_builder = ThreeWP_Broadcast()->collection();

		$bb = $bcd->beaver_builder;

		foreach( static::$data_arrays as $key )
		{
			$data = $bcd->custom_fields()->get_single( $key );
			if ( ! $data )
				continue;

			$data = maybe_unserialize( $data );
			foreach( $data as $data_key => $data_value )
				$this->preparse_data( $bcd, $data_value );

			$this->debug( 'Preparsing Beaver Builder %s', $key );
			// Go through all of the data.
			foreach( $data as $block_id => $section )
			{
				$data[ $block_id ] = $this->preparse_content( [
					'broadcasting_data' => $bcd,
					'content' => $section,
					'id' => $key . $block_id,
				] );

				if ( isset( $section->settings ) )
				{
					if ( isset( $section->settings->text ) )
					{
						$section->settings->text = $this->preparse_content( [
							'broadcasting_data' => $bcd,
							'content' => $section->settings->text,
							'id' => $key . $block_id . 'text',
						] );
					}

					if ( isset( $section->settings->type ) )
						switch( $section->settings->type )
						{
							case 'post-carousel':
								$post_type = $section->settings->post_type;
								$carousel_key = 'posts_' . $post_type;
								$this->debug( 'Saving bcds for carousel %s %s', $carousel_key, $section->settings->$carousel_key );
								// Load the bcd for each posts_page
								$post_ids = explode( ',', $section->settings->$carousel_key );
								$bb_pp = $bb->collection( 'post-carousel' );
								foreach( $post_ids as $post_id )
								{
									$post_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post_id );
									$bb_pp->set( $post_id, $post_bcd );
								}
							break;
						}
				}
			}
		}

		// If we find any layout data, store it separately.
		foreach( static::$layout_fields as $key )
		{
			$value = $bcd->custom_fields()->get_single( $key );
			if ( ! $value )
				continue;
			$value = maybe_unserialize( $value );
			$this->debug( 'Saving %s: %s', $key, $value );
			$bb->collection( 'layout_fields' )->set( $key, $value );
		}

	}

	/**
		@brief		Add our supported post types.
		@since		2018-06-19 09:26:44
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'fl-theme-layout' );
	}

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_beaver_builder' )
			->callback_this( 'show_copy_settings' )
			->menu_title( 'Beaver Builder' )
			->page_title( 'Beaver Builder Broadcast' );
	}
}
