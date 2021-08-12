<?php

namespace threewp_broadcast\premium_pack\geodirectory;

use \Exception;

/**
	@brief			Adds support for <a href="https://wordpress.org/plugins/geodirectory/">Geodirectory</a>.
	@plugin_group	3rd party compatability
	@since			2015-05-20 20:27:30
**/
class Geodirectory
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'broadcast_comments_sync_comments', 1000 );
		$this->add_action( 'geodir_after_save_comment' );
		$this->add_action( 'geodir_after_add_from_favorite' );
		$this->add_action( 'geodir_after_remove_from_favorite' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_menu' );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- Admin
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Our settings.
		@since		2017-09-26 12:30:40
	**/
	public function settings()
	{
		$form = $this->form();
		$r = '';

		$create_locations = $form->checkbox( 'create_locations' )
			->checked( $this->get_site_option( 'create_locations' ) )
			// Input title
			->description( __( "If the location is not found on the child blog, create it automatically or skip the blog.", 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Create locations automatically', 'threewp_broadcast' ) );

		$sync_favorites = $form->checkbox( 'sync_favorites' )
			->checked( $this->get_site_option( 'sync_favorites' ) )
			// Input title
			->description( __( "Sync the users' favorites between broadcasted places.", 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Sync favorites', 'threewp_broadcast' ) );

		if ( ! $this->broadcast_comments() )
		{
			$form->markup( 'm_sync_reviews' )
				->p( 'GeoDirectory reviews can be synced between broadcasted places if you enable the <a href="https://broadcast.plainviewplugins.com/addon/comments/">Broadcast Comments add-on</a>.' );
		}
		else
			$sync_reviews = $form->select( 'sync_reviews' )
				// Input title
				->description( __( "Should reviews be synced between parent and children, and if so, in which way?", 'threewp_broadcast' ) )
				// Input label
				->label( __( 'Sync reviews', 'threewp_broadcast' ) )
				->option( __( 'Do not sync reviews', 'threewp_broadcast' ), '' )
				->option( __( 'From parent to children', 'threewp_broadcast' ), 'from_parent' )
				->option( __( 'Both ways', 'threewp_broadcast' ), 'both' )
				->value( $this->get_site_option( 'sync_reviews' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $sync_favorites->is_checked();
			$this->update_site_option( 'sync_favorites', $value );

			$value = $create_locations->is_checked();
			$this->update_site_option( 'create_locations', $value );

			if ( $this->broadcast_comments() )
			{
				$value = $sync_reviews->get_post_value();
				$this->update_site_option( 'sync_reviews', $value );
			}

			$r .= $this->info_message_box()->_( 'Options saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $this->wrap( $r, __( 'Geodirectory settings', 'threewp_broadcast' ) );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- Callbacks
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		We have to sync the reviews table after the comments are synced.
		@since		2017-09-26 23:21:01
	**/
	public function broadcast_comments_sync_comments( $action )
	{
		global $wpdb;
		// First, delete all current reviews off of this child.
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'", $this->gd_table( 'post_review', $action->child_blog_id ), $action->child_post_id );
		$this->debug( $query );
		$wpdb->query( $query );

		// Now insert each new equivalent comment directly from the source table to this table.
		$columns = "`post_title`, `post_type`, `user_id`, `rating_ip`, `ratings`, `overall_rating`, `comment_images`, `wasthis_review`, `status`, `post_status`, `post_date`, `post_city`, `post_region`, `post_country`, `post_latitude`, `post_longitude`, `comment_content`";

		foreach( $action->equivalent_comment_ids->collection( $action->parent_blog_id ) as $old_comment_id => $child_blogs )
		{
			// Get the equivalent comment ID on this child blog.
			$new_comment_id = $child_blogs->get( $action->child_blog_id );
			if ( ! $new_comment_id )
				continue;

			// Insert exactly this post + comment ID row from the parent table.
			$query = sprintf( "INSERT INTO `%s` (`post_id`, `comment_id`, %s) SELECT '%d', '%d', %s FROM `%s` WHERE `post_id` = '%d' AND `comment_id` = '%d'",
				$this->gd_table( 'post_review', $action->child_blog_id ),
				$columns,
				$action->child_post_id,
				$new_comment_id,
				$columns,
				$this->gd_table( 'post_review', $action->parent_blog_id ),
				$action->parent_post_id,
				$old_comment_id
			);
			$this->debug( $query );
			$wpdb->query( $query );
		}
	}

	/**
		@brief		geodir_after_add_from_favorite
		@since		2017-09-26 12:42:51
	**/
	public function geodir_after_add_from_favorite( $post_id )
	{
		// Are we supposed to sync favorites?
		if ( ! $this->get_site_option( 'sync_favorites' ) )
			return;

		if ( isset( $this->__syncing_favorites ) )
			return;
		$this->__syncing_favorites = true;

		$action = new \threewp_broadcast\actions\each_linked_post();
		$action->post_id = $post_id;
		$action->add_callback( function( $o )
		{
			$this->debug( 'Adding favorite %d', $o->post_id );
			geodir_add_to_favorite( $o->post_id );
		} );
		$action->execute();

		unset( $this->__syncing_favorites );
	}

	/**
		@brief		After Geodirectory saves its review data.
		@since		2017-09-26 17:04:56
	**/
	public function geodir_after_save_comment( $REQUEST )
	{
		$comments = $this->broadcast_comments();
		if ( ! $comments )
			return $this->debug( 'Comments not found.' );
		$sync_type = $this->get_site_option( 'sync_reviews' );
		if ( $sync_type == '' )
			return $this->debug( 'Not syncing. Option is %s', $sync_type );
		$comment_post_id = $REQUEST[ 'comment_post_ID' ];

		// Since we are not given the comment ID, we must extract it from the db.
		global $wpdb;
		$query = sprintf( "SELECT max( `comment_ID` ) FROM `%s` WHERE `comment_post_ID` = '%d'",
			$wpdb->comments,
			$comment_post_id
		);
		$comment_id = $wpdb->get_var( $query );

		$prepare_sync = new \threewp_broadcast\premium_pack\comments\actions\prepare_sync();
		$prepare_sync->blog_id = get_current_blog_id();
		$prepare_sync->comment_id = $comment_id;
		$prepare_sync->post_id = $comment_post_id;
		$prepare_sync->sync_type = $sync_type;
		$prepare_sync->execute();

		$comments->sync_comments( [
			'prepare_sync' => $prepare_sync,
		] );
	}

	public function geodir_after_remove_from_favorite( $post_id )
	{
		// Are we supposed to sync favorites?
		if ( ! $this->get_site_option( 'sync_favorites' ) )
			return;

		if ( isset( $this->__syncing_favorites ) )
			return;
		$this->__syncing_favorites = true;

		$action = new \threewp_broadcast\actions\each_linked_post();
		$action->post_id = $post_id;
		$action->add_callback( function( $o )
		{
			$this->debug( 'Removing favorite %d', $o->post_id );
			geodir_remove_from_favorite( $o->post_id );
		} );
		$action->execute();

		unset( $this->__syncing_favorites );
	}

	/**
		@brief		Decide whether we should broadcast this post to this blog.
		@since		2015-08-01 17:28:59
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		if ( ! isset( $action->broadcasting_data->geodirectory ) )
			return;

		global $wpdb;

		// Only do this check if the post location is set.
		$post_location = $action->broadcasting_data->geodirectory->get( 'post_location', false );
		if ( ! $post_location )
			return $this->debug( 'No post location set.' );

		$action->broadcasting_data->geodirectory->forget( 'new_location_id' );
		$table = $this->gd_table( 'post_locations' );

		// Try to find whether this post has the same post location.
		$query = sprintf( "SELECT * FROM `%s` WHERE `country_slug` = '%s' AND `region_slug` = '%s' AND `city_slug` = '%s'",
			$table,
			$post_location[ 'country_slug' ],
			$post_location[ 'region_slug' ],
			$post_location[ 'city_slug' ]
		);
		$this->debug( 'Looking for the corresponding post location: %s', $query );
		$results = $wpdb->get_results( $query );
		$this->debug( 'Found post location: %s', $results );
		if ( count( $results ) < 1 )
		{
			// Should we automatically create this location?
			$create_locations = $this->get_site_option( 'create_locations' );
			if ( ! $create_locations )
			{
				$action->broadcast_here = false;
				return $this->debug( 'The same location was not found. Skipping this blog.' );
			}

			$this->debug( 'About to create location %s, %s.', $post_location[ 'city_slug' ], $post_location[ 'country_slug' ] );

			$new_data = $post_location;

			// The location ID is unique for this blog, therefore we cannot insert it.
			unset( $new_data[ 'location_id' ] );

			$wpdb->insert( $table, $new_data );
			$location_id = $wpdb->insert_id;

			// Reuse the same query! Yay!
			$results = $wpdb->get_results( $query );
		}
		$new_location = reset( $results );
		$action->broadcasting_data->geodirectory->set( 'new_location_id', $new_location->location_id );
		$this->debug( 'The new location ID is: %s', $new_location->location_id );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2015-05-20 20:50:00
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->geodirectory ) )
			return;

		$this->restore_attachments( $bcd );
		$this->restore_custom_fields( $bcd );
		$this->restore_event_schedule( $bcd );
		$this->restore_place_detail( $bcd );
		$this->restore_post_categories( $bcd );
		$this->restore_post_icons( $bcd );
		$this->restore_tax_meta( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-07-28 16:11:20
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_geodirectory() )
			return;

		$bcd = $action->broadcasting_data;
		$bcd->geodirectory = ThreeWP_Broadcast()->collection();

		$this->save_attachments( $bcd );
		$this->save_custom_fields( $bcd );
		$this->save_event_schedule( $bcd );
		$this->save_place_detail( $bcd );
		$this->save_post_icons( $bcd );
		$this->save_tax_meta( $bcd );
	}

	/**
		@brief		Add the gd_place custom post type.
		@since		2015-07-28 16:24:59
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		// Add support for custom post types.
		$types = $this->get_post_types();
		$action->post_types += $types;
	}

	/**
		@brief		Add ourselves into the menu.
		@since		2017-09-26 12:29:41
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_geodirectory' )
			->callback_this( 'settings' )
			->menu_title( 'Geodirectory' )
			->page_title( 'Geodirectory settings' );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- SAVE (even though S comes after R, it is more logical for save to come first.
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Save all of the attachments.
		@since		2015-07-29 16:30:48
	**/
	public function save_attachments( $bcd )
	{
		global $wpdb;

		// The attachment order is specified in the attachments table.
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $this->gd_table( 'attachments' ), $bcd->post->ID );
		$this->debug( 'Retrieving attachments: %s', $query );
		$results = $this->query( $query );
		$this->debug( 'Found attachments: %s', $results );
		$bcd->geodirectory->set( 'attachments', $results );
		$bcd->geodirectory->set( 'wp_upload_dir', wp_upload_dir() );
	}

	/**
		@brief		Save the custom fields.
		@since		2015-07-30 21:41:36
	**/
	public function save_custom_fields( $bcd )
	{
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_type` = '%s'", $this->gd_table( 'custom_fields' ), $bcd->post->post_type );
		$this->debug( 'Retrieving custom fields: %s', $query );
		$results = $this->query( $query );
		$results = $this->delete_array_key( $results, 'id' );
		$this->debug( 'Found custom fields: %s', $results );
		$bcd->geodirectory->set( 'custom_fields', $results );
	}

	/**
		@brief		Save the event schedule.
		@since		2015-08-01 16:40:15
	**/
	public function save_event_schedule( $bcd )
	{
		$query = sprintf( "SELECT * FROM `%s` WHERE `event_id` = '%s'", $this->gd_table( 'event_schedule' ), $bcd->post->ID );
		$this->debug( 'Retrieving event schedule: %s', $query );
		$results = $this->query( $query );
		$results = $this->delete_array_key( $results, 'schedule_id' );
		$this->debug( 'Found event schedule: %s', $results );
		$bcd->geodirectory->set( 'event_schedule', $results );
	}

	/**
		@brief		Search for the location ID.
		@since		2015-08-01 17:09:04
	**/
	public function save_location_id( $bcd, $results )
	{
		$this->debug( 'Looking for the post location ID.' );
		$location_id = false;
		foreach( $results as $result )
		{
			if ( isset( $result[ 'post_location_id' ] ) )
				$location_id = $result[ 'post_location_id' ];
		}

		$this->debug( 'The post location ID is: %s', $location_id );

		if ( $location_id !== false )
		{
			$bcd->geodirectory->set( 'location_id', $location_id );
			// Extract and save the location itself.
			$query = sprintf( "SELECT * FROM `%s` WHERE `location_id` = '%s'", $this->gd_table( 'post_locations' ), $location_id );
			$results = $this->query( $query );
			$post_location = reset( $results );
			$this->debug( 'Saving post location: %s, %s', $query, $post_location );
			$bcd->geodirectory->set( 'post_location', $post_location );
		}
	}

	/**
		@brief		Save the row from the place_detail table.
		@since		2015-07-29 15:47:46
	**/
	public function save_place_detail( $bcd )
	{
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $this->gd_table( $bcd->post->post_type . '_detail' ), $bcd->post->ID );
		$this->debug( 'Retrieving place_detail: %s', $query );
		$results = $this->query( $query );
		if ( count( $results ) < 1 )
			return;
		$results = $this->delete_array_key( $results, 'id' );
		$this->save_location_id( $bcd, $results );
		$place_detail = reset( $results );
		$this->debug( 'Found place detail: %s', $place_detail );
		$bcd->geodirectory->set( 'place_detail', $place_detail );
		// We will need the table name for later syncing.
		$bcd->geodirectory->set( 'place_detail_source_table', $this->gd_table( $bcd->post->post_type . '_detail' ) );
	}

	/**
		@brief		Save the post icons.
		@since		2015-07-28 16:27:45
	**/
	public function save_post_icons( $bcd )
	{
		// Extract all of the icons uses.
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $this->gd_table( 'post_icon' ), $bcd->post->ID );
		$this->debug( 'Retrieving post icons: %s', $query );
		$results = $this->query( $query );
		$this->debug( 'Post icons found: %s', $results );
		$results = $this->delete_array_key( $results, 'id' );
		$bcd->geodirectory->set( 'post_icons', $results );
	}

	/**
		@brief		Save the taxonomy meta data.
		@since		2015-07-28 20:45:53
	**/
	public function save_tax_meta( $bcd )
	{
		$gd = $bcd->geodirectory;

		$key = sprintf( '%scategory', $bcd->post->post_type );

		$gd_taxonomies = [ 'gd_place_tags', $key ];

		// Find all place tax metas.
		foreach( $gd_taxonomies as $taxonomy )
		{
			if ( ! isset( $bcd->parent_post_taxonomies[ $taxonomy ] ) )
				continue;

			foreach( $bcd->parent_post_taxonomies[ $taxonomy ] as $gd_term )
			{
				$term_id = $gd_term->term_id;
				$meta_key = sprintf( 'tax_meta_gd_place_%s', $term_id );
				$meta_value = get_option( $meta_key, true );
				if ( ! is_array( $meta_value ) )
				{
					$this->debug( 'No tax meta for term %s', $term_id );
					continue;
				}

				// We need to copy that icon data.
				foreach( [ 'ct_cat_default_img', 'ct_cat_icon' ] as $image_type )
					if ( isset( $meta_value[ $image_type ] ) )
						$bcd->try_add_attachment( $meta_value[ $image_type ][ 'id' ] );

				// And now save the term.
				$this->debug( 'Saving term data for term %s, %s', $term_id, $meta_value );
				$gd->collection( 'tax_meta' )->set( $term_id, $meta_value );
			}
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- RESTORE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Restore the attachments.
		@since		2015-07-29 16:41:55
	**/
	public function restore_attachments( $bcd )
	{
		global $wpdb;
		$new_post_id = $bcd->new_post( 'ID' );
		$wp_upload_dir = wp_upload_dir();
		$parent_wp_upload_dir = $bcd->geodirectory->get( 'wp_upload_dir' );

		$table = $this->gd_table( 'attachments' );
		$this->database_table_must_exist( $table );

		// Delete the current attachments from disk. Unfortunately, we cannot use geodir_get_images because it just don't work when switching blogs.
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Retrieving current attachments: %s', $query );
		$results = $this->query( $query );
		$this->debug( 'Current attachments: %s', $results );
		foreach( $results as $result )
		{
			$file = $wp_upload_dir[ 'basedir' ] . $result[ 'file' ];
			unlink( $file );
			$this->debug( 'Deleting %s', $file );
		}

		// And now delete them from the database.
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Deleting current attachments: %s', $query );
		$this->query( $query );

		// And put the new images in.
		$attachments = $bcd->geodirectory->get( 'attachments' );
		foreach( $attachments as $attachment )
		{
			$new_filename = basename( $attachment[ 'file' ] );
			$source = $parent_wp_upload_dir[ 'basedir' ] . $attachment[ 'file' ];
			$target = $wp_upload_dir[ 'path' ] . '/' . $new_filename;
			copy( $source, $target );
			$this->debug( 'Filesizes after copy - %s: %s  %s : %s',
				$source,
				filesize( $source ),
				$target,
				filesize( $target )
			);

			// Update the attachment data with the new path.
			$new_attachment = $attachment;
			unset( $new_attachment[ 'ID' ] );
			$new_attachment[ 'post_id' ] = $new_post_id;
			$new_attachment[ 'file' ] = $wp_upload_dir[ 'subdir' ] . '/' . $new_filename;
			$this->debug( 'Inserting attachment %s', $new_attachment );
			$wpdb->insert( $table, $new_attachment );

			$new_attachment[ 'ID' ] = $wpdb->insert_id;
			$new_attachment[ 'path' ] = $target;
			$new_attachment[ 'url' ] = $wp_upload_dir[ 'url' ] . '/' . $new_filename;

			$bcd->geodirectory->collection( 'copied_attachments' )->set( $attachment[ 'ID' ], $new_attachment );
		}
	}

	/**
		@brief		Restore the custom fields.
		@since		2015-07-30 21:43:28
	**/
	public function restore_custom_fields( $bcd )
	{
		// Delete the current fields.
		$query = sprintf( "DELETE FROM `%s` WHERE `post_type` = '%s'", $this->gd_table( 'custom_fields' ), $bcd->post->post_type );
		$this->debug( 'Deleting current custom fields: %s', $query );
		$this->query( $query );

		// And reinsert them all.
		global $wpdb;
		foreach( $bcd->geodirectory->get( 'custom_fields' ) as $custom_field )
		{
			$this->debug( 'Reinserting custom field: %s', $custom_field );
			$wpdb->insert( $this->gd_table( 'custom_fields' ), $custom_field );

			$column_name = $custom_field[ 'htmlvar_name' ];
			$this->debug( 'Maybe adding detail column %s', $column_name );
			geodir_add_column_if_not_exist( $this->gd_table( $bcd->post->post_type . '_detail' ), $column_name );
		}
	}

	/**
		@brief		Restore the event schedule.
		@since		2015-08-01 16:40:50
	**/
	public function restore_event_schedule( $bcd )
	{
		$new_post_id = $bcd->new_post( 'ID' );

		$query = sprintf( "DELETE FROM `%s` WHERE `event_id` = '%s'", $this->gd_table( 'event_schedule' ), $new_post_id );
		$this->debug( 'Deleting current event_schedule: %s', $query );
		$this->query( $query );

		global $wpdb;
		foreach( $bcd->geodirectory->get( 'event_schedule' ) as $event_schedule )
		{
			$event_schedule[ 'event_id' ] = $new_post_id;
			$this->debug( 'Inserting new event schedule: %s', $event_schedule );
			$wpdb->insert( $this->gd_table( 'event_schedule' ), $event_schedule );
		}
	}

	/**
		@brief		Restore the place detail row in the table.
		@since		2015-07-29 15:52:47
	**/
	public function restore_place_detail( $bcd )
	{
		if ( ! $bcd->geodirectory->has( 'place_detail' ) )
			return;

		$table = $this->gd_table( $bcd->post->post_type . '_detail' );
		$this->database_table_must_exist( $table );

		// Sync the tables to ensure that the place can be properly added.
		$this->sync_table_structure( [
			'source' => $bcd->geodirectory->get( 'place_detail_source_table' ),
			'target' => $table,
		] );
		$new_post_id = $bcd->new_post( 'ID' );

		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Deleting current place detail: %s', $query );
		$this->query( $query );

		$place_detail = $bcd->geodirectory->get( 'place_detail' );

		if ( ! $place_detail )
			return $this->debug( 'No place detail.' );

		if ( isset( $place_detail[ 'post_id' ] ) )
			$place_detail[ 'post_id' ] = $new_post_id;

		if ( isset( $place_detail[ 'logo' ] ) )
		{
			$logo_parts = explode( '|', $place_detail[ 'logo' ] );
			$old_image_id =  $logo_parts[ 1 ];
			$new_image = $bcd->geodirectory->collection( 'copied_attachments' )->get( $old_image_id );
			$new_image_id = $new_image[ 'ID' ];
			$logo_parts[ 0 ] = $new_image[ 'url' ];
			$logo_parts[ 1 ] = $new_image_id;
			$this->debug( 'Replacing logo with %s', $logo_parts );
			$place_detail[ 'logo' ] = implode( '|', $logo_parts );
		}

		if ( isset( $place_detail[ 'marker_json' ] ) )
			// Data duplication: Each post icon has its own marker also.
			$place_detail[ 'marker_json' ] = $this->fix_marker_json( $bcd, $place_detail[ 'marker_json' ] );

		if ( isset( $place_detail[ 'default_category' ] ) )
			$place_detail[ 'default_category' ] = $bcd->terms()->get( $place_detail[ 'default_category' ] );

		// Old db structure.
		// $key = sprintf( '%scategory', $bcd->post->post_type );
		// New db structure.
		$key = 'post_category';
		if ( isset( $place_detail[ $key ] ) )
		{
			// Data duplication. They are normal taxonomies also.
			$categories = explode( ',', $place_detail[ $key ] );
			foreach( $categories as $index => $category )
				$categories[ $index ] = $bcd->terms()->get( $category );
			$this->debug( 'Setting new %s to %s', $key, $categories );
			$place_detail[ $key ] = implode( ',', $categories );
		}

		// Handle multiple locations.
		$new_post_location_id = $bcd->geodirectory->get( 'new_location_id', 0 );
		if ( $new_post_location_id > 0 )
			$place_detail[ 'post_location_id' ] = $new_post_location_id;

		// Fix the featured image.
		if ( isset( $place_detail[ 'featured_image' ] ) )
		{
			if ( $bcd->has_thumbnail )
			{
				$new_thumbnail_id = $bcd->copied_attachments()->get( $bcd->thumbnail_id );
				$attachment = wp_get_attachment_metadata( $new_thumbnail_id );
				// GeoDirectory's featured image requires a forward slash in the beginning.
				$place_detail[ 'featured_image' ] = '/' . $attachment[ 'file' ];
			}
		}

		$place_detail = apply_filters( 'broadcast_geodirectory_insert_place_detail', $place_detail );

		$this->debug( 'Inserting new place detail: %s', $place_detail );

		global $wpdb;
		$wpdb->show_errors();
		$result = $wpdb->insert( $table, $place_detail );
		if ( ! $result )
			wp_die( 'Error inserting place: ' . $wpdb->last_error );
	}

	/**
		@brief		Restore the post categories.
		@since		2015-07-29 15:03:45
	**/
	public function restore_post_categories( $bcd )
	{
		if ( ! isset( $bcd->post_custom_fields[ 'post_categories' ] ) )
			return $this->debug( 'No post categories.' );
		$post_categories = $bcd->post_custom_fields[ 'post_categories' ];
		$post_categories = reset( $post_categories );

		$post_categories = maybe_unserialize( $post_categories );
		if ( ! is_array( $post_categories ) )
			return $this->debug( 'Post categories custom field is not an array.' );

		$key = sprintf( '%scategory', $bcd->post->post_type );

		if ( ! isset( $post_categories[ $key ] ) )
			return $this->debug( 'Post categories custom field does not contain %s.', $key );

		$post_categories = $post_categories[ $key];
		$post_categories = explode( '#', $post_categories );
		foreach( $post_categories as $index => $category )
		{
			// Another split from 140,y,d to extract the 140.
			$parts = explode( ',', $category );
			$new_term_id = $bcd->terms()->get( $parts[ 0 ] );
			if ( $new_term_id < 1 )
			{
				$this->debug( 'Unable to get an equivalent term for %s', $parts[ 0 ] );
				continue;
			}
			$parts[ 0 ] = $new_term_id;
			$category = implode( ',', $parts );
			$post_categories[ $index ] = $category;
		}
		$post_categories[ $key ] = implode( '#', $post_categories );

		$this->debug( 'Updating post categories with: %s', $post_categories );
		update_post_meta( $bcd->new_post( 'ID' ), 'post_categories', $post_categories );
	}

	/**
		@brief		Restore the post icons.
		@since		2015-07-28 16:27:29
	**/
	public function restore_post_icons( $bcd )
	{
		$table = $this->gd_table( 'post_icon' );

		if ( ! $this->database_table_exists( $table ) )
			return;

		global $wpdb;
		$new_post_id = $bcd->new_post( 'ID' );
		// Delete the current icons.
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Deleting current post icons: %s', $query );
		$this->query( $query );

		// And insert the new ones.
		foreach( $bcd->geodirectory->get( 'post_icons' ) as $post_icon )
		{
			$new_term_id = $bcd->terms()->get( $post_icon[ 'cat_id' ] );

			// Correct the post ID.
			$post_icon[ 'post_id' ] = $new_post_id;
			// And get the equivalent term.
			$post_icon[ 'cat_id' ] = $new_term_id;

			$post_icon[ 'json' ] = $this->fix_marker_json( $bcd, $post_icon[ 'json' ] );

			$this->debug( 'Inserting new post icon data: %s', $post_icon );
			$wpdb->insert( $table, $post_icon );
		}
	}

	/**
		@brief		Restore the tax meta.
		@since		2015-07-28 20:56:06
	**/
	public function restore_tax_meta( $bcd )
	{
		$gd = $bcd->geodirectory;

		if ( ! $gd->has( 'tax_meta' ) )
			return;
		foreach( $gd->get( 'tax_meta' ) as $old_term_id => $meta_value )
		{
			$new_term_id = $bcd->terms()->get( $old_term_id );
			$meta_key = sprintf( 'tax_meta_gd_place_%s', $new_term_id );

			// Fix the images, if necessary.
			foreach( [ 'ct_cat_default_img', 'ct_cat_icon' ] as $image_type )
				if ( isset( $meta_value[ $image_type ] ) )
				{
					$old_image_id = $meta_value[ $image_type ][ 'id' ];
					$new_image_id = $bcd->copied_attachments()->get( $old_image_id );

					$this->debug( 'Equivalent icon for %s is %s.', $old_image_id, $new_image_id );
					$meta_value[ $image_type ][ 'id' ] = $new_image_id;
					$meta_value[ $image_type ][ 'src' ] = wp_get_attachment_url( $new_image_id );
				}

			$this->debug( 'Updating tax meta for %s: %s', $meta_key, $meta_value );
			update_option( $meta_key, $meta_value );
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC is always last.
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Return the Broadcast Comments instance.
		@since		2017-09-26 22:14:05
	**/
	public function broadcast_comments()
	{
		if ( ! function_exists( 'broadcast_comments' ) )
			return false;
		return broadcast_comments();
	}

	/**
		@brief		Delete a key from all elements in the array.
		@since		2015-07-28 20:10:45
	**/
	public function delete_array_key( $array, $key )
	{
		foreach( $array as $index => $a )
			if ( isset( $array[ $index ][ $key ] ) )
				unset( $array[ $index ][ $key ] );
		return $array;
	}

	/**
		@brief		Fix the json object ID and cats.
		@since		2015-07-29 16:04:12
	**/
	public function fix_marker_json( $bcd, $json )
	{
		$new_post_id = $bcd->new_post( 'ID' );

		$json = json_decode( $json );
		$json->id = $new_post_id;

		// The marker is made of postid_catid.
		$marker = explode( '_', $json->marker_id );

		$new_term_id = $bcd->terms()->get( $marker[ 1 ] );

		$marker[ 0 ] = $new_post_id;
		$marker[ 1 ] = $new_term_id;

		// Reassemble marker.
		$json->marker_id = $new_post_id . '_' . $new_term_id;

		$json->group = 'catgroup' . $new_term_id;

		$json = json_encode( $json );

		$json = stripslashes( $json );

		return $json;
	}

	/**
		@brief		Returns the complete Geodir table.
		@since		2015-07-28 20:06:15
	**/
	public function gd_table( $type, $blog_id = null )
	{
		global $wpdb;

		if ( $blog_id !== null )
			switch_to_blog( $blog_id );

		$r = sprintf( '%sgeodir_%s', $wpdb->prefix, $type );

		if ( $blog_id !== null )
			restore_current_blog();

		return $r;
	}

	/**
		@brief		Return the array of post types that Geodirectory and the CPT plugin uses.
		@since		2015-08-01 13:32:34
	**/
	public function get_post_types()
	{
		$types = get_option( 'geodir_custom_post_types', true );
		if ( ! is_array( $types ) )
			$types = [];
		$types[ 'gd_place' ] = 'gd_place';
		$types[ 'gd_event' ] = 'gd_event';
		return $types;
	}

	/**
		@brief		Is Geodirectory installed?
		@since		2015-07-28 16:10:51
	**/
	public function has_geodirectory()
	{
		return defined( 'GEODIRECTORY_VERSION' );
	}

	/**
		@brief		Our site options.
		@since		2017-09-26 12:30:59
	**/
	public function site_options()
	{
		return array_merge( [
			'create_locations' => false,
			'sync_favorites' => false,
			'sync_reviews' => '',
		], parent::site_options() );
	}

	/**
		@brief		Try to sync table structure.
		@details	The options are
					- source the table name that will be used as the source.
					- target the table name that will be modified.
		@since		2017-09-17 12:37:51
		@throws		Exception if the sync fails.
	**/
	public function sync_table_structure( $options )
	{
		$options = (object) $options;
		global $wpdb;

		// Describe the source table.
		$query = sprintf( "DESCRIBE `%s`", $options->source );
		$source_description = $wpdb->get_results( $query );
		$source_description = $this->array_rekey( $source_description, 'Field' );

		// And the target table.
		$query = sprintf( "DESCRIBE `%s`", $options->target );
		$target_description = $wpdb->get_results( $query );
		$target_description = $this->array_rekey( $target_description, 'Field' );

		// Are they identical?
		$identical = true;
		foreach( $source_description as $key => $ignore )
			if ( ! isset( $target_description[ $key ] ) )
			{
				$identical = false;
				break;
			}

		$columns_to_delete = [];
		foreach( $target_description as $key => $ignore )
			if ( ! isset( $source_description[ $key ] ) )
			{
				$identical = false;
				$columns_to_delete[ $key ]  = $key;
			}

		if ( $identical )
			return;

		$this->debug( 'Beginning table sync: %s to %s', $options->source, $options->target );

		// Delete target columns.
		foreach( $columns_to_delete as $column_to_delete )
		{
			$query = sprintf( "ALTER TABLE `%s` DROP `%s`",
				$options->target,
				$column_to_delete
			);
			$this->debug( $query );
			$wpdb->query( $query );
			unset( $target_description[ $column_to_delete ] );
		}

		// Create a new temp table.
		$temp_table_name = $options->target . rand( 1000, 10000 );

		$query = sprintf( "CREATE TABLE `%s` LIKE `%s`",
			$temp_table_name,
			$options->source
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Copy everything from old target to the temp table.
		$target_columns = array_keys( $target_description );
		// Allow certain columns to be removed by others.
		$target_columns = apply_filters( 'broadcast_geodirectory_sync_table_structure_columns', $target_columns );
		$target_columns = '`' . implode( "`,`", $target_columns ) . '`';

		$query = sprintf( "INSERT INTO `%s` ( %s ) SELECT %s FROM `%s`",
			$temp_table_name,
			$target_columns,
			$target_columns,
			$options->target
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Delete the old target.
		$query = sprintf( "DROP TABLE `%s`", $options->target );
		$this->debug( $query );
		$wpdb->query( $query );

		// Rename temp to target.
		$query = sprintf( "RENAME TABLE `%s` TO `%s`", $temp_table_name, $options->target );
		$this->debug( $query );
		$wpdb->query( $query );
	}
}
