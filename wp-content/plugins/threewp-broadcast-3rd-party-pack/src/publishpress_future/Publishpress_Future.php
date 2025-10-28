<?php

namespace threewp_broadcast\premium_pack\publishpress_future;

use PublishPressFuture\Modules\Expirator;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/post-expirator/">PublishPress Future</a> plugin.
	@plugin_group		3rd party compatability
	@since				2016-02-08 15:39:40
**/
class Publishpress_Future
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The meta key where the post date is stored.
		@since		2023-05-04 19:51:56
	**/
	public static $meta_key_date = '_expiration-date';
	/**
		@brief		The meta key where the post expirator stores its values.
		@since		2017-11-08 12:37:24
	**/
	public static $meta_key_options = '_expiration-date-options';

	public function _construct()
	{
		$this->add_action( 'broadcast_post_expirator_schedule_post_expiration', 10, 3 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
	 * broadcast_post_expirator_schedule_post_expiration
	 *
	 * @since		2024-10-30 22:25:55
	 **/
	public function broadcast_post_expirator_schedule_post_expiration( $post_id, $timestamp, $opts )
	{
		$this->debug( 'Running ACTION_SCHEDULE_POST_EXPIRATION for %s %s %s', $post_id, $timestamp, $opts );

		// Fix the timestamp, because Post Expirator will fix it again.
		$gmt_offset = get_option( 'gmt_offset' ) * 3600;
		$timestamp += $gmt_offset;

		do_action( Expirator\HooksAbstract::ACTION_SCHEDULE_POST_EXPIRATION, $post_id, $timestamp, $opts );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-01-27 15:04:03
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$timestamp = $bcd->custom_fields()
			->get_single( static::$meta_key_date );

		if ( ! $timestamp )
		{
			$this->debug( 'No PublishPress Future meta_key_date' );
			return;
		}

		$opts = $bcd->custom_fields()
			->get_single( static::$meta_key_options );
		$opts = maybe_unserialize( $opts );

		if ( isset( $opts[ 'categoryTaxonomy' ] ) )
			$bcd->taxonomies()->also_sync( $bcd->post->post_type, $opts[ 'categoryTaxonomy' ] );

		if ( isset( $opts[ 'category' ] ) )
			if ( $opts[ 'category' ] != 0 )
				$bcd->taxonomies()->use_terms( $opts[ 'category' ] );

		$bcd->post_expirator = ThreeWP_Broadcast()->collection();
		$bcd->post_expirator->set( 'timestamp', $timestamp );
		$bcd->post_expirator->set( 'opts', $opts );
		$this->debug( 'Saving Post Expirator data: %s', $bcd->post_expirator );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-01-27 15:10:50
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$post_id = $bcd->new_post( 'ID' );

		if ( ! class_exists( 'PublishPressFuture\\Modules\\Expirator\\HooksAbstract' ) )
			return $this->debug( 'Post Expirator not detected.' );

		// Remove any existing schedule.
		// This will delete all postmeta :(
		//do_action( Expirator\HooksAbstract::ACTION_UNSCHEDULE_POST_EXPIRATION, $post_id );

		if ( ! isset( $bcd->post_expirator ) )
			return;

		// Modify the options so that it points to the correct post.
		$key = static::$meta_key_options;

		$opts = $bcd->post_expirator->get( 'opts' );

		$opts[ 'id' ] = $post_id;

		if ( isset( $opts[ 'category' ] ) )
			if ( is_array( $opts[ 'category' ] ) )
			{
				foreach( $opts[ 'category' ] as $index => $term_id )
				{
					$new_term_id = $bcd->terms()->get( $term_id );
					$this->debug( 'Replacing expiration term %d with %d.', $term_id, $new_term_id );
					$opts[ 'category' ][ $index ] = $new_term_id;
				}
			}
			else
			{
				$term_id = $opts[ 'category' ];
				$new_term_id = $bcd->terms()->get( $term_id );
				$this->debug( 'Replacing expiration term %d with %d.', $term_id, $new_term_id );
				$opts[ 'category' ] = $new_term_id . '';
			}

		if ( isset( $opts[ 'postLink' ] ) )
			$opts[ 'postLink' ] = get_permalink( $post_id );

		$this->debug( 'Setting new values for %s: %s', $key, $opts ).

		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $opts );

		$timestamp = $bcd->post_expirator->get( 'timestamp' );
		$this->debug( 'Scheduling new expiration event: %s %s %s',
			$post_id,
			$bcd->post_expirator->get( 'timestamp' ),
			$opts,
		);

		wp_schedule_single_event( time() + 1,
			'broadcast_post_expirator_schedule_post_expiration',
			[ $post_id, $timestamp, $opts ],
		);
		//do_action( Expirator\HooksAbstract::ACTION_SCHEDULE_POST_EXPIRATION, $post_id, $timestamp, $opts );
	}
}
