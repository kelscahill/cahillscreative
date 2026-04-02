<?php

namespace threewp_broadcast\premium_pack\advanced_ads;

use Exception;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/advanced-ads/">Advanced Ads</a> plugin.
	@plugin_group		3rd party compatability
	@since				2026-02-17 14:58:24
**/
class Advanced_Ads
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_wp_update_term', 3 );
	}

	/**
		@brief		maybe_restore_advanced_ads_ad_group_ids
		@since		2026-01-21 18:59:07
	**/
	public function maybe_restore_advanced_ads_ad_group_ids( $bcd )
	{
		$cf = $bcd->custom_fields();
		$key = 'advanced_ads_ad_group_ids';
		$data = $cf->get_single( $key );
		$data = maybe_unserialize( $data );
		$save_data = false;

		$old_post_id = $bcd->post->ID;
		$new_post_id = $bcd->new_post( 'ID' );

		$existing_options = $bcd->advanced_ads->collection( 'existing_advanced_ads_group_options' );

		// Update the ad weights for the groups used.
		$new_data = [];
		foreach( $data as $old_group_id )
		{
			// Update the group ID (new term ID).
			$new_group_id = $bcd->terms()->get( $old_group_id );
			$new_data []= $new_group_id;

			// Get the old ad weight.
			$old_term_meta = $bcd->advanced_ads->collection( 'advanced_ads_groups', $old_group_id )->get( 'term_meta' );
			$old_ad_weights = $old_term_meta[ 'ad_weights' ];
			$old_ad_weight = $old_ad_weights[ $old_post_id ];

			// And since we have the new term ID, we can also restore the ad weights.
			$new_existing_options = $existing_options->get( $old_group_id );
			if ( ! $new_existing_options )
			{
				$this->debug( 'No existing term meta found for group %s. Using get_term_meta.', $new_group_id );
				$new_existing_options = get_term_meta( $new_group_id, 'advanced_ads_group_options' );
				$new_existing_options = reset( $new_existing_options );
				unset( $new_existing_options[ 'ad_weights' ][ $old_post_id ] );
			}

			// Remove all weights for ads that don't exist.
			foreach( $new_existing_options[ 'ad_weights' ] as $ad_post_id => $ignore )
			{
				try
				{
					$ad_post = get_post( $ad_post_id );
					if ( ! is_a( $ad_post, \WP_Post::class ) )
						throw new Exception( 'nonexistent' );
					if ( $ad_post->post_type != 'advanced_ads' )
						throw new Exception( 'non-ad' );
				}
				catch( Exception $e )
				{
					$this->debug( 'Removing %s ad %s from group %s',
						$e->getMessage(),
						$ad_post_id,
						$new_group_id,
					);
					unset( $new_existing_options[ 'ad_weights' ][ $ad_post_id ] );
				}
			}

			$new_existing_options[ 'ad_weights' ][ $new_post_id ] = $old_ad_weight;
			$this->debug( 'Updating advanced_ads_groups for %s: %s', $new_group_id, $new_existing_options );
			update_term_meta( $new_group_id, 'advanced_ads_group_options', $new_existing_options );
		}

		$save_data = true;
		$this->debug( 'Replacing old %s %s with %s',
			$key,
			$data,
			$new_data,
		);
		$data = $new_data;

		if ( $save_data )
		{
			$cf->child_fields()->update_meta( $key, $data );
		}
	}

	/**
		@brief		maybe_restore_advanced_ads_ad_options
		@since		2026-01-21 18:00:00
	**/
	public function maybe_restore_advanced_ads_ad_options( $bcd )
	{
		$cf = $bcd->custom_fields();
		$key = 'advanced_ads_ad_options';
		$data = $cf->get_single( $key );
		$data = maybe_unserialize( $data );
		$save_data = false;

		$image_id = $bcd->advanced_ads->get( 'image_id' );
		if ( $image_id > 0 )
		{
			$new_image_id = $bcd->copied_attachments()->get( $image_id );
			$this->debug( 'Replacing image_id %s with %s', $image_id, $new_image_id );
			$data[ 'image_id' ] = $new_image_id;
			$save_data = true;
		}

		if ( $save_data )
		{
			$cf->child_fields()->update_meta( $key, $data );
		}
	}

	/**
		@brief		maybe_save_advanced_ads_ad_group_ids
		@since		2026-01-21 18:15:42
	**/
	public function maybe_save_advanced_ads_ad_group_ids( $bcd )
	{
		$cf = $bcd->custom_fields();
		if ( ! $cf->has( 'advanced_ads_ad_group_ids' ) )
			return;

		$advanced_ads_ad_group_ids = $cf->get_single( 'advanced_ads_ad_group_ids' );
		$advanced_ads_ad_group_ids = maybe_unserialize( $advanced_ads_ad_group_ids );

		if ( ! $advanced_ads_ad_group_ids )
			return;

		$this->prepare_bcd( $bcd );

		foreach( $advanced_ads_ad_group_ids as $group_id )
		{
			$term = get_term( $group_id );
			$term_meta = get_term_meta( $group_id, 'advanced_ads_group_options' );
			$term_meta = reset( $term_meta );
			$bcd->advanced_ads->collection( 'advanced_ads_groups', $group_id )
				->set( 'term', $term )
				->set( 'term_meta', $term_meta );
		}

		$this->debug( 'Saved advanced_ads_groups: %s', $bcd->advanced_ads->collection( 'advanced_ads_groups' ) );
	}

	/**
		@brief		Maybe handle the data saved in the advanced_ads_ad_options custom field.
		@since		2026-01-21 17:59:15
	**/
	public function maybe_save_advanced_ads_ad_options( $bcd )
	{
		$cf = $bcd->custom_fields();
		if ( ! $cf->has( 'advanced_ads_ad_options' ) )
			return;

		$advanced_ads_ad_options = $cf->get_single( 'advanced_ads_ad_options' );
		$advanced_ads_ad_options = maybe_unserialize( $advanced_ads_ad_options );

		if ( ! $advanced_ads_ad_options )
			return;

		if ( ! isset( $advanced_ads_ad_options[ 'image_id' ] ) )
			return;

		$this->prepare_bcd( $bcd );

		$image_id = $advanced_ads_ad_options[ 'image_id' ];
		$bcd->try_add_attachment( $image_id );
		$this->debug( 'Added image ID %s', $image_id );
		$bcd->advanced_ads->set( 'image_id', $image_id );
	}

	/**
		@brief		prepare_bcd
		@since		2026-01-21 18:02:16
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->advanced_ads ) )
			$bcd->advanced_ads = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_switch_to_blog
		@since		2026-01-21 20:19:00
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->advanced_ads ) )
			return;

		// Each blog has its own existing advanced_ads_group_options for the terms.
		$bcd->advanced_ads->collection( 'existing_advanced_ads_group_options' )->flush();
	}

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->advanced_ads ) )
			return;

		$this->maybe_restore_advanced_ads_ad_group_ids( $bcd );
		$this->maybe_restore_advanced_ads_ad_options( $bcd );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->maybe_save_advanced_ads_ad_group_ids( $bcd );
		$this->maybe_save_advanced_ads_ad_options( $bcd );
	}

	/**
		@brief		Add our post types.
		@since		2026-01-21 17:55:45
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'advanced_ads' );
	}

	/**
		@brief		We need to save the ads groups options, containing the ad weights.
		@since		2026-01-21 18:39:05
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		if ( $action->taxonomy != 'advanced_ads_groups' )
			return;

		$bcd = $action->broadcasting_data;

		$existing_term_meta = get_term_meta( $action->new_term->term_id, 'advanced_ads_group_options' );
		$existing_term_meta = reset( $existing_term_meta );
		if ( ! $existing_term_meta )
			return;
		$bcd->advanced_ads->collection( 'existing_advanced_ads_group_options' )
			->set( $action->old_term->term_id, $existing_term_meta );
		$this->debug( 'Saving existing advanced_ads_group_options for term %s: %s', $action->old_term->term_id, $existing_term_meta );
	}
}
