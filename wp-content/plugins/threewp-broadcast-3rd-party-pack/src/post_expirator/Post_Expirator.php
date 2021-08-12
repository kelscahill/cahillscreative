<?php

namespace threewp_broadcast\premium_pack\post_expirator;

/**
	@brief				Adds support for Aaron Axelsen's <a href="https://wordpress.org/plugins/post-expirator/">Post Expirator</a> plugin.
	@plugin_group		3rd party compatability
	@since				2016-02-08 15:39:40
**/
class Post_Expirator
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The meta key where the post expirator stores its values.
		@since		2017-11-08 12:37:24
	**/
	public static $meta_key = '_expiration-date-options';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-01-27 15:04:03
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		// Is there an event set?
		$timestamp = wp_next_scheduled('postExpiratorExpire', [ $bcd->post->ID ] );
		if ( ! $timestamp )
			return $this->debug( 'No post expirator scheduled.' );

		$opts = $bcd->custom_fields()
			->get_single( static::$meta_key );
		$opts = maybe_unserialize( $opts );

		if ( isset( $opts[ 'categoryTaxonomy' ] ) )
			$bcd->taxonomies()->also_sync( $bcd->post->post_type, $opts[ 'categoryTaxonomy' ] );

		$bcd->post_expirator = ThreeWP_Broadcast()->collection();
		$bcd->post_expirator->timestamp = $timestamp;
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-01-27 15:10:50
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$post_id = $bcd->new_post( 'ID' );

		// Remove any existing schedule.
		wp_clear_scheduled_hook('postExpiratorExpire', [ $post_id ] );

		if ( ! isset( $bcd->post_expirator ) )
			return;

		// Modify the options so that it points to the correct post.
		$key = static::$meta_key;
		$opts = $bcd->custom_fields()->get_single( $key );
		$opts = maybe_unserialize( $opts );
		$opts[ 'id' ] = $post_id;

		if ( isset( $opts[ 'category' ] ) )
			foreach( $opts[ 'category' ] as $index => $term_id )
			{
				$new_term_id = $bcd->terms()->get( $term_id );
				$this->debug( 'Replacing expiration term %d with %d.', $term_id, $new_term_id );
				$opts[ 'category' ][ $index ] = $new_term_id;
			}

		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $opts );

		$this->debug( 'Set new values for %s: %s', $key, $opts ).

		$this->debug( 'Scheduling new expiration event.' );
		wp_schedule_single_event(
			$bcd->post_expirator->timestamp,
			'postExpiratorExpire',
			[ $post_id ]
		);
	}
}
