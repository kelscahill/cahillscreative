<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief			Adds support for the Gravity Flow add-on.
	@since			2021-01-11 19:46:29
**/
class Gravity_Flow
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2017-04-27 22:46:42
	**/
	public function _construct()
	{
		$this->add_action( 'broadcast_gf_addon_feed_sync' );
		$this->add_action( 'broadcast_gf_addon_feed_sync', 'broadcast_gf_addon_feed_sync_after', 200 );		// Handle the different destinations.
	}

	/**
		@brief		Handle the syncing of gravityflow addon feeds.
		@since		2021-01-11 19:46:29
	**/
	public function broadcast_gf_addon_feed_sync( $action )
	{
		global $wpdb;

		// Find the equivalent feeds.
		foreach( $action->source_feeds as $source_feed_index => $source_feed )
		{
			if ( $source_feed->addon_slug != 'gravityflow' )
				continue;
			$source_meta = json_decode( $source_feed->meta );
			if ( ! isset( $source_meta->step_name ) )
				continue;
			foreach( $action->target_feeds as $target_feed_index => $target_feed )
			{
				if ( $target_feed->addon_slug != 'gravityflow' )
					continue;
				$target_meta = json_decode( $target_feed->meta );

				if ( ! isset( $target_meta->step_name ) )
					continue;

				if ( $source_meta->step_name == $target_meta->step_name )
				{
					// We have found a match!
					$action->feed_ids[ $source_feed->id ] = $target_feed->id;

					// Remove the target since we are handling it.
					unset( $action->target_feeds[ $target_feed_index ] );
					// And the source is also handled.
					unset( $action->source_feeds[ $source_feed_index ] );

					// Update the columns that are not the ID and form ID.
					$new_data = clone( $source_feed );
					unset( $new_data->id );
					unset( $new_data->form_id );

					$table = Gravity_Forms::instance()->rg_gf_table( 'addon_feed' );
					$this->debug( 'Updating target addon_feed %s: %s', $target_feed->id, $new_data );
					$wpdb->update( $table, (array) $new_data, [ 'id' => $target_feed->id ] );
				}
			}
		}
	}

	/**
		@brief		Fix the destination step IDs.
		@since		2021-01-12 22:23:07
	**/
	public function broadcast_gf_addon_feed_sync_after( $action )
	{
		global $wpdb;

		$feeds = Gravity_Forms::instance()->get_addon_feeds( $action->target_form_id, $wpdb->prefix );
		$table = Gravity_Forms::instance()->rg_gf_table( 'addon_feed' );

		foreach( $feeds as $feed )
		{
			if ( $feed->addon_slug != 'gravityflow' )
				continue;
			$meta = json_decode( $feed->meta );
			$modified = false;

			foreach( $meta as $key => $value )
			{
				if ( strpos( $key, 'destination_' ) === 0 )
				{
					// We are only interested in values that are integers, not strings.
					$value_int = intval( $value );
					if ( strlen( $value_int ) != strlen( $value ) )
						continue;
					$new_value = $action->feed_ids[ $value ];
					$this->debug( 'New value for meta key %s (%s) is %s', $key, $value, $new_value );
					$meta->$key = $new_value;
					$modified = true;
				}

				if ( strpos( $key, 'feed_' ) === 0 )
				{
					$feed_value = str_replace( 'feed_', '', $key );
					$feed_intval = intval( $feed_value );
					// We want only keys that start with feed_ and end in a number.
					if ( strlen( $feed_intval ) != strlen( $feed_value ) )
						continue;
					$new_value = $action->feed_ids[ $feed_value ];

					// Ignore this key if it has no equivalent. It could well be the same key we just created.
					if ( ! $new_value )
						continue;

					$new_key = 'feed_' . $new_value;
					$this->debug( 'New key for meta key %s (%s) is %s', $key, $value, $new_key );
					unset( $meta->$key );
					$meta->$new_key = $value;
					$modified = true;
				}

				if ( $key == 'target_form_id' )
				{
					$new_form = broadcast_gravity_forms()->find_equivalent_form( [
						'source_forms' => $action->source_forms,
						'target_forms' => $action->target_forms,
						'key' => 'id',
						'value' => $value,
					] );
					if ( ! $new_form )
						continue;
					$new_value = $new_form->id;
					$this->debug( 'New value for meta key %s (%s) is %s', $key, $value, $new_value );
					$meta->$key = $new_value;
					$modified = true;
				}
			}

			if ( $modified )
			{
				$feed->meta = json_encode( $meta );
				$this->debug( 'Updating feed %s', $feed->id );
				$wpdb->update( $table, [ 'meta' => json_encode( $meta ) ], [ 'id' => $feed->id ] );
			}
		}
	}
}
