<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief			Adds support for the Advanced Post Creation add-on.
	@since			2021-03-10 21:18:39
**/
class Advanced_Post_Creation
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2017-04-27 22:46:42
	**/
	public function _construct()
	{
		$this->add_action( 'broadcast_gf_addon_feed_sync' );
	}

	/**
		@brief		Parse the add-on meta.
		@since		2021-03-10 21:19:31
	**/
	public function broadcast_gf_addon_feed_sync( $action )
	{
		global $wpdb;
		// Find the equivalent feeds.
		foreach( $action->source_feeds as $source_feed_index => $source_feed )
		{
			if ( $source_feed->addon_slug != 'gravityformsadvancedpostcreation' )
				continue;
			$source_meta = json_decode( $source_feed->meta );
			if ( ! isset( $source_meta->feedName ) )
				continue;
			foreach( $action->target_feeds as $target_feed_index => $target_feed )
			{
				if ( $target_feed->addon_slug != 'gravityformsadvancedpostcreation' )
					continue;
				$target_meta = json_decode( $target_feed->meta );

				if ( ! isset( $target_meta->feedName ) )
					continue;

				if ( $source_meta->feedName == $target_meta->feedName )
				{
					// We have found a match!
					$action->feed_ids[ $source_feed->id ] = $target_feed->id;

					// Remove the target since we are handling it.
					unset( $action->target_feeds[ $target_feed_index ] );
					// And the source is also handled.
					unset( $action->source_feeds[ $source_feed_index ] );

					switch_to_blog( $action->broadcasting_data->parent_blog_id );
					$this->preparse_meta( $source_meta, $action->broadcasting_data );
					restore_current_blog();

					$new_meta = $this->parse_meta( $source_meta, $action->broadcasting_data );

					if ( json_encode( $target_meta ) == json_encode( $new_meta ) )
						continue;

					$table = Gravity_Forms::instance()->rg_gf_table( 'addon_feed' );
					$this->debug( 'Updating target addon_feed %s: %s', $target_feed->id, $new_meta );
					$wpdb->update( $table, [ 'meta' => json_encode( $new_meta ) ], [ 'id' => $target_feed->id ] );
				}
			}
		}
	}

	/**
		@brief		Parse this meta array.
		@since		2021-03-10 21:24:36
	**/
	public function parse_meta( $meta, $bcd )
	{
		if ( is_object( $meta ) )
			foreach( (array)$meta as $index => $value )
				$meta->$index = $this->parse_meta( $value, $bcd );

		if ( is_array( $meta ) )
			foreach( $meta as $index => $value )
				$meta[ $index ] = $this->parse_meta( $value, $bcd );

		if ( ! is_array( $meta ) && ! is_object( $meta ) )
		{
			$parse_action = new \threewp_broadcast\actions\parse_content();
			$parse_action->broadcasting_data = $bcd;
			$parse_action->content = $meta;
			$parse_action->id = 'Advanced_Post_Creation' . md5( json_encode( $meta ) );
			$parse_action->execute();
			$meta = $parse_action->content;
		}
		return $meta;
	}

	/**
		@brief		Preparse this meta array.
		@since		2021-03-10 21:24:36
	**/
	public function preparse_meta( $meta, $bcd )
	{
		if ( is_object( $meta ) )
			foreach( (array)$meta as $index => $value )
				$meta->$index = $this->preparse_meta( $value, $bcd );

		if ( is_array( $meta ) )
			foreach( $meta as $index => $value )
				$meta[ $index ] = $this->preparse_meta( $value, $bcd );

		if ( ! is_array( $meta ) && ! is_object( $meta ) )
		{
			$preparse_action = new \threewp_broadcast\actions\preparse_content();
			$preparse_action->broadcasting_data = $bcd;
			$preparse_action->content = $meta;
			$preparse_action->id = 'Advanced_Post_Creation' . md5( json_encode( $meta ) );
			$preparse_action->execute();
			$meta = $preparse_action->content;
		}
		return $meta;
	}
}
